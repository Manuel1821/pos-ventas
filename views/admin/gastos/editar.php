<?php

use App\Validation\ExpenseValidator;

$errors = $errors ?? [];
$old = $old ?? [];
$expense = $expense ?? [];
$categories = $categories ?? [];
$paymentMethods = $paymentMethods ?? ExpenseValidator::PAYMENT_METHODS;
$basePath = $basePath ?? '';
$expenseId = (int) ($expense['id'] ?? $old['id'] ?? 0);
ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= htmlspecialchars($basePath . '/admin/gastos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <h1 class="h4 mb-0"><i class="bi bi-pencil me-2" style="color:var(--teal);"></i> Editar gasto</h1>
    </div>
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

        <?php if (empty($categories)): ?>
            <div class="alert alert-warning">No hay categorías activas. <a href="<?= htmlspecialchars($basePath . '/admin/gastos/categorias/crear', ENT_QUOTES, 'UTF-8') ?>">Crear categoría</a></div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($basePath . '/admin/gastos/actualizar/' . $expenseId, ENT_QUOTES, 'UTF-8') ?>" method="post" class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label">Categoría <span class="text-danger">*</span></label>
                <select name="expense_category_id" class="form-select" required <?= empty($categories) ? 'disabled' : '' ?>>
                    <option value="">Seleccionar…</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) ($c['id'] ?? 0) ?>" <?= (int) ($old['expense_category_id'] ?? 0) === (int) ($c['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Método de pago <span class="text-danger">*</span></label>
                <select name="payment_method" class="form-select" required>
                    <?php foreach ($paymentMethods as $pm): ?>
                        <option value="<?= htmlspecialchars($pm, ENT_QUOTES, 'UTF-8') ?>" <?= ($old['payment_method'] ?? '') === $pm ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ExpenseValidator::paymentMethodLabel($pm), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Descripción <span class="text-danger">*</span></label>
                <input type="text" name="concept" class="form-control" required maxlength="180" value="<?= htmlspecialchars((string) ($old['concept'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Monto <span class="text-danger">*</span></label>
                <input type="number" name="amount" class="form-control" required min="0.01" step="0.01" value="<?= htmlspecialchars((string) ($old['amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Fecha y hora <span class="text-danger">*</span></label>
                <input type="datetime-local" name="occurred_at" class="form-control" required value="<?= htmlspecialchars((string) ($old['occurred_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Proveedor</label>
                <input type="text" name="supplier_name" class="form-control" maxlength="160" value="<?= htmlspecialchars((string) ($old['supplier_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Referencia</label>
                <input type="text" name="reference" class="form-control" maxlength="120" value="<?= htmlspecialchars((string) ($old['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label">Observaciones</label>
                <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars((string) ($old['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary" <?= empty($categories) ? 'disabled' : '' ?>>Guardar cambios</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/gastos/detalle/' . $expenseId, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Ver detalle</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = $pageTitle ?? 'Editar gasto';
require __DIR__ . '/../../layouts/admin.php';
