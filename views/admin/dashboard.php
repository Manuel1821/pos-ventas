<?php
$basePath = $basePath ?? '';
$todaySales = $todaySales ?? ['total' => 0, 'count' => 0];
$yesterdaySales = $yesterdaySales ?? ['total' => 0, 'count' => 0];
$monthSales = $monthSales ?? ['total' => 0, 'count' => 0];
$recentSales = $recentSales ?? [];
$catalog = $catalog ?? ['products' => 0, 'customers' => 0];
$cashOpen = $cashOpen ?? false;
$cashSummary = $cashSummary ?? null;
$deltaVsYesterday = $deltaVsYesterday ?? null;
$todayDateLabel = $todayDateLabel ?? date('d/m/Y');
$isAdmin = $isAdmin ?? false;

$fmtMoney = static function (float $n): string {
    return '$' . number_format($n, 2, '.', ',');
};

$saleStatusLabel = static function (string $st): string {
    return match (strtoupper($st)) {
        'PAID' => 'Pagada',
        'OPEN' => 'Crédito / pendiente',
        'CANCELLED' => 'Cancelada',
        'REFUNDED' => 'Devuelta',
        default => $st,
    };
};

ob_start();
?>
<div class="mb-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h1 class="h4 mb-1">Panel de control</h1>
            <p class="text-muted small mb-0">
                Resumen de tu punto de venta · <span class="text-dark"><?= htmlspecialchars($todayDateLabel, ENT_QUOTES, 'UTF-8') ?></span>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars($basePath . '/admin/pos/nueva-venta', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm rounded-3">
                <i class="bi bi-basket me-1"></i> Nueva venta
            </a>
            <a href="<?= htmlspecialchars($basePath . '/admin/caja', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="bi bi-cash-coin me-1"></i> Caja
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 card-shadow rounded-4 h-100" style="border-left:4px solid var(--teal)!important;">
            <div class="card-body p-3 p-md-4">
                <div class="text-muted small mb-1">Ventas de hoy</div>
                <div class="fs-4 fw-bold" style="color:#0f766e;"><?= htmlspecialchars($fmtMoney((float) ($todaySales['total'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted mt-1">
                    <?= (int) ($todaySales['count'] ?? 0) ?> ticket<?= ((int) ($todaySales['count'] ?? 0)) === 1 ? '' : 's' ?>
                    <?php if ($deltaVsYesterday !== null): ?>
                        <?php
                        $d = (float) $deltaVsYesterday;
                        $up = $d >= 0;
                        ?>
                        <span class="ms-1 <?= $up ? 'text-success' : 'text-danger' ?>">
                            <i class="bi <?= $up ? 'bi-arrow-up-right' : 'bi-arrow-down-right' ?>"></i>
                            <?= $up ? '+' : '' ?><?= htmlspecialchars(number_format($d, 1, '.', ''), ENT_QUOTES, 'UTF-8') ?>% vs ayer
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-3 p-md-4">
                <div class="text-muted small mb-1">Ventas de ayer</div>
                <div class="fs-5 fw-semibold text-dark"><?= htmlspecialchars($fmtMoney((float) ($yesterdaySales['total'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted"><?= (int) ($yesterdaySales['count'] ?? 0) ?> tickets</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-3 p-md-4">
                <div class="text-muted small mb-1">Mes en curso</div>
                <div class="fs-5 fw-semibold text-dark"><?= htmlspecialchars($fmtMoney((float) ($monthSales['total'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted"><?= (int) ($monthSales['count'] ?? 0) ?> ventas registradas</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-3 p-md-4">
                <div class="text-muted small mb-1">Estado de caja</div>
                <?php if ($cashOpen): ?>
                    <div class="fw-semibold text-success"><i class="bi bi-unlock-fill me-1"></i> Caja abierta</div>
                    <?php if ($cashSummary !== null): ?>
                        <div class="small text-muted mt-1">
                            Efectivo esperado: <strong><?= htmlspecialchars($fmtMoney((float) ($cashSummary['expected_amount'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="fw-semibold text-secondary"><i class="bi bi-lock-fill me-1"></i> Sin caja abierta</div>
                    <div class="small text-muted mt-1">Abre sesión para registrar ventas en efectivo.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h2 class="h6 fw-semibold mb-0"><i class="bi bi-clock-history me-2" style="color:var(--teal);"></i> Últimas ventas</h2>
                    <a href="<?= htmlspecialchars($basePath . '/admin/ventas', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary rounded-3">Ver todas</a>
                </div>
                <?php if (empty($recentSales)): ?>
                    <p class="text-muted small mb-0">Aún no hay ventas registradas. Comienza con <a href="<?= htmlspecialchars($basePath . '/admin/pos/nueva-venta', ENT_QUOTES, 'UTF-8') ?>">Nueva venta</a>.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Total</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSales as $rs): ?>
                                    <?php
                                    $sid = (int) ($rs['id'] ?? 0);
                                    $st = (string) ($rs['status'] ?? '');
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?= htmlspecialchars($basePath . '/admin/ventas/detalle/' . $sid, ENT_QUOTES, 'UTF-8') ?>" class="fw-semibold text-decoration-none">
                                                #<?= (int) ($rs['folio'] ?? 0) ?>
                                            </a>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars((string) ($rs['occurred_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="small"><?= htmlspecialchars((string) ($rs['customer_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end fw-semibold"><?= htmlspecialchars($fmtMoney((float) ($rs['total'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <?php if ($st === 'PAID'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success"><?= htmlspecialchars($saleStatusLabel($st), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php elseif ($st === 'OPEN'): ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning"><?= htmlspecialchars($saleStatusLabel($st), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= htmlspecialchars($saleStatusLabel($st), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 card-shadow rounded-4 mb-3">
            <div class="card-body p-4">
                <h2 class="h6 fw-semibold mb-3"><i class="bi bi-speedometer2 me-2" style="color:var(--teal);"></i> Tu tienda</h2>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(20,184,166,.12);border:1px solid rgba(20,184,166,.25);color:var(--teal);">
                        <i class="bi bi-shop fs-5"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Nombre comercial</div>
                        <div class="fw-semibold"><?= htmlspecialchars($shopName ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid rgba(15,23,42,.06);">
                            <div class="fs-5 fw-bold" style="color:#0f766e;"><?= (int) ($catalog['products'] ?? 0) ?></div>
                            <div class="small text-muted">Productos activos</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid rgba(15,23,42,.06);">
                            <div class="fs-5 fw-bold" style="color:#0f766e;"><?= (int) ($catalog['customers'] ?? 0) ?></div>
                            <div class="small text-muted">Clientes activos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 card-shadow rounded-4" style="background:linear-gradient(135deg,#f0fdfa 0%,#f8fafc 100%);">
            <div class="card-body p-4">
                <h2 class="h6 fw-semibold mb-2"><i class="bi bi-lightning-charge me-2" style="color:var(--teal);"></i> Accesos rápidos</h2>
                <div class="d-grid gap-2">
                    <a href="<?= htmlspecialchars($basePath . '/admin/productos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border rounded-3 text-start">
                        <i class="bi bi-box-seam me-2 text-secondary"></i> Catálogo de productos
                    </a>
                    <a href="<?= htmlspecialchars($basePath . '/admin/clientes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border rounded-3 text-start">
                        <i class="bi bi-people me-2 text-secondary"></i> Clientes
                    </a>
                    <a href="<?= htmlspecialchars($basePath . '/admin/reportes', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border rounded-3 text-start">
                        <i class="bi bi-bar-chart-line me-2 text-secondary"></i> Reportes
                    </a>
                    <?php if (!empty($isAdmin)): ?>
                        <a href="<?= htmlspecialchars($basePath . '/admin/configuracion/tienda', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border rounded-3 text-start">
                            <i class="bi bi-gear me-2 text-secondary"></i> Configuración de la tienda
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Panel';
require __DIR__ . '/../layouts/admin.php';
