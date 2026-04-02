<?php

use App\Validation\ExpenseValidator;

$items = $items ?? [];
$total = $total ?? 0;
$page = $page ?? 1;
$totalPages = $total_pages ?? 1;
$filters = $filters ?? [];
$categories = $categories ?? [];
$users = $users ?? [];
$paymentMethods = $paymentMethods ?? ExpenseValidator::PAYMENT_METHODS;
$basePath = $basePath ?? '';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}
$pageTitle = $pageTitle ?? 'Gastos';

if (!function_exists('gastosQueryBuild')) {
    function gastosQueryBuild(array $filters, int $pagina): string
    {
        $query = ['pagina' => $pagina];
        foreach (['q', 'desde', 'hasta', 'expense_category_id', 'supplier', 'payment_method', 'user_id', 'estado'] as $key) {
            if (!empty($filters[$key])) {
                $query[$key] = (string) $filters[$key];
            }
        }
        return http_build_query($query);
    }
}

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-wallet2 me-2" style="color:var(--teal);"></i> Gastos</h1>
        <p class="text-muted small mb-0">Egresos operativos con filtros y baja lógica. No afectan caja en este hito.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= htmlspecialchars($basePath . '/admin/gastos/categorias', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-folder2-open me-1"></i> Categorías
        </a>
        <a href="<?= htmlspecialchars($basePath . '/admin/gastos/crear', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Registrar gasto
        </a>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="get" action="<?= htmlspecialchars($basePath . '/admin/gastos', ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label small text-muted">Búsqueda</label>
                <input type="text" name="q" class="form-control" value="<?= htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Descripción, proveedor, ref.">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Desde</label>
                <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars((string) ($filters['desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">Hasta</label>
                <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars((string) ($filters['hasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label small text-muted">Categoría</label>
                <select name="expense_category_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) ($c['id'] ?? 0) ?>" <?= (int) ($filters['expense_category_id'] ?? 0) === (int) ($c['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label small text-muted">Proveedor</label>
                <input type="text" name="supplier" class="form-control" value="<?= htmlspecialchars((string) ($filters['supplier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Contiene…">
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label small text-muted">Método de pago</label>
                <select name="payment_method" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($paymentMethods as $pm): ?>
                        <option value="<?= htmlspecialchars($pm, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['payment_method'] ?? '') === $pm ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ExpenseValidator::paymentMethodLabel($pm), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label small text-muted">Registró</label>
                <select name="user_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) ($u['id'] ?? 0) ?>" <?= (int) ($filters['user_id'] ?? 0) === (int) ($u['id'] ?? 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim((string) ($u['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4 col-lg-2">
                <label class="form-label small text-muted">Estado</label>
                <select name="estado" class="form-select">
                    <option value="" <?= ($filters['estado'] ?? '') === '' ? 'selected' : '' ?>>Activos</option>
                    <option value="ALL" <?= ($filters['estado'] ?? '') === 'ALL' ? 'selected' : '' ?>>Todos</option>
                    <option value="CANCELLED" <?= ($filters['estado'] ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Anulados</option>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Filtrar</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/gastos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th class="text-end">Monto</th>
                        <th>Pago</th>
                        <th>Proveedor</th>
                        <th>Usuario</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">No hay gastos con estos criterios.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): ?>
                            <?php
                            $st = (string) ($row['status'] ?? 'ACTIVE');
                            $badgeClass = $st === 'ACTIVE' ? 'bg-success' : 'bg-secondary';
                            $stLabel = $st === 'ACTIVE' ? 'Activo' : 'Anulado';
                            ?>
                            <tr class="<?= $st === 'CANCELLED' ? 'table-secondary' : '' ?>">
                                <td class="small"><?= !empty($row['occurred_at']) ? date('d/m/Y H:i', strtotime((string) $row['occurred_at'])) : '—' ?></td>
                                <td><span class="badge rounded-pill" style="background:rgba(20,184,166,.12);color:#0f766e;border:1px solid rgba(20,184,166,.25);"><?= htmlspecialchars((string) ($row['category_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="small"><?= htmlspecialchars((string) ($row['concept'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end fw-semibold">$<?= number_format((float) ($row['total'] ?? 0), 2, '.', ',') ?></td>
                                <td class="small"><?= htmlspecialchars(ExpenseValidator::paymentMethodLabel((string) ($row['payment_method'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars((string) ($row['supplier_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small"><?= htmlspecialchars(trim((string) ($row['creator_name'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge rounded-pill <?= $badgeClass ?>"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= htmlspecialchars($basePath . '/admin/gastos/detalle/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                                        <?php if ($st === 'ACTIVE'): ?>
                                            <a href="<?= htmlspecialchars($basePath . '/admin/gastos/editar/' . (int) ($row['id'] ?? 0), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-muted">Mostrando <?= count($items) ?> de <?= (int) $total ?> registros</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/gastos?' . gastosQueryBuild($filters, $page - 1), ENT_QUOTES, 'UTF-8') ?>">Anterior</a></li>
                        <?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/gastos?' . gastosQueryBuild($filters, $i), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($basePath . '/admin/gastos?' . gastosQueryBuild($filters, $page + 1), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
