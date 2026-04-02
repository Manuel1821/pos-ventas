<?php
$pageTitle = $pageTitle ?? 'Corte por cajero';
$session = $session ?? null;
$cut = $cut ?? null;
$targetUserId = (int) ($targetUserId ?? 0);
$isAdmin = $isAdmin ?? false;
$sessionsList = $sessionsList ?? [];
$usersList = $usersList ?? [];
$basePath = $basePath ?? '';

$sessionIdVal = $session ? (int) ($session['id'] ?? 0) : 0;
$ticketQs = ['usuario' => $targetUserId];
if ($sessionIdVal > 0) {
    $ticketQs['sesion'] = $sessionIdVal;
}
$ticketBase = $basePath . '/admin/caja/corte-cajero/ticket?' . http_build_query($ticketQs);

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-person-badge me-2" style="color:var(--teal);"></i> Corte por cajero</h1>
        <p class="text-muted small mb-0">
            Resumen de ventas por forma de pago y efectivo esperado según el POS para la sesión de caja seleccionada.
            Imprima el ticket al terminar su turno o antes de cerrar sesión.
        </p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/caja', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Caja
    </a>
</div>

<?php if (!$session): ?>
    <div class="card border-0 card-shadow rounded-4">
        <div class="card-body p-4">
            <p class="text-muted mb-3">No hay sesión de caja abierta. Si necesita un corte histórico, un administrador puede elegir una sesión cerrada en los filtros.</p>
            <?php if ($isAdmin): ?>
                <form method="get" action="<?= htmlspecialchars($basePath . '/admin/caja/corte-cajero', ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Sesión de caja</label>
                        <select name="sesion" class="form-select">
                            <?php foreach ($sessionsList as $srow): ?>
                                <option value="<?= (int) ($srow['id'] ?? 0) ?>">
                                    #<?= (int) ($srow['id'] ?? 0) ?> — <?= date('d/m/Y H:i', strtotime($srow['opened_at'] ?? 'now')) ?>
                                    (<?= htmlspecialchars($srow['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Cajero</label>
                        <select name="usuario" class="form-select">
                            <?php foreach ($usersList as $u): ?>
                                <option value="<?= (int) ($u['id'] ?? 0) ?>" <?= (int) ($u['id'] ?? 0) === $targetUserId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Ver corte</button>
                    </div>
                </form>
            <?php else: ?>
                <a href="<?= htmlspecialchars($basePath . '/admin/caja/apertura', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Abrir caja</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <?php if ($isAdmin): ?>
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/caja/corte-cajero', ENT_QUOTES, 'UTF-8') ?>" class="card border-0 card-shadow rounded-4 mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Sesión de caja</label>
                        <select name="sesion" class="form-select">
                            <option value="<?= (int) ($session['id'] ?? 0) ?>" selected>
                                #<?= (int) ($session['id'] ?? 0) ?> — <?= date('d/m/Y H:i', strtotime($session['opened_at'] ?? 'now')) ?>
                                (<?= htmlspecialchars($session['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                            <?php foreach ($sessionsList as $srow): ?>
                                <?php if ((int) ($srow['id'] ?? 0) === (int) ($session['id'] ?? 0)) {
                                    continue;
                                } ?>
                                <option value="<?= (int) ($srow['id'] ?? 0) ?>">
                                    #<?= (int) ($srow['id'] ?? 0) ?> — <?= date('d/m/Y H:i', strtotime($srow['opened_at'] ?? 'now')) ?>
                                    (<?= htmlspecialchars($srow['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Cajero</label>
                        <select name="usuario" class="form-select">
                            <?php foreach ($usersList as $u): ?>
                                <option value="<?= (int) ($u['id'] ?? 0) ?>" <?= (int) ($u['id'] ?? 0) === $targetUserId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Aplicar</button>
                    </div>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="card border-0 card-shadow rounded-4 mb-4">
            <div class="card-body py-3">
                <span class="badge rounded-pill <?= ($session['status'] ?? '') === 'OPEN' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                    Sesión #<?= (int) ($session['id'] ?? 0) ?> — <?= htmlspecialchars($session['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="text-muted small ms-2">Desde <?= date('d/m/Y H:i', strtotime($session['opened_at'] ?? 'now')) ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$cut): ?>
        <div class="alert alert-warning">No se pudo generar el corte (cajero no válido para esta tienda).</div>
    <?php else: ?>
        <?php
        $s = $cut;
        ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 card-shadow rounded-4 h-100">
                    <div class="card-body p-3">
                        <div class="small text-muted">Tickets (ventas)</div>
                        <div class="fw-bold fs-5" style="color:var(--teal);"><?= (int) $s['sales_count'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 card-shadow rounded-4 h-100">
                    <div class="card-body p-3">
                        <div class="small text-muted">Total ventas</div>
                        <div class="fw-bold">$<?= number_format((float) $s['sales_total'], 2, '.', ',') ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 card-shadow rounded-4 h-100">
                    <div class="card-body p-3">
                        <div class="small text-muted">Efectivo en ventas (POS)</div>
                        <div class="fw-bold text-success">$<?= number_format((float) $s['cash_from_pos_sales'], 2, '.', ',') ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 card-shadow rounded-4 h-100 border-primary">
                    <div class="card-body p-3">
                        <div class="small text-muted">Efectivo neto esperado</div>
                        <div class="fw-bold fs-5 text-primary">$<?= number_format((float) $s['expected_cash_hand'], 2, '.', ',') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 card-shadow rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 py-3">
                <h2 class="h6 mb-0">Cobros por forma de pago (sus ventas)</h2>
                <p class="small text-muted mb-0 mt-1">Incluye efectivo, tarjetas, transferencia y cargos a cuenta según cómo registró cada cobro en el POS.</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Forma de pago</th>
                                <th class="text-end">Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($s['payments_by_method'])): ?>
                                <tr><td colspan="2" class="text-center text-muted py-4">Sin cobros en esta sesión.</td></tr>
                            <?php else: ?>
                                <?php foreach ($s['payments_by_method'] as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end fw-semibold">$<?= number_format((float) $row['amount'], 2, '.', ',') ?></td>
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
                <h2 class="h6 mb-0">Ventas a crédito</h2>
                <p class="small text-muted mb-0 mt-1">
                    Ventas con saldo pendiente o con cargo a <strong>cuenta crédito</strong> del cliente.
                </p>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="small text-muted">Total importe ventas a crédito</div>
                        <div class="fw-bold fs-5" style="color:#b45309;">$<?= number_format((float) ($s['credit_sales_total'] ?? 0), 2, '.', ',') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Tickets (ventas crédito)</div>
                        <div class="fw-semibold"><?= (int) ($s['credit_sales_count'] ?? 0) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Saldo pendiente total</div>
                        <div class="fw-bold text-warning">$<?= number_format((float) ($s['credit_pending_total'] ?? 0), 2, '.', ',') ?></div>
                    </div>
                </div>
                <h3 class="h6 mb-3">Clientes (ventas a crédito) — importe</h3>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th class="text-end">Ventas</th>
                                <th class="text-end">Total comprado</th>
                                <th class="text-end">Saldo pendiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($s['credit_customers'])): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">Sin ventas a crédito en esta sesión.</td></tr>
                            <?php else: ?>
                                <?php foreach ($s['credit_customers'] as $cr): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cr['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end"><?= (int) ($cr['sales_count'] ?? 0) ?></td>
                                        <td class="text-end fw-semibold">$<?= number_format((float) ($cr['total_amount'] ?? 0), 2, '.', ',') ?></td>
                                        <td class="text-end">$<?= number_format((float) ($cr['pending_amount'] ?? 0), 2, '.', ',') ?></td>
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
                <h2 class="h6 mb-0">Movimientos de caja registrados por usted</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Ingresos manuales</div>
                        <div class="fw-semibold text-success">+ $<?= number_format((float) $s['manual_in'], 2, '.', ',') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Retiros manuales</div>
                        <div class="fw-semibold text-warning">− $<?= number_format((float) $s['manual_out'], 2, '.', ',') ?></div>
                    </div>
                </div>
                <p class="small text-muted mb-0 mt-3">
                    El <strong>efectivo neto esperado</strong> suma el efectivo cobrado en sus ventas más sus ingresos y menos sus retiros.
                    Los reembolsos por cancelación o devolución los registra quien ejecuta la acción; no se incluyen aquí salvo que usted los haya registrado como retiro de caja.
                </p>
            </div>
        </div>

        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body">
                <label for="contadoEfectivo" class="form-label fw-semibold">Efectivo físico contado (opcional)</label>
                <div class="row g-2 align-items-end flex-wrap">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="contadoEfectivo" name="contado" placeholder="Ej. 1250.50" inputmode="decimal" autocomplete="off">
                    </div>
                    <div class="col-md-8">
                        <button type="button" class="btn btn-primary" id="btnImprimirCorte">
                            <i class="bi bi-printer me-2"></i> Imprimir ticket de corte
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function () {
            var base = <?= json_encode($ticketBase, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
            document.getElementById('btnImprimirCorte').addEventListener('click', function () {
                var c = document.getElementById('contadoEfectivo').value.trim();
                var url = base;
                if (c !== '') {
                    url += (base.indexOf('?') >= 0 ? '&' : '?') + 'contado=' + encodeURIComponent(c);
                }
                window.open(url, '_blank', 'noopener');
            });
        })();
        </script>
    <?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
