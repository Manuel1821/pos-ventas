<?php
$summary = $summary ?? null;
$errors = $errors ?? [];
$old = $old ?? ['monto_contado' => '', 'observaciones' => ''];
$basePath = $basePath ?? '';
$pageTitle = $pageTitle ?? 'Cerrar caja';
if (!$summary) {
    header('Location: ' . $basePath . '/admin/caja');
    exit;
}
$expected = (float) ($summary['expected_amount'] ?? 0);
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-x-circle me-2" style="color:var(--teal);"></i> Cerrar caja</h1>
        <p class="text-muted small mb-0">Capture el monto contado y opcionalmente las observaciones del corte.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/caja', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-4">
        <div class="card border-0 card-shadow rounded-4 border-primary">
            <div class="card-body p-4">
                <div class="small text-muted">Total esperado en caja</div>
                <div class="fw-bold fs-3 text-primary">$<?= number_format($expected, 2, '.', ',') ?></div>
                <div class="small text-muted mt-2">
                    Inicial + ventas + ingresos − retiros
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 list-unstyled">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($basePath . '/admin/caja/guardar-cierre', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="monto_contado" class="form-label fw-semibold">Monto contado (real en caja)</label>
                        <input type="text" id="monto_contado" name="monto_contado" class="form-control form-control-lg" placeholder="<?= number_format($expected, 2, '.', '') ?>" value="<?= htmlspecialchars($old['monto_contado'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autofocus>
                        <div class="form-text">El sistema calculará la diferencia (sobrante o faltante).</div>
                    </div>
                    <div class="mb-4">
                        <label for="observaciones" class="form-label fw-semibold">Observaciones del corte</label>
                        <textarea id="observaciones" name="observaciones" class="form-control" rows="3" placeholder="Opcional"><?= htmlspecialchars($old['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger flex-grow-1">
                            <i class="bi bi-x-circle"></i> Cerrar sesión de caja
                        </button>
                        <a href="<?= htmlspecialchars($basePath . '/admin/caja', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
