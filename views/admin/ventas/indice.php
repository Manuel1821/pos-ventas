<?php
$items = $items ?? [];
$total = $total ?? 0;
$page = $page ?? 1;
$totalPages = $total_pages ?? 1;
$filters = $filters ?? [];
$customers = $customers ?? [];
$sellers = $sellers ?? [];
$basePath = $basePath ?? '';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}
$pageTitle = $pageTitle ?? 'Ventas';

if (!function_exists('ventasQueryBuild')) {
    function ventasQueryBuild(array $filters, int $pagina): string
    {
        $query = ['pagina' => $pagina];
        foreach (['folio', 'desde', 'hasta', 'customer_id', 'user_id', 'status'] as $key) {
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
        <h1 class="h4 mb-1"><i class="bi bi-receipt-cutoff me-2" style="color:var(--teal);"></i> Ventas</h1>
        <p class="text-muted small mb-0">Historial administrativo de ventas con filtros, detalle y reimpresion.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/pos/nueva-venta', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Nueva venta
    </a>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/ventas', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">Folio</label>
                <input type="text" name="folio" class="form-control" value="<?= htmlspecialchars((string) ($filters['folio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. 1024">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars((string) ($filters['desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars((string) ($filters['hasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">Cliente</label>
                <select name="customer_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= (int) ($c['id'] ?? 0) ?>" <?= (int) ($filters['customer_id'] ?? 0) === (int) ($c['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">Vendedor</label>
                <select name="user_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($sellers as $u): ?>
                        <option value="<?= (int) ($u['id'] ?? 0) ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) ($u['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim((string) ($u['name'] ?? 'Usuario')), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">Estado</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="PAID" <?= ($filters['status'] ?? '') === 'PAID' ? 'selected' : '' ?>>Pagada</option>
                    <option value="OPEN" <?= ($filters['status'] ?? '') === 'OPEN' ? 'selected' : '' ?>>Abierta</option>
                    <option value="CANCELLED" <?= ($filters['status'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelada</option>
                    <option value="REFUNDED" <?= ($filters['status'] ?? '') === 'REFUNDED' ? 'selected' : '' ?>>Devuelta</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/ventas', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
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
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Vendedor</th>
                        <th class="text-end">Total</th>
                        <th>Pagos</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No hay ventas que coincidan con los filtros.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): ?>
                            <?php
                            $status = (string) ($row['status'] ?? 'PAID');
                            $badgeClass = 'bg-secondary';
                            $label = $status;
                            if ($status === 'PAID') {
                                $badgeClass = 'bg-success';
                                $label = 'Pagada';
                            } elseif ($status === 'OPEN') {
                                $badgeClass = 'bg-warning text-dark';
                                $label = 'Abierta';
                            } elseif ($status === 'CANCELLED') {
                                $badgeClass = 'bg-danger';
                                $label = 'Cancelada';
                            } elseif ($status === 'REFUNDED') {
                                $badgeClass = 'bg-info text-dark';
                                $label = 'Devuelta';
                            }
                            ?>
                            <tr>
                                <td class="fw-semibold">#<?= (int) ($row['folio'] ?? 0) ?></td>
                                <td class="small"><?= !empty($row['occurred_at']) ? date('d/m/Y H:i', strtotime((string) $row['occurred_at'])) : '—' ?></td>
                                <td class="small"><?= htmlspecialchars((string) ($row['customer_name'] ?? 'Cliente general'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars(trim((string) ($row['seller_name'] ?? 'Usuario')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-semibold">$<?= number_format((float) ($row['total'] ?? 0), 2, '.', ',') ?></td>
                                <td class="small"><?= htmlspecialchars((string) ($row['payment_methods'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= htmlspecialchars($basePath . '/admin/ventas/detalle/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" title="Ver detalle">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= htmlspecialchars($basePath . '/admin/ventas/ticket/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" title="Reimprimir ticket" target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
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
                <div class="small text-muted">Mostrando <?= count($items) ?> de <?= (int) $total ?> ventas</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/ventas?' . ventasQueryBuild($filters, $page - 1), ENT_QUOTES, 'UTF-8') ?>">Anterior</a></li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/ventas?' . ventasQueryBuild($filters, $i), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/ventas?' . ventasQueryBuild($filters, $page + 1), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a></li>
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

