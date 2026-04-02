<?php
$detail = $detail ?? null;
$sale = $detail['sale'] ?? [];
$items = $detail['items'] ?? [];
$payments = $detail['payments'] ?? [];
$cancellation = $cancellation ?? null;
$returns = $returns ?? [];
$basePath = $basePath ?? '';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}
$pageTitle = $pageTitle ?? 'Detalle de venta';

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-receipt me-2" style="color:var(--teal);"></i> Detalle de venta</h1>
        <p class="text-muted small mb-0">Revision completa de la operacion y pagos asociados.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= htmlspecialchars($basePath . '/admin/ventas', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
        <a href="<?= htmlspecialchars($basePath . '/admin/ventas/ticket/' . (int) ($sale['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" target="_blank">
            <i class="bi bi-printer me-1"></i> Reimprimir
        </a>
    </div>
</div>

<?php if (!$detail): ?>
    <div class="alert alert-danger">No se encontro la venta solicitada.</div>
<?php else: ?>
    <?php
    $status = (string) ($sale['status'] ?? 'PAID');
    $canCancel = $status === 'PAID' && !$cancellation;
    $canReturn = in_array($status, ['PAID', 'REFUNDED'], true) && !$cancellation;
    ?>
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h2 class="h6 mb-0">Informacion general</h2>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="small text-muted">Folio</div>
                            <div class="fw-semibold">#<?= (int) ($sale['folio'] ?? 0) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Fecha</div>
                            <div class="fw-semibold"><?= !empty($sale['occurred_at']) ? date('d/m/Y H:i', strtotime((string) $sale['occurred_at'])) : '—' ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Estado</div>
                            <div class="fw-semibold">
                                <?php if ($status === 'PAID'): ?>
                                    <span class="badge bg-success rounded-pill">Pagada</span>
                                <?php elseif ($status === 'CANCELLED'): ?>
                                    <span class="badge bg-danger rounded-pill">Cancelada</span>
                                <?php elseif ($status === 'REFUNDED'): ?>
                                    <span class="badge bg-info text-dark rounded-pill">Con devoluciones</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Caja</div>
                            <div class="fw-semibold"><?= (int) ($sale['cash_session_id'] ?? 0) > 0 ? ('#' . (int) $sale['cash_session_id']) : '—' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Cliente</div>
                            <div class="fw-semibold"><?= htmlspecialchars((string) ($sale['customer_name'] ?? 'Cliente general'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted">
                                <?= htmlspecialchars((string) (($sale['customer_phone'] ?? '') !== '' ? $sale['customer_phone'] : ($sale['customer_email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Vendedor</div>
                            <div class="fw-semibold"><?= htmlspecialchars(trim((string) ($sale['seller_name'] ?? 'Usuario')), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php if (!empty($sale['notes'])): ?>
                            <div class="col-12">
                                <div class="small text-muted">Observaciones</div>
                                <div class="fw-semibold"><?= htmlspecialchars((string) $sale['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h2 class="h6 mb-0">Totales</h2>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted small">Subtotal</span><b>$<?= number_format((float) ($sale['subtotal'] ?? 0), 2, '.', ',') ?></b></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted small">Descuento</span><b class="text-danger">-$<?= number_format((float) ($sale['discount_total'] ?? 0), 2, '.', ',') ?></b></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted small">Impuesto</span><b>$<?= number_format((float) ($sale['tax_total'] ?? 0), 2, '.', ',') ?></b></div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-2"><span class="fw-semibold">Total</span><span class="fw-bold fs-5 text-primary">$<?= number_format((float) ($sale['total'] ?? 0), 2, '.', ',') ?></span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted small">Pagado</span><b>$<?= number_format((float) ($sale['paid_total'] ?? 0), 2, '.', ',') ?></b></div>
                    <div class="d-flex justify-content-between"><span class="text-muted small">Saldo</span><b class="<?= ((float) ($sale['total'] ?? 0) - (float) ($sale['paid_total'] ?? 0)) > 0.009 ? 'text-danger' : 'text-success' ?>">
                        $<?= number_format(max(0, (float) ($sale['total'] ?? 0) - (float) ($sale['paid_total'] ?? 0)), 2, '.', ',') ?>
                    </b></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-6">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h2 class="h6 mb-0"><i class="bi bi-x-octagon me-1 text-danger"></i> Cancelar venta</h2>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if ($cancellation): ?>
                        <div class="alert alert-danger mb-0">
                            <div class="fw-semibold mb-1">Venta cancelada</div>
                            <div class="small"><b>Motivo:</b> <?= htmlspecialchars((string) ($cancellation['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small"><b>Reembolso:</b> $<?= number_format((float) ($cancellation['refund_amount'] ?? 0), 2, '.', ',') ?></div>
                            <div class="small"><b>Usuario:</b> <?= htmlspecialchars((string) ($cancellation['created_by_name'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small"><b>Fecha:</b> <?= !empty($cancellation['created_at']) ? date('d/m/Y H:i', strtotime((string) $cancellation['created_at'])) : '—' ?></div>
                            <?php if (!empty($cancellation['notes'])): ?>
                                <div class="small mt-1"><b>Observaciones:</b> <?= htmlspecialchars((string) $cancellation['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!$canCancel): ?>
                        <div class="alert alert-secondary mb-0">Esta venta ya no puede cancelarse por su estado actual.</div>
                    <?php else: ?>
                        <form method="post" action="<?= htmlspecialchars($basePath . '/admin/ventas/cancelar/' . (int) ($sale['id'] ?? 0), ENT_QUOTES, 'UTF-8')">
                            <div class="mb-3">
                                <label class="form-label small text-muted">Motivo de cancelación *</label>
                                <input type="text" name="reason" class="form-control" maxlength="180" required placeholder="Ej. Cobro duplicado o error de captura">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Observaciones</label>
                                <textarea name="notes" class="form-control" rows="3" maxlength="255" placeholder="Detalle adicional para auditoría"></textarea>
                            </div>
                            <div class="small text-muted mb-3">
                                Esta operación regresa stock, registra movimiento de caja (salida) y conserva historial.
                            </div>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Esta acción cancelará la venta y aplicará reembolso. ¿Deseas continuar?');">
                                <i class="bi bi-x-circle me-1"></i> Confirmar cancelación
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-header bg-transparent border-0 py-3 px-4">
                    <h2 class="h6 mb-0"><i class="bi bi-arrow-counterclockwise me-1 text-info"></i> Registrar devolución</h2>
                </div>
                <div class="card-body px-4 pb-4">
                    <?php if (!$canReturn): ?>
                        <div class="alert alert-secondary mb-0">Esta venta no permite devoluciones.</div>
                    <?php else: ?>
                        <form method="post" action="<?= htmlspecialchars($basePath . '/admin/ventas/devolver/' . (int) ($sale['id'] ?? 0), ENT_QUOTES, 'UTF-8')">
                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-end">Vendida</th>
                                        <th class="text-end">Devuelta</th>
                                        <th class="text-end">Disponible</th>
                                        <th class="text-end" style="width: 160px;">A devolver</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($items as $it): ?>
                                        <?php
                                        $soldQty = (float) ($it['quantity'] ?? 0);
                                        $returnedQty = (float) ($it['returned_quantity'] ?? 0);
                                        $remainingQty = max(0.0, $soldQty - $returnedQty);
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($it['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end"><?= number_format($soldQty, 3, '.', ',') ?></td>
                                            <td class="text-end text-info"><?= number_format($returnedQty, 3, '.', ',') ?></td>
                                            <td class="text-end fw-semibold"><?= number_format($remainingQty, 3, '.', ',') ?></td>
                                            <td class="text-end">
                                                <input
                                                    type="number"
                                                    class="form-control form-control-sm text-end"
                                                    name="return_qty[<?= (int) ($it['id'] ?? 0) ?>]"
                                                    min="0"
                                                    max="<?= htmlspecialchars(number_format($remainingQty, 3, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    step="0.001"
                                                    value="0"
                                                >
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Motivo de devolución *</label>
                                <input type="text" name="reason" class="form-control" maxlength="180" required placeholder="Ej. Producto en mal estado">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Observaciones</label>
                                <textarea name="notes" class="form-control" rows="3" maxlength="255" placeholder="Detalle adicional para auditoría"></textarea>
                            </div>
                            <button type="submit" class="btn btn-info text-dark" onclick="return confirm('Se registrará una devolución parcial o total. ¿Deseas continuar?');">
                                <i class="bi bi-arrow-return-left me-1"></i> Confirmar devolución
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 card-shadow rounded-4 mb-3">
        <div class="card-header bg-transparent border-0 py-3 px-4">
            <h2 class="h6 mb-0">Productos vendidos</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th class="text-end">Cant.</th>
                        <th class="text-end">P. unit.</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Desc.</th>
                        <th class="text-end">Imp.</th>
                        <th class="text-end">Importe</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Sin partidas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $it): ?>
                            <?php
                            $fallbackSubtotal = (float) ($it['line_total'] ?? 0);
                            $lineSubtotal = isset($it['line_subtotal']) ? (float) $it['line_subtotal'] : $fallbackSubtotal;
                            $lineDiscount = isset($it['discount_amount']) ? (float) $it['discount_amount'] : 0.0;
                            $lineTax = isset($it['tax_amount']) ? (float) $it['tax_amount'] : 0.0;
                            $lineTotal = $lineSubtotal + $lineTax;
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($it['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars((string) ($it['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <?= !empty($it['barcode']) ? (' | ' . htmlspecialchars((string) $it['barcode'], ENT_QUOTES, 'UTF-8')) : '' ?>
                                    </div>
                                </td>
                                <td class="text-end"><?= number_format((float) ($it['quantity'] ?? 0), 3, '.', ',') ?></td>
                                <td class="text-end">$<?= number_format((float) ($it['unit_price'] ?? 0), 2, '.', ',') ?></td>
                                <td class="text-end">$<?= number_format($lineSubtotal, 2, '.', ',') ?></td>
                                <td class="text-end text-danger">-$<?= number_format($lineDiscount, 2, '.', ',') ?></td>
                                <td class="text-end">$<?= number_format($lineTax, 2, '.', ',') ?></td>
                                <td class="text-end fw-semibold">$<?= number_format($lineTotal, 2, '.', ',') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 card-shadow rounded-4">
        <div class="card-header bg-transparent border-0 py-3 px-4">
            <h2 class="h6 mb-0">Pagos asociados</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Metodo</th>
                        <th class="text-end">Monto</th>
                        <th>Fecha</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Sin pagos.</td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td>#<?= (int) ($p['id'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string) ($p['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-semibold">$<?= number_format((float) ($p['amount'] ?? 0), 2, '.', ',') ?></td>
                                <td class="small"><?= !empty($p['created_at']) ? date('d/m/Y H:i', strtotime((string) $p['created_at'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 card-shadow rounded-4 mt-3">
        <div class="card-header bg-transparent border-0 py-3 px-4">
            <h2 class="h6 mb-0">Historial de devoluciones</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Motivo</th>
                        <th class="text-end">Reembolso</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($returns)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Sin devoluciones registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($returns as $ret): ?>
                            <tr>
                                <td>#<?= (int) ($ret['id'] ?? 0) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars((string) ($ret['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (!empty($ret['notes'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars((string) $ret['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold text-danger">-$<?= number_format((float) ($ret['refund_amount'] ?? 0), 2, '.', ',') ?></td>
                                <td><?= htmlspecialchars((string) ($ret['created_by_name'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= !empty($ret['created_at']) ? date('d/m/Y H:i', strtotime((string) $ret['created_at'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';

