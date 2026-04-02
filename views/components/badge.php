<?php
// Componente: badge bootstrap.
// Variables esperadas:
// - $text (string)
// - $variant (opcional, default 'secondary')
?>
<?php $variant = (string) ($variant ?? 'secondary'); ?>
<span class="badge rounded-pill text-bg-<?= htmlspecialchars($variant, ENT_QUOTES, 'UTF-8') ?>">
    <?= htmlspecialchars((string) ($text ?? ''), ENT_QUOTES, 'UTF-8') ?>
</span>

