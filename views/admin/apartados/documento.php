<?php
$layaway = $layaway ?? [];
$items = $items ?? [];
$payments = $payments ?? [];
$shopRow = $shopRow ?? [];
$basePath = $basePath ?? '';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}
$folio = (int) ($layaway['folio'] ?? 0);
$st = (string) ($layaway['status'] ?? 'OPEN');
$pageTitle = $pageTitle ?? ('Apartado #' . $folio);
$customerName = trim((string) ($layaway['customer_name'] ?? ''));
$total = (float) ($layaway['total'] ?? 0);
$paid = (float) ($layaway['paid_total'] ?? 0);
$balance = max(0, $total - $paid);
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-archive me-2" style="color:var(--teal);"></i> Apartado #<?= $folio ?></h1>
        <p class="text-muted small mb-0">Control de pagos parciales y saldo pendiente.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= htmlspecialchars($basePath . '/admin/apartados', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Volver al listado</a>
        <a href="<?= htmlspecialchars($basePath . '/admin/apartados/ticket/' . (int) ($layaway['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" target="_blank" rel="noopener">
            <i class="bi bi-printer me-1"></i> Imprimir ticket
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Cliente</div>
                        <div class="fw-semibold"><?= htmlspecialchars($customerName !== '' ? $customerName : 'Sin cliente', ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (!empty($layaway['customer_phone'])): ?>
                            <div class="small text-muted"><?= htmlspecialchars((string) $layaway['customer_phone'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Estado</div>
                        <?php if ($st === 'OPEN'): ?>
                            <span class="badge text-bg-warning rounded-pill">Abierto</span>
                        <?php elseif ($st === 'PAID'): ?>
                            <span class="badge text-bg-success rounded-pill">Pagado</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary rounded-pill"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <div class="small mt-2 text-muted">Inicio: <?= !empty($layaway['starts_at']) ? date('d/m/Y', strtotime((string) $layaway['starts_at'])) : '—' ?></div>
                        <div class="small text-muted">Límite: <?= !empty($layaway['due_date']) ? date('d/m/Y', strtotime((string) $layaway['due_date'])) : 'Sin límite' ?></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Descripción</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">P. Unit.</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($it['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if (!empty($it['sku'])): ?>
                                            <div class="small text-muted">SKU: <?= htmlspecialchars((string) $it['sku'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= rtrim(rtrim(number_format((float) ($it['quantity'] ?? 0), 3, '.', ''), '0'), '.') ?></td>
                                    <td class="text-end">$<?= number_format((float) ($it['unit_price'] ?? 0), 2, '.', ',') ?></td>
                                    <td class="text-end"><?= number_format((float) ($it['tax_percent'] ?? 0), 2, '.', ',') ?>%</td>
                                    <td class="text-end fw-semibold">$<?= number_format((float) ($it['line_total'] ?? 0), 2, '.', ',') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                $showCustomerNote = !empty($layaway['note_to_customer'])
                    && $st === 'OPEN'
                    && $balance > 0.009;
                ?>
                <?php if ($showCustomerNote): ?>
                    <div class="alert alert-light border mt-3 mb-0">
                        <div class="small text-muted mb-1">Nota</div>
                        <?= nl2br(htmlspecialchars((string) $layaway['note_to_customer'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card border-0 card-shadow rounded-4 mb-3">
            <div class="card-body p-4">
                <h2 class="h6 mb-3">Resumen</h2>
                <div class="d-flex justify-content-between small mb-2"><span>Total</span><span class="fw-semibold">$<?= number_format($total, 2, '.', ',') ?></span></div>
                <div class="d-flex justify-content-between small mb-2"><span>Pagado</span><span class="fw-semibold">$<?= number_format($paid, 2, '.', ',') ?></span></div>
                <div class="d-flex justify-content-between small"><span>Saldo</span><span class="fw-semibold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>"><?= $balance > 0 ? '$' . number_format($balance, 2, '.', ',') : 'Liquidado' ?></span></div>
            </div>
        </div>

        <div class="card border-0 card-shadow rounded-4 mb-3">
            <div class="card-body p-4">
                <h2 class="h6 mb-3">Registrar abono</h2>
                <form method="post" action="<?= htmlspecialchars($basePath . '/admin/apartados/registrar-abono/' . (int) ($layaway['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-2">
                        <label class="form-label small">Monto</label>
                        <input type="number" min="0.01" step="0.01" name="amount" class="form-control" required <?= $st !== 'OPEN' ? 'disabled' : '' ?>>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Forma de pago</label>
                        <select name="payment_method" class="form-select" <?= $st !== 'OPEN' ? 'disabled' : '' ?>>
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                            <option value="TARJETA_DEBITO">Tarjeta débito</option>
                            <option value="TARJETA_CREDITO">Tarjeta crédito</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Referencia</label>
                        <input type="text" maxlength="120" name="reference" class="form-control" <?= $st !== 'OPEN' ? 'disabled' : '' ?>>
                    </div>
                    <button class="btn btn-primary w-100" type="submit" <?= $st !== 'OPEN' ? 'disabled' : '' ?>>Registrar abono</button>
                </form>
                <?php if ($st === 'OPEN' && $balance > 0.009): ?>
                    <form class="mt-3 pt-3 border-top" method="post" action="<?= htmlspecialchars($basePath . '/admin/apartados/registrar-abono/' . (int) ($layaway['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('¿Liquidar el saldo pendiente ($<?= number_format($balance, 2, '.', ',') ?>) y marcar este apartado como pagado?');">
                        <input type="hidden" name="amount" value="<?= htmlspecialchars(number_format($balance, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mb-2">
                            <label class="form-label small">Forma de pago (liquidación)</label>
                            <select name="payment_method" class="form-select">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="TARJETA_DEBITO">Tarjeta débito</option>
                                <option value="TARJETA_CREDITO">Tarjeta crédito</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Referencia</label>
                            <input type="text" maxlength="120" name="reference" class="form-control" placeholder="Opcional (ej. retiro en tienda)">
                        </div>
                        <button class="btn btn-success w-100" type="submit">
                            <i class="bi bi-check-circle me-1"></i> Liquidar apartado
                        </button>
                        <div class="small text-muted mt-2 mb-0">Registra un abono por el saldo total ($<?= number_format($balance, 2, '.', ',') ?>) de una vez.</div>
                    </form>
                <?php endif; ?>
                <?php if ($st === 'OPEN'): ?>
                    <form class="mt-2" method="post" action="<?= htmlspecialchars($basePath . '/admin/apartados/cancelar/' . (int) ($layaway['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('¿Cancelar este apartado?');">
                        <button class="btn btn-outline-danger w-100" type="submit">Cancelar apartado</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-body p-4">
        <h2 class="h6 mb-3">Historial de abonos</h2>
        <?php if (empty($payments)): ?>
            <div class="text-muted small">Sin abonos registrados.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Método</th>
                            <th>Referencia</th>
                            <th>Registró</th>
                            <th class="text-end">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <?php $ts = !empty($p['created_at']) ? strtotime((string) $p['created_at']) : false; ?>
                            <tr>
                                <td class="small"><?= $ts ? date('d/m/Y H:i', $ts) : '—' ?></td>
                                <td class="small"><?= htmlspecialchars((string) ($p['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars((string) ($p['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars((string) ($p['created_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-semibold">$<?= number_format((float) ($p['amount'] ?? 0), 2, '.', ',') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';

