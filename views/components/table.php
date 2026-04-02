<?php
// Componente: tabla bootstrap.
// Variables esperadas:
// - $headers: string[] (encabezados)
// - $rows: array<int, array<int|string>> (filas)
?>
<?php
$headers = $headers ?? [];
$rows = $rows ?? [];
?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <?php if (!empty($headers)): ?>
            <thead>
            <tr>
                <?php foreach ($headers as $h): ?>
                    <th scope="col"><?= htmlspecialchars((string) $h, ENT_QUOTES, 'UTF-8') ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
        <?php endif; ?>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <td><?= is_string($cell) ? htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

