<?php
$users = $users ?? [];
$currentUserId = isset($currentUserId) ? (int) $currentUserId : 0;
$basePath = $basePath ?? '';

function role_label_usuarios(?string $names): string
{
    if ($names === null || $names === '') {
        return '—';
    }
    $parts = array_map('trim', explode(',', $names));
    $map = ['admin' => 'Administrador', 'cajero' => 'Cajero', 'vendedor' => 'Vendedor'];
    $out = [];
    foreach ($parts as $p) {
        $out[] = $map[$p] ?? $p;
    }
    return implode(', ', $out);
}

ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-person-badge me-2" style="color:var(--teal);"></i> Usuarios de la tienda</h1>
        <p class="text-muted small mb-0">Alta de cuentas para cajeros y administradores. Solo administradores ven esta pantalla.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= htmlspecialchars($basePath . '/admin/configuracion/tienda', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear"></i> Datos de la tienda
        </a>
        <a href="<?= htmlspecialchars($basePath . '/admin/configuracion/usuarios/crear', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo usuario
        </a>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">No hay usuarios registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <?php
                            $uid = (int) ($u['id'] ?? 0);
                            $st = (string) ($u['status'] ?? 'ACTIVE');
                            $isSelf = $uid === $currentUserId;
                            ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isSelf): ?>
                                        <span class="badge bg-secondary ms-1">Tú</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string) ($u['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(role_label_usuarios($u['role_names'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if ($st === 'ACTIVE'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($basePath . '/admin/configuracion/usuarios/editar/' . $uid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <?php if (!$isSelf): ?>
                                        <form method="post" action="<?= htmlspecialchars($basePath . '/admin/configuracion/usuarios/cambiar-estado/' . $uid, ENT_QUOTES, 'UTF-8') ?>" class="d-inline" onsubmit="return confirm('¿Cambiar el estado de este usuario?');">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <?= $st === 'ACTIVE' ? 'Desactivar' : 'Activar' ?>
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
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = $pageTitle ?? 'Usuarios';
require __DIR__ . '/../../layouts/admin.php';
?>
