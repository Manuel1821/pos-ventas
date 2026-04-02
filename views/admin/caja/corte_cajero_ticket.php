<?php
$pageTitle = $pageTitle ?? 'Ticket — Corte por cajero';
$session = $session ?? null;
$cut = $cut ?? null;
$countedCash = $countedCash ?? null;
$cashDifference = $cashDifference ?? null;
$shopName = $shopName ?? '';

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 no-print">
    <div>
        <h1 class="h5 mb-0"><i class="bi bi-receipt me-2" style="color:var(--teal);"></i> Corte por cajero</h1>
    </div>
    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Imprimir
    </button>
</div>

<?php if (!$session || !$cut): ?>
    <div class="alert alert-danger">No se pudo generar el ticket de corte.</div>
<?php else: ?>
    <?php
    $s = $cut;
    $st = $session;
    ?>
    <div id="cortePrintArea" class="border rounded-3 p-3 bg-white" style="max-width:420px;font-family:system-ui,Segoe UI,sans-serif;font-size:13px;">
        <div class="text-center mb-2">
            <div class="fw-bold"><?= htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="small text-muted">Corte por cajero</div>
        </div>
        <hr class="my-2">
        <div class="small">
            <div><strong>Cajero:</strong> <?= htmlspecialchars($s['cashier_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Sesión caja:</strong> #<?= (int) ($st['id'] ?? 0) ?> (<?= htmlspecialchars((string) ($st['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</div>
            <div><strong>Desde:</strong> <?= date('d/m/Y H:i', strtotime($st['opened_at'] ?? 'now')) ?></div>
            <div><strong>Impreso:</strong> <?= date('d/m/Y H:i') ?></div>
        </div>
        <hr class="my-2">
        <div class="fw-semibold mb-1">Ventas</div>
        <div class="d-flex justify-content-between small"><span>Tickets</span><span><?= (int) $s['sales_count'] ?></span></div>
        <div class="d-flex justify-content-between small"><span>Total ventas</span><span>$<?= number_format((float) $s['sales_total'], 2, '.', ',') ?></span></div>
        <hr class="my-2">
        <div class="fw-semibold mb-1">Por forma de pago</div>
        <?php foreach ($s['payments_by_method'] as $row): ?>
            <div class="d-flex justify-content-between small">
                <span><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <span>$<?= number_format((float) $row['amount'], 2, '.', ',') ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (empty($s['payments_by_method'])): ?>
            <div class="small text-muted">Sin cobros</div>
        <?php endif; ?>
        <hr class="my-2">
        <div class="fw-semibold mb-1">Ventas a crédito</div>
        <div class="d-flex justify-content-between small"><span>Tickets</span><span><?= (int) ($s['credit_sales_count'] ?? 0) ?></span></div>
        <div class="d-flex justify-content-between small"><span>Total importe</span><span>$<?= number_format((float) ($s['credit_sales_total'] ?? 0), 2, '.', ',') ?></span></div>
        <div class="d-flex justify-content-between small"><span>Saldo pendiente</span><span>$<?= number_format((float) ($s['credit_pending_total'] ?? 0), 2, '.', ',') ?></span></div>
        <?php if (!empty($s['credit_customers'])): ?>
            <div class="small fw-semibold mt-2 mb-1">Clientes</div>
            <?php foreach ($s['credit_customers'] as $cr): ?>
                <div class="small" style="border-bottom:1px dashed #e2e8f0;padding:4px 0;">
                    <div><?= htmlspecialchars($cr['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="d-flex justify-content-between text-muted">
                        <span><?= (int) ($cr['sales_count'] ?? 0) ?> venta(s)</span>
                        <span>Total $<?= number_format((float) ($cr['total_amount'] ?? 0), 2, '.', ',') ?></span>
                    </div>
                    <?php if (((float) ($cr['pending_amount'] ?? 0)) > 0.009): ?>
                        <div class="d-flex justify-content-between"><span>Pendiente</span><span>$<?= number_format((float) ($cr['pending_amount'] ?? 0), 2, '.', ',') ?></span></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <hr class="my-2">
        <div class="fw-semibold mb-1">Movimientos suyos (caja)</div>
        <div class="d-flex justify-content-between small"><span>Ingresos</span><span>+$<?= number_format((float) $s['manual_in'], 2, '.', ',') ?></span></div>
        <div class="d-flex justify-content-between small"><span>Retiros</span><span>−$<?= number_format((float) $s['manual_out'], 2, '.', ',') ?></span></div>
        <hr class="my-2">
        <div class="d-flex justify-content-between fw-bold"><span>Efectivo en ventas (POS)</span><span>$<?= number_format((float) $s['cash_from_pos_sales'], 2, '.', ',') ?></span></div>
        <div class="d-flex justify-content-between fw-bold text-primary"><span>Efectivo neto esperado</span><span>$<?= number_format((float) $s['expected_cash_hand'], 2, '.', ',') ?></span></div>
        <?php if ($countedCash !== null && is_finite((float) $countedCash)): ?>
            <hr class="my-2">
            <div class="d-flex justify-content-between small"><span>Efectivo contado</span><span>$<?= number_format((float) $countedCash, 2, '.', ',') ?></span></div>
            <?php if ($cashDifference !== null): ?>
                <div class="d-flex justify-content-between small <?= abs((float) $cashDifference) < 0.01 ? 'text-success' : 'text-danger' ?>">
                    <span>Diferencia</span>
                    <span><?= ((float) $cashDifference >= 0 ? '+' : '') ?>$<?= number_format((float) $cashDifference, 2, '.', ',') ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <hr class="my-2">
        <div class="small text-muted text-center">Conserve este comprobante para arqueo.</div>
    </div>
<?php endif; ?>

<style>
    @media print {
        .no-print { display: none !important; }
        body * { visibility: hidden; }
        #cortePrintArea, #cortePrintArea * { visibility: visible; }
        #cortePrintArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            max-width: 100%;
            border: none !important;
            padding: 0 !important;
        }
        @page { size: auto; margin: 4mm; }
    }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
