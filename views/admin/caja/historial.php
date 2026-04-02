<?php
$items = $items ?? [];
$total = $total ?? 0;
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$per_page = $per_page ?? 15;
$filters = $filters ?? ['desde' => '', 'hasta' => '', 'estado' => ''];
$basePath = $basePath ?? '';
$pageTitle = $pageTitle ?? 'Historial de caja';
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-clock-history me-2" style="color:var(--teal);"></i> Historial de caja</h1>
        <p class="text-muted small mb-0">Aperturas, cierres y cortes de sesiones anteriores.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/caja', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary">
        <i class="bi bi-cash-coin"></i> Caja actual
    </a>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/caja/historial', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filters['desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($filters['hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="OPEN" <?= ($filters['estado'] ?? '') === 'OPEN' ? 'selected' : '' ?>>Abierta</option>
                    <option value="CLOSED" <?= ($filters['estado'] ?? '') === 'CLOSED' ? 'selected' : '' ?>>Cerrada</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/caja/historial', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
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
                        <th scope="col">Id</th>
                        <th scope="col">Apertura</th>
                        <th scope="col">Cierre</th>
                        <th scope="col">Responsable</th>
                        <th scope="col">Inicial</th>
                        <th scope="col">Esperado</th>
                        <th scope="col">Contado</th>
                        <th scope="col">Diferencia</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">No hay sesiones que coincidan con el filtro.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): ?>
                            <?php
                            $status = $row['status'] ?? 'CLOSED';
                            $diff = isset($row['difference']) ? (float) $row['difference'] : null;
                            ?>
                            <tr>
                                <td class="small">#<?= (int)($row['id'] ?? 0) ?></td>
                                <td class="small"><?= date('d/m/Y H:i', strtotime($row['opened_at'] ?? 'now')) ?></td>
                                <td class="small"><?= !empty($row['closed_at']) ? date('d/m/Y H:i', strtotime($row['closed_at'])) : '—' ?></td>
                                <td class="small"><?= htmlspecialchars($row['opened_by_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small">$<?= number_format((float)($row['initial_amount'] ?? 0), 2, '.', ',') ?></td>
                                <td class="small"><?= $row['expected_amount'] !== null ? '$' . number_format((float)$row['expected_amount'], 2, '.', ',') : '—' ?></td>
                                <td class="small"><?= $row['counted_amount'] !== null ? '$' . number_format((float)$row['counted_amount'], 2, '.', ',') : '—' ?></td>
                                <td class="small">
                                    <?php if ($diff !== null): ?>
                                        <span class="<?= $diff >= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($diff, 2, '.', ',') ?></span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill <?= $status === 'OPEN' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $status === 'OPEN' ? 'Abierta' : 'Cerrada' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($basePath . '/admin/caja/detalle/' . (int)($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">Mostrando <?= count($items) ?> de <?= $total ?> sesiones</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/caja/historial?pagina=' . ($page - 1) . ($filters['desde'] ? '&desde=' . rawurlencode($filters['desde']) : '') . ($filters['hasta'] ? '&hasta=' . rawurlencode($filters['hasta']) : '') . ($filters['estado'] ? '&estado=' . rawurlencode($filters['estado']) : ''), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/caja/historial?pagina=' . $i . ($filters['desde'] ? '&desde=' . rawurlencode($filters['desde']) : '') . ($filters['hasta'] ? '&hasta=' . rawurlencode($filters['hasta']) : '') . ($filters['estado'] ? '&estado=' . rawurlencode($filters['estado']) : ''), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/caja/historial?pagina=' . ($page + 1) . ($filters['desde'] ? '&desde=' . rawurlencode($filters['desde']) : '') . ($filters['hasta'] ? '&hasta=' . rawurlencode($filters['hasta']) : '') . ($filters['estado'] ? '&estado=' . rawurlencode($filters['estado']) : ''), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a>
                            </li>
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
