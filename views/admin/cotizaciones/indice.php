<?php
$items = $items ?? [];
$total = $total ?? 0;
$page = $page ?? 1;
$totalPages = $total_pages ?? 1;
$filters = $filters ?? [];
$tab = $tab ?? 'all';
$basePath = $basePath ?? '';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}
$pageTitle = $pageTitle ?? 'Cotizaciones';

if (!function_exists('cotizacionesQueryBuild')) {
    function cotizacionesQueryBuild(array $filters, int $pagina, string $tab): string
    {
        $query = ['pagina' => $pagina, 'tab' => $tab];
        foreach (['q', 'sort'] as $key) {
            if (!empty($filters[$key])) {
                $query[$key] = (string) $filters[$key];
            }
        }
        return http_build_query($query);
    }
}

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-file-earmark-text me-2" style="color:var(--teal);"></i> Cotizaciones</h1>
        <p class="text-muted small mb-0">Crea cotizaciones y envíaselas a tus clientes.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/cotizaciones/crear', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Crear cotización
    </a>
</div>

<div class="card border-0 card-shadow rounded-4 mb-3">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/cotizaciones', ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-12 col-lg">
                <label class="form-label small text-muted mb-1">Buscar</label>
                <input type="search" name="q" class="form-control" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Filtrar por número de cotización, cliente, vendedor o productos">
            </div>
            <div class="col-6 col-md-auto">
                <label class="form-label small text-muted mb-1">Ordenar</label>
                <select name="sort" class="form-select">
                    <option value="date_desc" <?= ($filters['sort'] ?? '') === 'date_desc' ? 'selected' : '' ?>>Más recientes</option>
                    <option value="date_asc" <?= ($filters['sort'] ?? '') === 'date_asc' ? 'selected' : '' ?>>Más antiguas</option>
                    <option value="total_desc" <?= ($filters['sort'] ?? '') === 'total_desc' ? 'selected' : '' ?>>Mayor total</option>
                    <option value="total_asc" <?= ($filters['sort'] ?? '') === 'total_asc' ? 'selected' : '' ?>>Menor total</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/cotizaciones?tab=' . urlencode($tab), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<?php
$tabQs = function (string $t) use ($filters): string {
    $q = ['tab' => $t];
    if (!empty($filters['q'])) {
        $q['q'] = (string) $filters['q'];
    }
    if (!empty($filters['sort'])) {
        $q['sort'] = (string) $filters['sort'];
    }
    return http_build_query($q);
};
?>
<ul class="nav nav-pills gap-2 mb-3 flex-wrap">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/cotizaciones?' . $tabQs('all'), ENT_QUOTES, 'UTF-8') ?>">Todas</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'open' ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/cotizaciones?' . $tabQs('open'), ENT_QUOTES, 'UTF-8') ?>">Abiertas</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'sold' ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/cotizaciones?' . $tabQs('sold'), ENT_QUOTES, 'UTF-8') ?>">Vendidas</a>
    </li>
</ul>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cotización</th>
                        <th>Fecha</th>
                        <th>Creada por</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No hay cotizaciones que coincidan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): ?>
                            <?php
                            $st = (string) ($row['status'] ?? 'OPEN');
                            $badgeClass = 'text-bg-secondary';
                            $label = $st === 'SOLD' ? 'Vendida' : 'Abierta';
                            if ($st === 'OPEN') {
                                $badgeClass = 'text-white';
                                $badgeStyle = 'background:#14b8a6 !important;';
                            } else {
                                $badgeStyle = '';
                            }
                            $created = !empty($row['created_at']) ? strtotime((string) $row['created_at']) : false;
                            $fechaTxt = $created ? date('d/m/Y H:i', $created) : '—';
                            $today = date('Y-m-d');
                            if ($created && date('Y-m-d', $created) === $today) {
                                $fechaTxt = 'Hoy a las ' . date('H:i', $created);
                            }
                            ?>
                            <tr>
                                <td class="fw-semibold">#<?= (int) ($row['folio'] ?? 0) ?></td>
                                <td class="small"><?= htmlspecialchars($fechaTxt, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars(trim((string) ($row['created_by_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?php
                                    $cn = trim((string) ($row['customer_name'] ?? ''));
                                    echo $cn !== '' ? htmlspecialchars($cn, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>';
                                ?></td>
                                <td>
                                    <?php if ($st === 'OPEN'): ?>
                                        <span class="badge rounded-pill" style="<?= htmlspecialchars($badgeStyle, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold">$<?= number_format((float) ($row['total'] ?? 0), 2, '.', ',') ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= htmlspecialchars($basePath . '/admin/cotizaciones/documento/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" title="Ver / PDF" target="_blank" rel="noopener">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                        <?php if ($st === 'OPEN'): ?>
                                            <form method="post" action="<?= htmlspecialchars($basePath . '/admin/cotizaciones/marcar-vendida/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="d-inline" onsubmit="return confirm('¿Marcar esta cotización como vendida?');">
                                                <button type="submit" class="btn btn-outline-secondary" title="Marcar como vendida"><i class="bi bi-check2-circle"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">Mostrando <?= count($items) ?> de <?= (int) $total ?> cotizaciones</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/cotizaciones?' . cotizacionesQueryBuild($filters, $page - 1, $tab), ENT_QUOTES, 'UTF-8') ?>">Anterior</a></li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/cotizaciones?' . cotizacionesQueryBuild($filters, $i, $tab), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/cotizaciones?' . cotizacionesQueryBuild($filters, $page + 1, $tab), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
