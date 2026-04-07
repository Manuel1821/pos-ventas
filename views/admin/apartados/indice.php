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
$pageTitle = $pageTitle ?? 'Apartados';

if (!function_exists('apartadosQueryBuild')) {
    function apartadosQueryBuild(array $filters, int $pagina, string $tab): string
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
        <h1 class="h4 mb-1"><i class="bi bi-archive me-2" style="color:var(--teal);"></i> Apartados</h1>
        <p class="text-muted small mb-0">Registra apartados, anticipo y abonos.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/apartados/crear', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Crear apartado
    </a>
</div>

<div class="card border-0 card-shadow rounded-4 mb-3">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/apartados', ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-12 col-lg">
                <label class="form-label small text-muted mb-1">Buscar</label>
                <input type="search" name="q" class="form-control" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Folio, cliente o producto">
            </div>
            <div class="col-6 col-md-auto">
                <label class="form-label small text-muted mb-1">Ordenar</label>
                <select name="sort" class="form-select">
                    <option value="date_desc" <?= ($filters['sort'] ?? '') === 'date_desc' ? 'selected' : '' ?>>Más recientes</option>
                    <option value="date_asc" <?= ($filters['sort'] ?? '') === 'date_asc' ? 'selected' : '' ?>>Más antiguos</option>
                    <option value="total_desc" <?= ($filters['sort'] ?? '') === 'total_desc' ? 'selected' : '' ?>>Mayor total</option>
                    <option value="total_asc" <?= ($filters['sort'] ?? '') === 'total_asc' ? 'selected' : '' ?>>Menor total</option>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/apartados?tab=' . urlencode($tab), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
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
    <li class="nav-item"><a class="nav-link <?= $tab === 'all' ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/apartados?' . $tabQs('all'), ENT_QUOTES, 'UTF-8') ?>">Todos</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'open' ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/apartados?' . $tabQs('open'), ENT_QUOTES, 'UTF-8') ?>">Abiertos</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'paid' ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/apartados?' . $tabQs('paid'), ENT_QUOTES, 'UTF-8') ?>">Pagados</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab === 'cancelled' ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/apartados?' . $tabQs('cancelled'), ENT_QUOTES, 'UTF-8') ?>">Cancelados</a></li>
</ul>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Cuenta</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">No hay apartados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): ?>
                            <?php
                            $labels = ['OPEN' => 'Abierto', 'PAID' => 'Pagado', 'CANCELLED' => 'Cancelado', 'MIXED' => 'Varios'];
                            $classes = ['OPEN' => 'text-bg-warning', 'PAID' => 'text-bg-success', 'CANCELLED' => 'text-bg-secondary', 'MIXED' => 'text-bg-info'];
                            $stDistinct = (int) ($row['status_distinct_count'] ?? 1);
                            $st = $stDistinct > 1 ? 'MIXED' : (string) ($row['status_sample'] ?? 'OPEN');
                            $created = !empty($row['created_at']) ? strtotime((string) $row['created_at']) : false;
                            $fechaTxt = $created ? date('d/m/Y H:i', $created) : '—';
                            $totalAmt = (float) ($row['total'] ?? 0);
                            $paidAmt = (float) ($row['paid_total'] ?? 0);
                            $balance = max(0, $totalAmt - $paidAmt);
                            $useDebtList = ($tab === 'all' || $tab === 'open');
                            $debtIdsRaw = trim((string) ($row['layaway_ids_debt_csv'] ?? ''));
                            if ($useDebtList && $debtIdsRaw !== '') {
                                $folioTokens = array_values(array_filter(array_map('trim', explode(',', (string) ($row['folios_debt_csv'] ?? '')))));
                                $idTokens = array_values(array_filter(array_map('intval', explode(',', $debtIdsRaw))));
                                $layawayCount = max(1, (int) ($row['layaway_count_debt'] ?? count($idTokens)));
                            } else {
                                $folioTokens = array_values(array_filter(array_map('trim', explode(',', (string) ($row['folios_csv'] ?? '')))));
                                $idTokens = array_values(array_filter(array_map('intval', explode(',', (string) ($row['layaway_ids_csv'] ?? '')))));
                                $layawayCount = max(1, (int) ($row['layaway_count'] ?? 1));
                            }
                            $pairs = [];
                            foreach ($idTokens as $i => $lid) {
                                if ($lid <= 0) {
                                    continue;
                                }
                                $pairs[] = ['id' => $lid, 'folio' => $folioTokens[$i] ?? '?'];
                            }
                            $cuentaHtml = '';
                            if ($layawayCount <= 1 && count($pairs) === 1) {
                                $cuentaHtml = '<span class="fw-semibold">#' . htmlspecialchars((string) $pairs[0]['folio'], ENT_QUOTES, 'UTF-8') . '</span>';
                            } else {
                                $fols = array_map(static function ($p) {
                                    return '#' . htmlspecialchars((string) $p['folio'], ENT_QUOTES, 'UTF-8');
                                }, $pairs);
                                $cuentaHtml = '<div class="fw-semibold">' . (int) $layawayCount . ' apartados</div>'
                                    . '<div class="small text-muted">' . implode(', ', $fols) . '</div>';
                            }
                            $custName = trim((string) ($row['customer_name'] ?? ''));
                            if ($custName === '') {
                                $custName = 'Sin cliente';
                            }
                            $ddId = 'ap_acc_' . preg_replace('/[^0-9\-]/', '', (string) ($row['grp'] ?? '0')) . '_' . substr(sha1((string) json_encode($pairs)), 0, 8);
                            ?>
                            <tr>
                                <td><?= $cuentaHtml ?></td>
                                <td class="small"><?= htmlspecialchars($fechaTxt, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars($custName, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge rounded-pill <?= htmlspecialchars($classes[$st] ?? 'text-bg-light', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($labels[$st] ?? $st, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-end fw-semibold">$<?= number_format($totalAmt, 2, '.', ',') ?></td>
                                <td class="text-end">$<?= number_format($paidAmt, 2, '.', ',') ?></td>
                                <td class="text-end"><?= $balance > 0 ? '$' . number_format($balance, 2, '.', ',') : '<span class="text-success fw-semibold">Liquidado</span>' ?></td>
                                <td class="text-end">
                                    <?php if (count($pairs) === 1): ?>
                                        <a href="<?= htmlspecialchars($basePath . '/admin/apartados/documento/' . (int) $pairs[0]['id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php elseif (count($pairs) > 1): ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' data-bs-boundary="viewport" aria-expanded="false" id="<?= htmlspecialchars($ddId, ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="<?= htmlspecialchars($ddId, ENT_QUOTES, 'UTF-8') ?>">
                                                <?php foreach ($pairs as $p): ?>
                                                    <li>
                                                        <a class="dropdown-item small" href="<?= htmlspecialchars($basePath . '/admin/apartados/documento/' . (int) $p['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                            Apartado #<?= htmlspecialchars((string) $p['folio'], ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">Mostrando <?= count($items) ?> de <?= (int) $total ?> cuentas (clientes)</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/apartados?' . apartadosQueryBuild($filters, $page - 1, $tab), ENT_QUOTES, 'UTF-8') ?>">Anterior</a></li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/apartados?' . apartadosQueryBuild($filters, $i, $tab), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/apartados?' . apartadosQueryBuild($filters, $page + 1, $tab), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a></li>
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

