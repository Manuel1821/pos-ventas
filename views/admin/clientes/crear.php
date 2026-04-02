<?php
$errors = $errors ?? [];
$old = $old ?? [];
$basePath = $basePath ?? '';
ob_start();
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= htmlspecialchars($basePath . '/admin/clientes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h4 mb-0"><i class="bi bi-person-plus me-2" style="color:var(--teal);"></i> Nuevo cliente</h1>
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
        <form action="<?= htmlspecialchars($basePath . '/admin/clientes/guardar', ENT_QUOTES, 'UTF-8') ?>" method="post">
            <div class="row g-3">
                <div class="col-12 col-lg-6">
                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="200" autofocus>
                </div>
                <div class="col-12 col-lg-6">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="30" placeholder="Opcional">
                </div>
                <div class="col-12 col-lg-6">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="190" placeholder="Opcional">
                </div>
                <div class="col-12 col-lg-6">
                    <label for="rfc" class="form-label">RFC</label>
                    <input type="text" class="form-control" id="rfc" name="rfc" value="<?= htmlspecialchars($old['rfc'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="20" placeholder="Opcional">
                </div>
                <div class="col-12">
                    <label for="address" class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($old['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="500" placeholder="Opcional">
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="1000" placeholder="Opcional"><?= htmlspecialchars($old['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar cliente</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/clientes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Nuevo cliente';
require __DIR__ . '/../../layouts/admin.php';
