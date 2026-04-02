<?php
$errors = $errors ?? [];
$old = $old ?? [];
$basePath = $basePath ?? '';
ob_start();
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= htmlspecialchars($basePath . '/admin/categorias', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h4 mb-0"><i class="bi bi-tag me-2" style="color:var(--teal);"></i> Nueva categoría</h1>
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
        <form action="<?= htmlspecialchars($basePath . '/admin/categorias/guardar', ENT_QUOTES, 'UTF-8') ?>" method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="150" autofocus>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar categoría</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/categorias', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Nueva categoría';
require __DIR__ . '/../../layouts/admin.php';
