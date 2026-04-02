<?php
$category = $category ?? [];
$errors = $errors ?? [];
$old = $old ?? $category;
$basePath = $basePath ?? '';
$id = (int) ($category['id'] ?? 0);
ob_start();
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= htmlspecialchars($basePath . '/admin/categorias', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h4 mb-0"><i class="bi bi-pencil-square me-2" style="color:var(--teal);"></i> Editar categoría</h1>
</div>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-body p-4">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 list-unstyled">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="<?= htmlspecialchars($basePath . '/admin/categorias/actualizar/' . $id, ENT_QUOTES, 'UTF-8') ?>" method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="150" autofocus>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="ACTIVE" <?= ($old['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Activa</option>
                    <option value="INACTIVE" <?= ($old['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactiva</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/categorias', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Editar categoría';
require __DIR__ . '/../../layouts/admin.php';
