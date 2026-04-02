<?php

/** @var array<string, mixed> $result */
/** @var array<string, mixed> $filters */
/** @var array<int, array<string, mixed>> $products */

$result = $result ?? ['items' => [], 'total' => 0, 'page' => 1, 'total_pages' => 1];
$items = $result['items'] ?? [];
$page = (int) ($result['page'] ?? 1);
$totalPages = (int) ($result['total_pages'] ?? 1);
$filters = $filters ?? [];
$products = $products ?? [];
$basePath = $basePath ?? '';

if (!function_exists('lotesQueryBuild')) {
    /**
     * @param array<string, mixed> $filters
     */
    function lotesQueryBuild(array $filters, int $pagina): string
    {
        $query = ['pagina' => $pagina];
        foreach (['q', 'producto_id', 'vencimiento'] as $key) {
            if (!empty($filters[$key])) {
                $query[$key] = (string) $filters[$key];
            }
        }

        return http_build_query($query);
    }
}

$today = date('Y-m-d');

ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-hourglass-split me-2" style="color:var(--teal);"></i> Lotes y caducidades</h1>
        <p class="text-muted small mb-0">Registro de lotes por producto con cantidad y fecha de caducidad. El stock del catálogo no se modifica automáticamente.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/lotes/crear', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nuevo lote
    </a>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/lotes', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Producto</label>
                <select name="producto_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) ($p['id'] ?? 0) ?>" <?= (int) ($filters['producto_id'] ?? 0) === (int) ($p['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Lote / producto / SKU</label>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar…">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Caducidad</label>
                <select name="vencimiento" class="form-select">
                    <option value="todos" <?= ($filters['vencimiento'] ?? 'todos') === 'todos' ? 'selected' : '' ?>>Todos</option>
                    <option value="proximos_30" <?= ($filters['vencimiento'] ?? '') === 'proximos_30' ? 'selected' : '' ?>>Próximos 30 días</option>
                    <option value="vencidos" <?= ($filters['vencimiento'] ?? '') === 'vencidos' ? 'selected' : '' ?>>Vencidos</option>
                    <option value="sin_fecha" <?= ($filters['vencimiento'] ?? '') === 'sin_fecha' ? 'selected' : '' ?>>Sin fecha</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/lotes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col">Lote</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Caducidad</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                No hay lotes. <a href="<?= htmlspecialchars($basePath . '/admin/lotes/crear', ENT_QUOTES, 'UTF-8') ?>">Registrar el primero</a>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): ?>
                            <?php
                            $exp = $row['expiry_date'] ?? null;
                            $expStr = $exp ? (string) $exp : '';
                            $badgeClass = 'bg-secondary';
                            $label = '—';
                            if ($expStr !== '') {
                                if ($expStr < $today) {
                                    $badgeClass = 'bg-danger';
                                    $label = 'Vencido';
                                } elseif ($expStr <= date('Y-m-d', strtotime('+30 days'))) {
                                    $badgeClass = 'bg-warning text-dark';
                                    $label = 'Próximo';
                                } else {
                                    $badgeClass = 'bg-success';
                                    $label = 'Vigente';
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($row['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($row['product_sku'])): ?>
                                        <div class="small text-muted">SKU: <?= htmlspecialchars((string) $row['product_sku'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="font-monospace"><?= htmlspecialchars((string) ($row['lot_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars(rtrim(rtrim(number_format((float) ($row['quantity'] ?? 0), 3, '.', ''), '0'), '.')) ?>
                                    <span class="text-muted small"><?= htmlspecialchars((string) ($row['product_unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <?php if ($expStr !== ''): ?>
                                        <span class="me-1"><?= htmlspecialchars($expStr, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin fecha</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($basePath . '/admin/lotes/editar/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="<?= htmlspecialchars($basePath . '/admin/lotes/eliminar/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este lote?');">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <nav>
                <ul class="pagination pagination-sm mb-0 justify-content-center">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/lotes?' . lotesQueryBuild($filters, $p), ENT_QUOTES, 'UTF-8') ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Lotes y caducidades';
require __DIR__ . '/../../layouts/admin.php';
