<?php
// Componente: botón/link bootstrap.
// Variables esperadas:
// - $label (string)
// - $variant (opcional, default 'primary')
// - $href (opcional, si existe se renderiza como <a>)
// - $type (opcional, default 'button')
?>
<?php
$variant = (string) ($variant ?? 'primary');
$label = (string) ($label ?? '');
$href = $href ?? null;
$type = (string) ($type ?? 'button');
?>

<?php if (!empty($href) && is_string($href)): ?>
    <a class="btn btn-<?= htmlspecialchars($variant, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
    </a>
<?php else: ?>
    <button type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-<?= htmlspecialchars($variant, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
    </button>
<?php endif; ?>

