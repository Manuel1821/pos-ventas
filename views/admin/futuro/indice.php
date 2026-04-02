<?php
ob_start();
?>
<div class="row g-3">
    <div class="col-12 col-lg-8">
        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill" style="background:rgba(20,184,166,.12);color:#0f766e;border:1px solid rgba(20,184,166,.25);">
                            <i class="bi bi-hourglass-split"></i> Hito 10
                        </span>
                        <h1 class="h4 mb-0"><?= htmlspecialchars((string) ($moduleTitle ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
                    </div>
                    <span class="badge rounded-pill text-bg-secondary">
                        <?= htmlspecialchars((string) ($moduleKey ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <p class="text-muted mb-3">
                    <?= htmlspecialchars((string) ($moduleSummary ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php if (($moduleKey ?? '') === 'tienda' && !empty($shopSlug ?? '')): ?>
                    <div class="mb-3">
                        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-globe"></i> Ver catálogo web
                        </a>
                    </div>
                <?php endif; ?>

                <div class="mt-4">
                    <h2 class="h6 fw-semibold mb-2">
                        <i class="bi bi-map"></i> Roadmap propuesto (alto nivel)
                    </h2>
                    <?php if (!empty($moduleRoadmap) && is_array($moduleRoadmap)): ?>
                        <ul class="text-muted mb-0">
                            <?php foreach ($moduleRoadmap as $step): ?>
                                <li><?= htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-muted">A definir.</div>
                    <?php endif; ?>
                </div>

                <hr class="my-4">

                <div>
                    <h2 class="h6 fw-semibold mb-2">
                        <i class="bi bi-link-45deg"></i> Dependencias técnicas y funcionales
                    </h2>
                    <?php if (!empty($moduleDependencies) && is_array($moduleDependencies)): ?>
                        <div class="row g-2">
                            <?php foreach ($moduleDependencies as $dep): ?>
                                <div class="col-12 col-md-6">
                                    <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid rgba(15,23,42,.06);">
                                        <i class="bi bi-check2-circle me-2" style="color:var(--teal);"></i>
                                        <span class="text-muted"><?= htmlspecialchars((string) $dep, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-muted">A definir.</div>
                    <?php endif; ?>
                </div>

                <hr class="my-4">

                <div>
                    <h2 class="h6 fw-semibold mb-2">
                        <i class="bi bi-list-check"></i> Siguientes pasos sugeridos
                    </h2>
                    <?php if (!empty($moduleNextSteps) && is_array($moduleNextSteps)): ?>
                        <ul class="text-muted mb-0">
                            <?php foreach ($moduleNextSteps as $step): ?>
                                <li><?= htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-muted">A definir.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-4">
                <h2 class="h6 fw-semibold mb-3">
                    <i class="bi bi-shield-lock"></i> Lineamientos para no romper el MVP
                </h2>
                <div class="text-muted" style="font-size:13px;">
                    <div class="mb-3">
                        <i class="bi bi-dot me-2" style="color:var(--teal);"></i>
                        Integrar por módulos y validar impacto en inventario/caja.
                    </div>
                    <div class="mb-3">
                        <i class="bi bi-dot me-2" style="color:var(--teal);"></i>
                        Ejecutar operaciones sensibles en transacciones y con auditoria.
                    </div>
                    <div class="mb-3">
                        <i class="bi bi-dot me-2" style="color:var(--teal);"></i>
                        Mantener separación controlador/servicio/repositorio.
                    </div>
                    <div>
                        <i class="bi bi-dot me-2" style="color:var(--teal);"></i>
                        Evitar cambios de esquema en esta fase: solo plan y UI.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageTitle = (string) ($moduleTitle ?? 'Modulo futuro');
require __DIR__ . '/../../layouts/admin.php';

