<?php
// Vista pública de login.
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{ --teal:#14b8a6; }
        body{ background: #f5f7fb; }
        .brand{
            color: var(--teal);
            font-weight: 800;
            letter-spacing: .2px;
        }
        .btn-primary{
            background: var(--teal);
            border-color: var(--teal);
        }
        .btn-primary:hover{ filter: brightness(.95); }
        .panel{
            background: #fff;
            border: 1px solid rgba(15,23,42,.08);
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15,23,42,.06);
        }
        body { overflow: auto !important; }
        .modal-backdrop { display: none !important; pointer-events: none !important; }
        body.modal-open { overflow: auto !important; }
        .pos-app-wrapper { position: relative; z-index: 99999; pointer-events: auto; min-height: 100vh; }
    </style>
</head>
<body>
<div class="pos-app-wrapper">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-7 col-lg-5">
            <div class="panel p-4 p-md-5">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center" style="width:44px;height:44px;border-radius:14px;background:rgba(20,184,166,.12);border:1px solid rgba(20,184,166,.25);color:var(--teal);">
                            <i class="bi bi-lock"></i>
                        </div>
                        <div>
                            <div class="brand">POS SaaS</div>
                            <div class="text-muted" style="font-size:12px;">Acceso al panel administrativo</div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($flash) && isset($flash['type'], $flash['message'])): ?>
                    <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <?php
                // Evita avisos por variable no definida.
                $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
                $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
                if ($basePath === '.' || $basePath === '\\' || $basePath === '/' || $basePath === '') {
                    $basePath = '';
                }
                ?>
                <form method="POST" action="<?= htmlspecialchars($basePath . '/login', ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label" for="email">Correo</label>
                        <input class="form-control" id="email" name="email" type="email" placeholder="admin@tenda.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input class="form-control" id="password" name="password" type="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </button>
                    <div class="text-muted mt-3" style="font-size:12px;">
                        Consejo: configura el usuario admin con los seeders del Hito 1.
                    </div>
                </form>
            </div>
            <div class="text-center text-muted mt-3" style="font-size:12px;">
                PHP + MySQL (PDO) - Bootstrap 5
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Solución defensiva: evita que un backdrop/modal de otro script del host se quede pegado.
(function () {
  try {
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
    document.body.style.overflow = '';
  } catch (e) {}
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

