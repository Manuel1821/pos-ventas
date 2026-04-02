<?php
$tipo = $tipo ?? 'IN';
$errors = $errors ?? [];
$old = $old ?? ['monto' => '', 'motivo' => ''];
$basePath = $basePath ?? '';
$pageTitle = $pageTitle ?? ($tipo === 'IN' ? 'Ingreso' : 'Retiro');
$isIngreso = $tipo === 'IN';
$actionUrl = $isIngreso ? $basePath . '/admin/caja/guardar-ingreso' : $basePath . '/admin/caja/guardar-retiro';
$titulo = $isIngreso ? 'Ingreso a caja' : 'Retiro de caja';
$icono = $isIngreso ? 'plus-circle' : 'dash-circle';
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-<?= $icono ?> me-2" style="color:var(--teal);"></i> <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted small mb-0">Registre el monto y el motivo del movimiento.</p>
    </div>
    <a href="<?= htmlspecialchars($basePath . '/admin/caja', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
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
                <form method="post" action="<?= htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label for="monto" class="form-label fw-semibold">Monto</label>
                        <input type="text" id="monto" name="monto" class="form-control form-control-lg" placeholder="0.00" value="<?= htmlspecialchars($old['monto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autofocus required>
                    </div>
                    <div class="mb-4">
                        <label for="motivo" class="form-label fw-semibold">Motivo u observaciones</label>
                        <textarea id="motivo" name="motivo" class="form-control" rows="2" placeholder="Opcional"><?= htmlspecialchars($old['motivo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn <?= $isIngreso ? 'btn-success' : 'btn-warning' ?> flex-grow-1">
                            <i class="bi bi-check-lg"></i> Registrar <?= $isIngreso ? 'ingreso' : 'retiro' ?>
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
