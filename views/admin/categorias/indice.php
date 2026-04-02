<?php
$categories = $categories ?? [];
$basePath = $basePath ?? '';
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-tags me-2" style="color:var(--teal);"></i> Categorías de productos</h1>
        <p class="text-muted small mb-0">Administra las categorías para organizar tu catálogo.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/categorias/crear', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nueva categoría
    </a>
</div>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Nombre</th>
                        <th scope="col">Slug</th>
                        <th scope="col">Estado</th>
                        <th scope="col" class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">No hay categorías. <a href="<?= htmlspecialchars($basePath . '/admin/categorias/crear', ENT_QUOTES, 'UTF-8') ?>">Crear la primera</a>.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($cat['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-muted"><?= htmlspecialchars($cat['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php $status = $cat['status'] ?? 'ACTIVE'; ?>
                                    <span class="badge rounded-pill <?= $status === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $status === 'ACTIVE' ? 'Activa' : 'Inactiva' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($basePath . '/admin/categorias/editar/' . (int)($cat['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="<?= htmlspecialchars($basePath . '/admin/categorias/cambiar-estado/' . (int)($cat['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline">
                                        <button type="submit" class="btn btn-sm btn-outline-<?= $status === 'ACTIVE' ? 'warning' : 'success' ?>">
                                            <?= $status === 'ACTIVE' ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Categorías';
require __DIR__ . '/../../layouts/admin.php';
