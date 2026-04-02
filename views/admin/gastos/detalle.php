<?php

use App\Validation\ExpenseValidator;

$expense = $expense ?? [];
$basePath = $basePath ?? '';
$eid = (int) ($expense['id'] ?? 0);
$st = (string) ($expense['status'] ?? 'ACTIVE');
ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= htmlspecialchars($basePath . '/admin/gastos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
        <h1 class="h4 mb-0"><i class="bi bi-receipt me-2" style="color:var(--teal);"></i> Detalle del gasto</h1>
    </div>
    <?php if ($st === 'ACTIVE'): ?>
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($basePath . '/admin/gastos/editar/' . $eid, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Editar</a>
            <form action="<?= htmlspecialchars($basePath . '/admin/gastos/anular/' . $eid, ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-inline" onsubmit="return confirm('¿Anular este gasto? Quedará marcado como anulado en el historial.');">
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i> Anular</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <span class="badge rounded-pill" style="background:rgba(20,184,166,.12);color:#0f766e;border:1px solid rgba(20,184,166,.25);">
                        <?= htmlspecialchars((string) ($expense['category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="badge rounded-pill <?= $st === 'ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $st === 'ACTIVE' ? 'Activo' : 'Anulado' ?>
                    </span>
                </div>
                <h2 class="h5 fw-semibold mb-3"><?= htmlspecialchars((string) ($expense['concept'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4 text-muted">Monto total</dt>
                    <dd class="col-sm-8 fw-semibold fs-5" style="color:var(--teal);">$<?= number_format((float) ($expense['total'] ?? 0), 2, '.', ',') ?></dd>
                    <dt class="col-sm-4 text-muted">Fecha</dt>
                    <dd class="col-sm-8"><?= !empty($expense['occurred_at']) ? date('d/m/Y H:i', strtotime((string) $expense['occurred_at'])) : '—' ?></dd>
                    <dt class="col-sm-4 text-muted">Método de pago</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars(ExpenseValidator::paymentMethodLabel((string) ($expense['payment_method'] ?? '')), ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4 text-muted">Proveedor</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string) ($expense['supplier_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4 text-muted">Referencia</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string) ($expense['reference'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4 text-muted">Registró</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars(trim((string) ($expense['creator_name'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4 text-muted">Alta en sistema</dt>
                    <dd class="col-sm-8"><?= !empty($expense['created_at']) ? date('d/m/Y H:i', strtotime((string) $expense['created_at'])) : '—' ?></dd>
                </dl>
                <?php if (!empty($expense['notes'])): ?>
                    <hr>
                    <div class="text-muted small text-uppercase mb-1">Observaciones</div>
                    <p class="mb-0"><?= nl2br(htmlspecialchars((string) $expense['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = $pageTitle ?? 'Detalle del gasto';
require __DIR__ . '/../../layouts/admin.php';
