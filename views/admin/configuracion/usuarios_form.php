<?php
$mode = $mode ?? 'create';
$user = $user ?? null;
$errors = $errors ?? [];
$old = $old ?? [];
$basePath = $basePath ?? '';
$isEdit = $mode === 'edit';
$uid = $isEdit && $user ? (int) ($user['id'] ?? 0) : 0;
ob_start();
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= htmlspecialchars($basePath . '/admin/configuracion/usuarios', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h4 mb-0"><i class="bi bi-person-plus me-2" style="color:var(--teal);"></i> <?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h1>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
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

                <form method="post" action="<?= htmlspecialchars($basePath . ($isEdit ? '/admin/configuracion/usuarios/actualizar/' . $uid : '/admin/configuracion/usuarios/guardar'), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required maxlength="100" value="<?= htmlspecialchars($old['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="last_name" class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required maxlength="100" value="<?= htmlspecialchars($old['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required maxlength="190" autocomplete="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-12">
                            <label for="password" class="form-label">Contraseña <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                            <input type="password" class="form-control" id="password" name="password" autocomplete="new-password"<?= $isEdit ? '' : ' required minlength="8"' ?>>
                            <div class="form-text"><?= $isEdit ? 'Deja en blanco para mantener la contraseña actual. Mínimo 8 caracteres si la cambias.' : 'Mínimo 8 caracteres.' ?></div>
                        </div>
                        <div class="col-12">
                            <label for="role" class="form-label">Rol en la tienda <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <?php
                                $r = (string) ($old['role'] ?? 'cajero');
                                ?>
                                <option value="cajero" <?= $r === 'cajero' ? 'selected' : '' ?>>Cajero — ventas, caja y operación diaria</option>
                                <option value="admin" <?= $r === 'admin' ? 'selected' : '' ?>>Administrador — acceso completo y esta configuración</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></button>
                        <a href="<?= htmlspecialchars($basePath . '/admin/configuracion/usuarios', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = $pageTitle ?? ($isEdit ? 'Editar usuario' : 'Nuevo usuario');
require __DIR__ . '/../../layouts/admin.php';
?>
