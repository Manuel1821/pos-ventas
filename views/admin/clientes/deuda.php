<?php
$customer = $customer ?? [];
$debt = $debt ?? ['items' => [], 'total_debt' => 0.0];
$items = $debt['items'] ?? [];
$totalDebt = (float) ($debt['total_debt'] ?? 0);
$debtSettlements = $debtSettlements ?? [];
$pmLabels = [
    'EFECTIVO' => 'Efectivo',
    'TRANSFERENCIA' => 'Transferencia',
    'TARJETA_DEBITO' => 'Tarjeta de débito',
    'TARJETA_CREDITO' => 'Tarjeta de crédito',
    'OTRO' => 'Otro',
    'CUENTA_CREDITO' => 'Cuenta crédito',
];
$basePath = $basePath ?? '';
$flash = $flash ?? null;
$userName = $userName ?? '';
$shopName = $shopName ?? '';
$pageTitle = $pageTitle ?? 'Deuda del cliente';

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-cash-coin me-2" style="color:var(--teal);"></i> Deuda del cliente</h1>
        <p class="text-muted small mb-0">Detalle por nota, saldo total, historial de cobros y abonos.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($totalDebt > 0.009): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAbono">
                <i class="bi bi-cash-stack me-1"></i> Abonar
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalLiquidar">
                <i class="bi bi-check2-all me-1"></i> Liquidar deuda
            </button>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($basePath . '/admin/clientes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

<?php if (!empty($flash) && isset($flash['type'], $flash['message'])): ?>
    <div class="mb-3">
        <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show card-shadow" role="alert">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
            <div>
                <div class="text-muted small">Cliente</div>
                <div class="fw-semibold fs-5">
                    <?= htmlspecialchars((string) ($customer['name'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="text-end">
                <div class="text-muted small">Saldo pendiente total</div>
                <div class="fw-bold fs-4" style="color: #f59e0b;">
                    $<?= htmlspecialchars(number_format($totalDebt, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <h3 class="h6 mb-3"><i class="bi bi-journal-text me-2 text-secondary"></i> Detalle de la deuda (notas abiertas)</h3>
        <p class="small text-muted mb-3">
            Cada fila es una venta a crédito con saldo pendiente. El total de arriba es la suma de estas columnas «Saldo».
            Al usar <strong>Abonar</strong> o <strong>Liquidar</strong> se aplica al total; el desglose por nota se actualiza automáticamente.
        </p>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Saldo</th>
                        <th>Observaciones venta</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No hay ventas abiertas con saldo para este cliente.
                            <?php if ($totalDebt > 0.009): ?>
                                <span class="d-block small mt-1">Si acaba de registrar un abono, actualice la página.</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $row): ?>
                        <?php $saldo = (float) ($row['saldo'] ?? 0); ?>
                        <tr>
                            <td class="fw-semibold">#<?= (int) ($row['folio'] ?? 0) ?></td>
                            <td class="small"><?= !empty($row['occurred_at']) ? date('d/m/Y H:i', strtotime((string) $row['occurred_at'])) : '—' ?></td>
                            <td class="text-end">$<?= number_format((float) ($row['total'] ?? 0), 2, '.', ',') ?></td>
                            <td class="text-end">$<?= number_format((float) ($row['paid_total'] ?? 0), 2, '.', ',') ?></td>
                            <td class="text-end fw-semibold" style="color:#f59e0b;">$<?= number_format($saldo, 2, '.', ',') ?></td>
                            <td class="small text-muted"><?= !empty($row['notes']) ? htmlspecialchars((string) $row['notes'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-header bg-transparent border-0 py-3">
        <h2 class="h6 mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i> Abonos y liquidaciones</h2>
        <p class="small text-muted mb-0 mt-1">Una fila por cada cobro que registre (importe total de esa operación).</p>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th class="text-end">Importe</th>
                        <th>Forma de pago</th>
                        <th>Tipo</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($debtSettlements)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Aún no hay abonos ni liquidaciones registrados para este cliente.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($debtSettlements as $row): ?>
                        <?php
                            $st = (string) ($row['settlement_type'] ?? '');
                            $tipo = $st === 'LIQUIDACION' ? 'Liquidación' : 'Abono';
                            $obsTxt = trim((string) ($row['observaciones'] ?? ''));
                            $pm = (string) ($row['payment_method'] ?? '');
                            $pmLabel = $pmLabels[$pm] ?? $pm;
                        ?>
                        <tr>
                            <td class="small"><?= !empty($row['created_at']) ? date('d/m/Y H:i', strtotime((string) $row['created_at'])) : '—' ?></td>
                            <td class="text-end fw-semibold text-success">$<?= number_format((float) ($row['amount'] ?? 0), 2, '.', ',') ?></td>
                            <td class="small"><?= htmlspecialchars($pmLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge rounded-pill <?= $tipo === 'Liquidación' ? 'bg-danger' : 'bg-primary' ?>"><?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td class="small"><?= $obsTxt !== '' ? htmlspecialchars($obsTxt, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalDebt > 0.009): ?>
    <?php $cid = (int) ($customer['id'] ?? 0); ?>
    <div class="modal fade" id="modalAbono" tabindex="-1" aria-labelledby="modalAbonoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 card-shadow">
                <div class="modal-header border-0">
                    <h2 class="modal-title h5" id="modalAbonoLabel">Abonar a cuenta</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="post" action="<?= htmlspecialchars($basePath . '/admin/clientes/deuda/' . $cid . '/pago', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="abono">
                        <p class="small text-muted">Saldo pendiente: <strong>$<?= htmlspecialchars(number_format($totalDebt, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></strong></p>
                        <div class="mb-3">
                            <label for="abonoMonto" class="form-label fw-semibold">Importe a abonar</label>
                            <input type="number" class="form-control" name="monto" id="abonoMonto" required
                                   min="0.01" step="0.01" max="<?= htmlspecialchars((string) $totalDebt, ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="0.00" autocomplete="off">
                        </div>
                        <div class="mb-0">
                            <label for="abonoMetodo" class="form-label fw-semibold">Forma de pago</label>
                            <select class="form-select" name="payment_method" id="abonoMetodo" required>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="TARJETA_DEBITO">Tarjeta de débito</option>
                                <option value="TARJETA_CREDITO">Tarjeta de crédito</option>
                                <option value="OTRO">Otro</option>
                            </select>
                            <div class="form-text">En efectivo se requiere caja abierta; el ingreso queda en la sesión de caja actual.</div>
                        </div>
                        <div class="mb-0">
                            <label for="abonoObs" class="form-label fw-semibold">Observación <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea class="form-control" name="observaciones" id="abonoObs" rows="2" maxlength="500" placeholder="Ej. Recibió transferencia, comprobante #123"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar abono</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLiquidar" tabindex="-1" aria-labelledby="modalLiquidarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 card-shadow">
                <div class="modal-header border-0">
                    <h2 class="modal-title h5" id="modalLiquidarLabel">Liquidar deuda</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form method="post" action="<?= htmlspecialchars($basePath . '/admin/clientes/deuda/' . $cid . '/pago', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="liquidar">
                        <p class="mb-3">
                            Se cobrará el saldo total pendiente: <strong class="text-danger">$<?= htmlspecialchars(number_format($totalDebt, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></strong>.
                        </p>
                        <div class="mb-0">
                            <label for="liqMetodo" class="form-label fw-semibold">Forma de pago</label>
                            <select class="form-select" name="payment_method" id="liqMetodo" required>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="TARJETA_DEBITO">Tarjeta de débito</option>
                                <option value="TARJETA_CREDITO">Tarjeta de crédito</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                        <div class="mb-0 mt-3">
                            <label for="liqObs" class="form-label fw-semibold">Observación <span class="text-muted fw-normal">(opcional)</span></label>
                            <textarea class="form-control" name="observaciones" id="liqObs" rows="2" maxlength="500" placeholder="Nota interna sobre la liquidación"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Liquidar todo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>

