<?php
$products = $products ?? [];
$total = $total ?? 0;
$page = $page ?? 1;
$total_pages = $total_pages ?? 1;
$per_page = $per_page ?? 15;
$search = $search ?? '';
$categoryId = $categoryId ?? null;
$statusFilter = $statusFilter ?? null;
$categories = $categories ?? [];
$basePath = $basePath ?? '';
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-box-seam me-2" style="color:var(--teal);"></i> Catálogo de productos</h1>
        <p class="text-muted small mb-0">Administra los productos para ventas e inventario.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= htmlspecialchars($basePath . '/admin/categorias', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-tags"></i> Categorías
        </a>
        <a href="<?= htmlspecialchars($basePath . '/admin/productos/crear', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo producto
        </a>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/productos', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Buscar</label>
                <input type="text" name="buscar" class="form-control" placeholder="Nombre, SKU o código de barras" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Categoría</label>
                <select name="categoria" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="ACTIVE" <?= $statusFilter === 'ACTIVE' ? 'selected' : '' ?>>Activos</option>
                    <option value="INACTIVE" <?= $statusFilter === 'INACTIVE' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/productos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
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
                        <th scope="col" style="width:60px;">Imagen</th>
                        <th scope="col">Producto</th>
                        <th scope="col">SKU / Código</th>
                        <th scope="col">Categoría</th>
                        <th scope="col" class="text-end">Precio</th>
                        <th scope="col" class="text-end">Stock</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No hay productos. <a href="<?= htmlspecialchars($basePath . '/admin/productos/crear', ENT_QUOTES, 'UTF-8') ?>">Crear el primero</a>.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($p['image_path'])): ?>
                                        <img src="<?= htmlspecialchars($basePath . '/admin/productos/' . (int)($p['id'] ?? 0) . '/imagen-miniatura', ENT_QUOTES, 'UTF-8') ?>" alt="" class="rounded" style="width:48px;height:48px;object-fit:cover;" loading="lazy">
                                    <?php else: ?>
                                        <div class="rounded d-flex align-items-center justify-content-center bg-light text-muted" style="width:48px;height:48px;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($p['description'])): ?>
                                        <div class="small text-muted text-truncate" style="max-width:200px;"><?= htmlspecialchars($p['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small"><?= htmlspecialchars($p['sku'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($p['barcode'])): ?>
                                        <br><span class="text-muted small"><?= htmlspecialchars($p['barcode'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['category_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-semibold"><?= number_format((float)($p['price'] ?? 0), 2) ?></td>
                                <td class="text-end">
                                    <?php $stock = (float)($p['stock'] ?? 0); ?>
                                    <span class="badge rounded-pill <?= $stock <= 0 ? 'bg-danger' : 'bg-success' ?>"><?= number_format($stock, 2) ?></span>
                                </td>
                                <td>
                                    <?php $status = $p['status'] ?? 'ACTIVE'; ?>
                                    <span class="badge rounded-pill <?= $status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $status === 'ACTIVE' ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($basePath . '/admin/productos/editar/' . (int)($p['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="<?= htmlspecialchars($basePath . '/admin/productos/cambiar-estado/' . (int)($p['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-outline-<?= $status === 'ACTIVE' ? 'warning' : 'success' ?>" title="<?= $status === 'ACTIVE' ? 'Desactivar' : 'Activar' ?>">
                                            <i class="bi bi-<?= $status === 'ACTIVE' ? 'pause' : 'play' ?>"></i>
                                        </button>
                                    </form>
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
                    Mostrando <?= count($products) ?> de <?= $total ?> productos
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/productos?pagina=' . ($page - 1) . ($search !== '' ? '&buscar=' . rawurlencode($search) : '') . ($categoryId !== null ? '&categoria=' . $categoryId : '') . ($statusFilter !== null ? '&estado=' . $statusFilter : ''), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/productos?pagina=' . $i . ($search !== '' ? '&buscar=' . rawurlencode($search) : '') . ($categoryId !== null ? '&categoria=' . $categoryId : '') . ($statusFilter !== null ? '&estado=' . $statusFilter : ''), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/productos?pagina=' . ($page + 1) . ($search !== '' ? '&buscar=' . rawurlencode($search) : '') . ($categoryId !== null ? '&categoria=' . $categoryId : '') . ($statusFilter !== null ? '&estado=' . $statusFilter : ''), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a>
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
$pageTitle = 'Productos';
require __DIR__ . '/../../layouts/admin.php';
