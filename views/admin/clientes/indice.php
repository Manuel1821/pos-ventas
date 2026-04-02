<?php
$customers = $customers ?? [];
$total = $total ?? 0;
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$per_page = $per_page ?? 15;
$search = $search ?? '';
$statusFilter = $statusFilter ?? null;
$basePath = $basePath ?? '';
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-people me-2" style="color:var(--teal);"></i> Catálogo de clientes</h1>
        <p class="text-muted small mb-0">Administra clientes para ventas y reportes. Usa "Público en general" para ventas rápidas.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= htmlspecialchars($basePath . '/admin/clientes/crear', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo cliente
        </a>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/clientes', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <div class="col-12 col-md-5">
                <label class="form-label small text-muted">Buscar</label>
                <input type="text" name="buscar" class="form-control" placeholder="Nombre, teléfono o correo" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="ACTIVE" <?= $statusFilter === 'ACTIVE' ? 'selected' : '' ?>>Activos</option>
                    <option value="INACTIVE" <?= $statusFilter === 'INACTIVE' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/clientes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
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
                        <th scope="col">Cliente</th>
                        <th scope="col" class="text-end">Deuda total</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col">Correo</th>
                        <th scope="col">RFC</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Registro</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                No hay clientes que coincidan con el filtro.
                                <?php if ($search === '' && $statusFilter === null): ?>
                                    <a href="<?= htmlspecialchars($basePath . '/admin/clientes/crear', ENT_QUOTES, 'UTF-8') ?>">Registrar el primero</a>.
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($basePath . '/admin/clientes', ENT_QUOTES, 'UTF-8') ?>">Ver todos</a>.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $c): ?>
                            <?php
                            $isPublic = !empty($c['is_public']);
                            $status = $c['status'] ?? 'ACTIVE';
                            $debtTotal = (float) ($c['debt_total'] ?? 0);
                            ?>
                            <tr class="<?= $isPublic ? 'table-light' : '' ?>">
                                <td>
                                    <div class="fw-semibold">
                                        <a class="text-decoration-none" href="<?= htmlspecialchars($basePath . '/admin/clientes/deuda/' . (int) ($c['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <?php if ($isPublic): ?>
                                            <span class="badge bg-info ms-1">Genérico</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($c['address'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width:220px;"><?= htmlspecialchars($c['address'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold" style="<?= $debtTotal > 0.009 ? 'color:#f59e0b;' : '' ?>">
                                    $<?= htmlspecialchars(number_format($debtTotal, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td><?= htmlspecialchars($c['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($c['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($c['rfc'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge rounded-pill <?= $status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $status === 'ACTIVE' ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= date('d/m/Y', strtotime($c['created_at'] ?? 'now')) ?></td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($basePath . '/admin/clientes/editar/' . (int)($c['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if (!$isPublic): ?>
                                        <form action="<?= htmlspecialchars($basePath . '/admin/clientes/cambiar-estado/' . (int)($c['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline">
                                            <button type="submit" class="btn btn-sm btn-outline-<?= $status === 'ACTIVE' ? 'warning' : 'success' ?>" title="<?= $status === 'ACTIVE' ? 'Desactivar' : 'Activar' ?>">
                                                <i class="bi bi-<?= $status === 'ACTIVE' ? 'pause' : 'play' ?>"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">
                    Mostrando <?= count($customers) ?> de <?= $total ?> clientes
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/clientes?pagina=' . ($page - 1) . ($search !== '' ? '&buscar=' . rawurlencode($search) : '') . ($statusFilter !== null ? '&estado=' . $statusFilter : ''), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/clientes?pagina=' . $i . ($search !== '' ? '&buscar=' . rawurlencode($search) : '') . ($statusFilter !== null ? '&estado=' . $statusFilter : ''), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/clientes?pagina=' . ($page + 1) . ($search !== '' ? '&buscar=' . rawurlencode($search) : '') . ($statusFilter !== null ? '&estado=' . $statusFilter : ''), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a>
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
$pageTitle = 'Clientes';
require __DIR__ . '/../../layouts/admin.php';
