<?php
$errors = $errors ?? [];
$old = $old ?? [];
$products = $products ?? [];
$basePath = $basePath ?? '';
ob_start();
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= htmlspecialchars($basePath . '/admin/lotes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h4 mb-0"><i class="bi bi-tag me-2" style="color:var(--teal);"></i> Nuevo lote</h1>
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
        <form action="<?= htmlspecialchars($basePath . '/admin/lotes/guardar', ENT_QUOTES, 'UTF-8') ?>" method="post">
            <div class="mb-3">
                <label for="product_id" class="form-label">Producto <span class="text-danger">*</span></label>
                <select class="form-select" id="product_id" name="product_id" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) ($p['id'] ?? 0) ?>" <?= (int) ($old['product_id'] ?? 0) === (int) ($p['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($p['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($p['sku'])): ?>
                                (<?= htmlspecialchars((string) $p['sku'], ENT_QUOTES, 'UTF-8') ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="lot_code" class="form-label">Código de lote <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="lot_code" name="lot_code" value="<?= htmlspecialchars((string) ($old['lot_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required maxlength="64" autofocus>
                </div>
                <div class="col-md-6">
                    <label for="quantity" class="form-label">Cantidad en lote <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="quantity" name="quantity" value="<?= htmlspecialchars((string) ($old['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required inputmode="decimal" placeholder="0">
                </div>
            </div>
            <div class="mb-3 mt-3">
                <label for="expiry_date" class="form-label">Fecha de caducidad</label>
                <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?= htmlspecialchars((string) ($old['expiry_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-text">Opcional. Déjela vacía si el producto no aplica caducidad.</div>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notas</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" maxlength="2000"><?= htmlspecialchars((string) ($old['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/lotes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Nuevo lote';
require __DIR__ . '/../../layouts/admin.php';
