<?php
$detail = $detail ?? null;
$basePath = $basePath ?? '';
$pageTitle = $pageTitle ?? 'Detalle de sesión';
if (!$detail) {
    header('Location: ' . $basePath . '/admin/caja/historial');
    exit;
}
$session = $detail['session'];
$movements = $detail['movements'] ?? [];
$initial = (float) ($detail['initial_amount'] ?? 0);
$totalIns = (float) ($detail['total_ins'] ?? 0);
$totalOuts = (float) ($detail['total_outs'] ?? 0);
$salesPaid = (float) ($detail['sales_paid'] ?? 0);
$expected = (float) ($detail['expected_amount'] ?? 0);
$status = $session['status'] ?? 'CLOSED';
$diff = isset($session['difference']) ? (float) $session['difference'] : null;
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-receipt me-2" style="color:var(--teal);"></i> Detalle de sesión #<?= (int)($session['id'] ?? 0) ?></h1>
        <p class="text-muted small mb-0">Apertura: <?= date('d/m/Y H:i', strtotime($session['opened_at'] ?? 'now')) ?> — Estado: <?= $status === 'OPEN' ? 'Abierta' : 'Cerrada' ?></p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/caja/historial', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al historial
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-3">
                <div class="small text-muted">Monto inicial</div>
                <div class="fw-bold">$<?= number_format($initial, 2, '.', ',') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-3">
                <div class="small text-muted">Ingresos</div>
                <div class="fw-bold text-success">$<?= number_format($totalIns, 2, '.', ',') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-3">
                <div class="small text-muted">Retiros</div>
                <div class="fw-bold text-warning">$<?= number_format($totalOuts, 2, '.', ',') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-3">
                <div class="small text-muted">Ventas cobradas</div>
                <div class="fw-bold text-primary">$<?= number_format($salesPaid, 2, '.', ',') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-3">
                <div class="small text-muted">Total esperado</div>
                <div class="fw-bold">$<?= number_format($expected, 2, '.', ',') ?></div>
            </div>
        </div>
    </div>
    <?php if ($diff !== null): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted">Diferencia</div>
                    <div class="fw-bold <?= $diff >= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($diff, 2, '.', ',') ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($session['observations'])): ?>
    <div class="card border-0 card-shadow rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="small text-muted">Observaciones del cierre</div>
            <div><?= htmlspecialchars($session['observations'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-header bg-transparent border-0 py-3">
        <h2 class="h6 mb-0"><i class="bi bi-list-ul me-2"></i> Movimientos</h2>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Fecha / Hora</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Monto</th>
                        <th scope="col">Motivo</th>
                        <th scope="col">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movements)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay movimientos en esta sesión.</td>
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
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
