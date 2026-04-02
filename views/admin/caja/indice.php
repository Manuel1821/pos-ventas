<?php
$summary = $summary ?? null;
$movements = $movements ?? [];
$basePath = $basePath ?? '';
$pageTitle = $pageTitle ?? 'Caja';
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-cash-coin me-2" style="color:var(--teal);"></i> Caja</h1>
        <p class="text-muted small mb-0">Estado actual de la caja, movimientos y acciones rápidas.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!$summary): ?>
            <a href="<?= htmlspecialchars($basePath . '/admin/caja/apertura', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-down-right"></i> Abrir caja
            </a>
        <?php else: ?>
            <a href="<?= htmlspecialchars($basePath . '/admin/caja/ingreso', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ingreso
            </a>
            <a href="<?= htmlspecialchars($basePath . '/admin/caja/retiro', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning">
                <i class="bi bi-dash-circle"></i> Retiro
            </a>
            <a href="<?= htmlspecialchars($basePath . '/admin/caja/cierre', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-danger">
                <i class="bi bi-x-circle"></i> Cerrar caja
            </a>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($basePath . '/admin/caja/corte-cajero', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary">
            <i class="bi bi-person-badge"></i> Corte cajero
        </a>
        <a href="<?= htmlspecialchars($basePath . '/admin/caja/historial', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history"></i> Historial
        </a>
    </div>
</div>

<?php if (!$summary): ?>
    <div class="card border-0 card-shadow rounded-4">
        <div class="card-body p-5 text-center">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px;height:80px;background:rgba(20,184,166,.12);border:2px solid rgba(20,184,166,.3);color:var(--teal);">
                <i class="bi bi-cash-stack fs-2"></i>
            </div>
            <h2 class="h5 mb-2">No hay caja abierta</h2>
            <p class="text-muted mb-4">Para operar ventas y registrar movimientos, abra una sesión de caja con el monto inicial.</p>
            <a href="<?= htmlspecialchars($basePath . '/admin/caja/apertura', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-down-right"></i> Abrir caja
            </a>
        </div>
    </div>
<?php else:
    $s = $summary;
    $session = $s['session'];
    $initial = (float) $s['initial_amount'];
    $ins = (float) $s['total_ins'];
    $outs = (float) $s['total_outs'];
    $sales = (float) $s['sales_paid'];
    $expected = (float) $s['expected_amount'];
?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted">Monto inicial</div>
                    <div class="fw-bold fs-5" style="color:var(--teal);">$<?= number_format($initial, 2, '.', ',') ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted">Ingresos manuales</div>
                    <div class="fw-bold text-success">$<?= number_format($ins, 2, '.', ',') ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted">Retiros</div>
                    <div class="fw-bold text-warning">$<?= number_format($outs, 2, '.', ',') ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted">Ventas cobradas</div>
                    <div class="fw-bold text-primary">$<?= number_format($sales, 2, '.', ',') ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 card-shadow rounded-4 h-100 border-primary">
                <div class="card-body p-3">
                    <div class="small text-muted">Total esperado</div>
                    <div class="fw-bold fs-5 text-primary">$<?= number_format($expected, 2, '.', ',') ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4 col-lg-2">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted">Abierta desde</div>
                    <div class="small fw-semibold"><?= date('d/m/Y H:i', strtotime($session['opened_at'] ?? 'now')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 card-shadow rounded-4">
        <div class="card-header bg-transparent border-0 py-3">
            <h2 class="h6 mb-0"><i class="bi bi-list-ul me-2"></i> Movimientos de la sesión</h2>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Fecha / Hora</th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Monto</th>
                            <th scope="col">Motivo / Observaciones</th>
                            <th scope="col">Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movements)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aún no hay movimientos en esta sesión.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movements as $m): ?>
                                <tr>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime($m['occurred_at'] ?? 'now')) ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= ($m['type'] ?? '') === 'IN' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= ($m['type'] ?? '') === 'IN' ? 'Ingreso' : 'Retiro' ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold <?= ($m['type'] ?? '') === 'IN' ? 'text-success' : 'text-warning' ?>">
                                        <?= ($m['type'] ?? '') === 'IN' ? '+' : '-' ?> $<?= number_format((float)($m['amount'] ?? 0), 2, '.', ',') ?>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($m['note'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($m['creator_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
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
