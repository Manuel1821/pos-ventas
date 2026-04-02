<?php
// Layout base para páginas públicas (catálogo web).
// Se espera:
// - $pageTitle
// - $content (HTML string)
// - variables opcionales: $flash, $basePath
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Catalogo', ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root{
            --teal:#14b8a6;
        }
        body{
            background:#f5f7fb;
            color:#0f172a;
        }
        .topbar{
            background: #ffffff;
            border-bottom: 1px solid rgba(15,23,42,.08);
        }
        .brand-badge{
            background: rgba(20,184,166,.12);
            border: 1px solid rgba(20,184,166,.25);
            color: #0f766e;
        }
        .card-shadow{
            box-shadow: 0 6px 18px rgba(15,23,42,.06);
        }
        .product-img{
            width:100%;
            height: 180px;
            object-fit: cover;
            border-radius: 14px;
            background: #f1f5f9;
        }
        @media (max-width: 576px){
            .product-img{
                height: 150px;
            }
        }
    </style>
</head>
<body>
<header class="topbar">
    <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bag" style="color:var(--teal);"></i>
            <div>
                <div class="fw-semibold" style="line-height:1.1;"><?= htmlspecialchars($shopName ?? 'Catalogo', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="text-muted" style="font-size:12px;">Compra por internet (demo)</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($shopSlug ?? '')): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-grid"></i> Ver catalogo
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container-fluid py-4 px-3 px-lg-4">
    <?php if (!empty($flash) && isset($flash['type'], $flash['message'])): ?>
        <div class="mb-4">
            <div class="alert alert-<?= htmlspecialchars((string) $flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show card-shadow" role="alert">
                <?= htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        </div>
    <?php endif; ?>

    <?= $content ?? '' ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

