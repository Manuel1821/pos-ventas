<?php
$tab = $tab ?? 'ventas_periodo';
$tabs = $tabs ?? [];
$filters = $filters ?? [];
$reportData = $reportData ?? [];
$users = $users ?? [];
$cashSessions = $cashSessions ?? [];
$expenseCategories = $expenseCategories ?? [];
$productCategories = $productCategories ?? [];
$paymentMethods = $paymentMethods ?? [];
$basePath = $basePath ?? '';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}
$pageTitle = $pageTitle ?? 'Reportes';

if (!function_exists('reportesBuildQuery')) {
    function reportesBuildQuery(string $tab, array $filters): string
    {
        $query = ['reporte' => $tab];
        foreach ([
            'desde',
            'hasta',
            'user_id',
            'cash_session_id',
            'expense_category_id',
            'category_id',
            'payment_method',
            'supplier',
            'q',
            'status',
            'availability',
        ] as $key) {
            if (!empty($filters[$key])) {
                $query[$key] = (string) $filters[$key];
            }
        }
        return http_build_query($query);
    }
}

$tabLabels = [
    'ventas_periodo' => 'Ventas por periodo',
    'ventas_metodo_pago' => 'Ventas por método de pago',
    'caja' => 'Caja',
    'gastos' => 'Gastos',
    'utilidad' => 'Utilidad básica',
    'inventario' => 'Inventario actual',
];
$summary = (array) ($reportData['summary'] ?? []);
$items = (array) ($reportData['items'] ?? []);

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-bar-chart-line me-2" style="color:var(--teal);"></i> Reportes</h1>
        <p class="text-muted small mb-0">Consulta operativa de ventas, caja, gastos, utilidad e inventario con filtros unificados.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/reportes/exportar?' . reportesBuildQuery($tab, $filters), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Exportar CSV
    </a>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <ul class="nav nav-pills flex-wrap gap-2">
            <?php foreach ($tabs as $tabKey): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === $tabKey ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($basePath . '/admin/reportes?' . reportesBuildQuery($tabKey, $filters), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ($tabLabels[$tabKey] ?? $tabKey), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/reportes', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <input type="hidden" name="reporte" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">

            <?php if (in_array($tab, ['ventas_periodo', 'ventas_metodo_pago', 'caja', 'gastos', 'utilidad'], true)): ?>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Desde</label>
                    <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars((string) ($filters['desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted">Hasta</label>
                    <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars((string) ($filters['hasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            <?php endif; ?>

            <?php if (in_array($tab, ['ventas_periodo', 'ventas_metodo_pago', 'caja', 'gastos', 'utilidad'], true)): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Usuario</label>
                    <select name="user_id" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int) ($u['id'] ?? 0) ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) ($u['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(trim((string) ($u['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array($tab, ['ventas_periodo', 'ventas_metodo_pago', 'caja', 'utilidad'], true)): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Sesión de caja</label>
                    <select name="cash_session_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($cashSessions as $s): ?>
                            <option value="<?= (int) ($s['id'] ?? 0) ?>" <?= (int) ($filters['cash_session_id'] ?? 0) === (int) ($s['id'] ?? 0) ? 'selected' : '' ?>>
                                #<?= (int) ($s['id'] ?? 0) ?> - <?= htmlspecialchars((string) ($s['opened_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array($tab, ['ventas_metodo_pago', 'gastos', 'utilidad'], true)): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Método de pago</label>
                    <select name="payment_method" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($paymentMethods as $pm): ?>
                            <option value="<?= htmlspecialchars($pm, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['payment_method'] ?? '') === $pm ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pm, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array($tab, ['gastos', 'utilidad'], true)): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Categoría gasto</label>
                    <select name="expense_category_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($expenseCategories as $c): ?>
                            <option value="<?= (int) ($c['id'] ?? 0) ?>" <?= (int) ($filters['expense_category_id'] ?? 0) === (int) ($c['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Proveedor</label>
                    <input type="text" name="supplier" class="form-control" value="<?= htmlspecialchars((string) ($filters['supplier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            <?php endif; ?>

            <?php if ($tab === 'inventario'): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Categoría producto</label>
                    <select name="category_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($productCategories as $c): ?>
                            <option value="<?= (int) ($c['id'] ?? 0) ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) ($c['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="ACTIVE" <?= ($filters['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Activo</option>
                        <option value="INACTIVE" <?= ($filters['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted">Disponibilidad</label>
                    <select name="availability" class="form-select">
                        <option value="">Todas</option>
                        <option value="IN_STOCK" <?= ($filters['availability'] ?? '') === 'IN_STOCK' ? 'selected' : '' ?>>Con stock</option>
                        <option value="OUT_OF_STOCK" <?= ($filters['availability'] ?? '') === 'OUT_OF_STOCK' ? 'selected' : '' ?>>Sin stock</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted">Búsqueda</label>
                    <input type="text" name="q" class="form-control" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre, SKU o código">
                </div>
            <?php endif; ?>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/reportes?reporte=' . $tab, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($summary as $label => $value): ?>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase"><?= htmlspecialchars(str_replace('_', ' ', (string) $label), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="h5 mb-0 mt-1">
                        <?php
                        $isMoney = str_contains((string) $label, 'total') || str_contains((string) $label, 'profit');
                        echo $isMoney
                            ? '$' . number_format((float) $value, 2, '.', ',')
                            : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($tab === 'utilidad'): ?>
    <div class="alert alert-info border-0 card-shadow rounded-4">
        Esta utilidad es <strong>operativa básica</strong> (ventas pagadas - gastos activos) y no sustituye un cálculo contable formal.
    </div>
<?php endif; ?>

<?php if ($tab !== 'utilidad'): ?>
    <div class="card border-0 card-shadow rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <?php if (!empty($items)): ?>
                            <?php foreach (array_keys((array) $items[0]) as $head): ?>
                                <th><?= htmlspecialchars(str_replace('_', ' ', (string) $head), ENT_QUOTES, 'UTF-8') ?></th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th>Resultado</th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td class="text-center text-muted py-5">No hay datos para los filtros seleccionados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): ?>
                            <tr>
                                <?php foreach ((array) $row as $value): ?>
                                    <td>
                                        <?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                <?php endforeach; ?>
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

