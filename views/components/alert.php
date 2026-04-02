<?php
// Componente: alerta bootstrap.
// Se espera $type (success|danger|warning|info|primary) y $message.
?>
<?php if (!empty($message ?? '')): ?>
    <div class="alert alert-<?= htmlspecialchars((string) ($type ?? 'info'), ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

