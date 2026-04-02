<?php
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup del POS (Hito 1)</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root{ --teal:#14b8a6; }
        body{ background:#f5f7fb; }
        .panel{
            background:#fff;
            border: 1px solid rgba(15,23,42,.08);
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15,23,42,.06);
        }
        .btn-primary{
            background: var(--teal);
            border-color: var(--teal);
        }
        .btn-primary:hover{ filter: brightness(.95); }
        .kpi{
            background:#f8fafc;
            border:1px solid rgba(15,23,42,.06);
            border-radius: 14px;
            padding: 14px;
        }
        .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; }
        /* Evita que un backdrop/modal deje la pantalla oscura y no clickeable */
        body { overflow: auto !important; }
        .modal-backdrop { display: none !important; pointer-events: none !important; }
        body.modal-open { overflow: auto !important; }
        /* Contenedor del POS por encima de cualquier overlay del host */
        .pos-app-wrapper { position: relative; z-index: 99999; pointer-events: auto; min-height: 100vh; }
    </style>
</head>
<body>
<div class="pos-app-wrapper">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <?php
            // Base URL (por ejemplo: https://mimostrador.com/ventas) para que funcione en subcarpetas.
            $baseUrl = $baseUrl ?? (string) (($GLOBALS['config']['app']['url'] ?? '') ?: '');
            $baseUrl = rtrim($baseUrl, '/');
            ?>
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:46px;height:46px;border-radius:16px;background:rgba(20,184,166,.12);border:1px solid rgba(20,184,166,.25);display:flex;align-items:center;justify-content:center;color:var(--teal);font-weight:800;">
                        <i class="bi bi-gear-wide-connected" style="font-size:20px;"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Setup inicial (Hito 1)</div>
                        <div class="text-muted" style="font-size:12px;">Aplica migraciones y seeders a MySQL.</div>
                    </div>
                </div>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Ir a login</a>
            </div>

            <?php if (!empty($flash) && isset($flash['type'], $flash['message'])): ?>
                <div class="mb-4">
                    <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel p-4 p-md-5">
                <h1 class="h5 fw-semibold mb-3">Estado del entorno</h1>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="kpi">
                            <div class="text-muted" style="font-size:12px;">DB</div>
                            <div class="fw-semibold fs-4">
                                <?= !empty($status['db']['connected']) ? '<span style="color:#0f766e;">Conectada</span>' : '<span style="color:#b91c1c;">No conectada</span>' ?>
                            </div>
                            <?php if (!empty($status['db']['error'])): ?>
                                <div class="text-danger mt-2 small mono" style="word-break:break-word;">
                                    <?= htmlspecialchars((string)$status['db']['error'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="kpi">
                            <div class="text-muted" style="font-size:12px;">Base de datos</div>
                            <div class="fw-semibold mono"><?= htmlspecialchars((string)($status['db']['db_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="kpi">
                            <div class="text-muted" style="font-size:12px;">Instalación</div>
                            <div class="fw-semibold fs-4">
                                <?= !empty($status['installed']) ? '<span style="color:#0f766e;">Hecha</span>' : '<span style="color:#b91c1c;">Pendiente</span>' ?>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h2 class="h6 fw-semibold mb-2"><i class="bi bi-shield-check"></i> Ejecutar migraciones y seeders</h2>
                <p class="text-muted mb-3" style="font-size:13px;">
                    Esto creará (si no existen) las tablas y cargará datos base, incluyendo el usuario administrador.
                </p>

                <form method="POST" action="<?= htmlspecialchars($baseUrl . '/setup/run', ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end">
                    <div class="col-12 col-md-8">
                        <label class="form-label" for="confirm">Escribe exactamente `SI` para confirmar</label>
                        <input class="form-control" id="confirm" name="confirm" type="text" placeholder="SI" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            Aplicar cambios
                        </button>
                    </div>
                </form>
            </div>

            <div class="text-center text-muted mt-3" style="font-size:12px;">
                Si falla, revisa tu archivo `.env` con las variables de conexión MySQL.
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Solución defensiva: algunos temas/JS externos pueden dejar un backdrop/modal abierto.
(function () {
  try {
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
    document.body.style.overflow = '';
  } catch (e) {}
})();
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

