<?php
ob_start();
?>
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body p-4">
                <h1 class="h5 fw-semibold mb-2"><i class="bi bi-activity"></i> Conectividad</h1>
                <p class="text-muted mb-0">
                    MySQL respondió correctamente. Esto confirma que PDO + configuración están funcionando.
                </p>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-4">
                <div class="text-muted" style="font-size:12px;">Hora del servidor</div>
                <div class="fw-semibold fs-5"><?= htmlspecialchars($now ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Health';
require __DIR__ . '/../layouts/admin.php';

