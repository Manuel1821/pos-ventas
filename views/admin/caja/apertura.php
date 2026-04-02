<?php
$errors = $errors ?? [];
$old = $old ?? ['monto_inicial' => ''];
$basePath = $basePath ?? '';
$pageTitle = $pageTitle ?? 'Abrir caja';
ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-box-arrow-in-down-right me-2" style="color:var(--teal);"></i> Abrir caja</h1>
        <p class="text-muted small mb-0">Indique el monto inicial o fondo de caja para iniciar la sesión.</p>
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
                <form method="post" action="<?= htmlspecialchars($basePath . '/admin/caja/guardar-apertura', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-4">
                        <label for="monto_inicial" class="form-label fw-semibold">Monto inicial (fondo de caja)</label>
                        <input type="text" id="monto_inicial" name="monto_inicial" class="form-control form-control-lg" placeholder="0.00" value="<?= htmlspecialchars($old['monto_inicial'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autofocus>
                        <div class="form-text">Puede ser 0 si inicia sin efectivo.</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-check-lg"></i> Abrir caja
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
