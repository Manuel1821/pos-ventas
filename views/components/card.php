<?php
// Componente: tarjeta bootstrap.
// Variables esperadas:
// - $title (opcional)
// - $body (HTML string, opcional)
// - $icon (opcional, por ejemplo 'bi bi-box')
?>
<?php $title = $title ?? null; ?>
<div class="card border-0 card-shadow rounded-4 h-100">
    <div class="card-body p-4">
        <?php if (!empty($icon ?? '')): ?>
            <div class="mb-2" style="color:var(--teal);">
                <i class="<?= htmlspecialchars((string) $icon, ENT_QUOTES, 'UTF-8') ?>"></i>
            </div>
        <?php endif; ?>
        <?php if (!empty($title)): ?>
            <h3 class="h6 fw-semibold"><?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?></h3>
        <?php endif; ?>
        <?= $body ?? '' ?>
    </div>
</div>

