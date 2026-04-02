<?php
// Layout base del panel administrativo.
// Se espera que el layout reciba: $pageTitle, $content (HTML string), $userName, $shopName y opcionalmente $flash.

use App\Core\Auth;
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Panel', ENT_QUOTES, 'UTF-8') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root{
            --teal:#14b8a6;
            --bg:#0b1220;
        }
        body{
            background:#f5f7fb;
        }
        .sidebar{
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #081224 0%, var(--bg) 100%);
            color: rgba(255,255,255,.9);
        }
        .sidebar-desktop{
            position: sticky;
            top: 0;
        }
        .sidebar-mobile .offcanvas{
            width: 280px;
            background: linear-gradient(180deg, #081224 0%, var(--bg) 100%);
            color: rgba(255,255,255,.9);
        }
        .sidebar-mobile .offcanvas-header{
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-mobile .btn-close{
            filter: invert(1) grayscale(100%);
        }
        .sidebar a{
            color: rgba(255,255,255,.85);
            text-decoration: none;
        }
        .sidebar a:hover{
            color: #fff;
        }
        .brand{
            padding: 18px 18px 10px;
        }
        .brand .logo{
            width:40px;height:40px;border-radius:12px;
            display:flex;align-items:center;justify-content:center;
            background: rgba(20,184,166,.18);
            border: 1px solid rgba(20,184,166,.35);
            color: var(--teal);
            font-weight: 800;
        }
        .menu-link{
            display:flex;align-items:center;gap:10px;
            padding: 12px 18px;
            border-left: 3px solid transparent;
            transition: all .15s ease;
        }
        .menu-link.active{
            border-left-color: var(--teal);
            background: rgba(20,184,166,.10);
        }
        .sidebar .btn-nueva-venta {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 8px 14px 16px;
            padding: 12px 14px;
            border-radius: 12px;
            font-weight: 600;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.14);
            color: #fff !important;
            text-decoration: none !important;
            transition: background .15s ease, transform .1s ease;
        }
        .sidebar .btn-nueva-venta:hover {
            background: rgba(255,255,255,.18);
            color: #fff !important;
            transform: translateY(-1px);
        }
        .sidebar .btn-nueva-venta i { font-size: 1.15rem; }
        .sidebar .btn-nueva-venta.active {
            background: rgba(20,184,166,.22);
            border-color: rgba(20,184,166,.45);
            box-shadow: 0 0 0 1px rgba(20,184,166,.25);
        }
        .topbar{
            background: #ffffff;
            border-bottom: 1px solid rgba(15,23,42,.08);
        }
        .btn-primary{
            background: var(--teal);
            border-color: var(--teal);
        }
        .btn-primary:hover{
            filter: brightness(.95);
        }
        .card-shadow{
            box-shadow: 0 6px 18px rgba(15,23,42,.06);
        }
        /* Modales Bootstrap: backdrop va al body; NO usar z-index en .pos-app-wrapper o el backdrop
           queda encima de todo el panel y bloquea clics en cerrar/cancelar del modal. */
        body.modal-open { overflow: hidden !important; }
        .modal { z-index: 1055; }
        .modal-backdrop { z-index: 1050; }
        .pos-app-wrapper { position: relative; min-height: 100vh; }
        @media (max-width: 991.98px){
            .topbar .badge{
                font-size: 11px;
                padding: .35rem .5rem;
            }
        }
    </style>
</head>
<body>
<div class="pos-app-wrapper">
<?php
// Prefijo real en subcarpetas (ej: /manuel).
// Usamos SCRIPT_NAME (front-controller) para que links funcionen aunque APP_URL esté vacío o sea distinto.
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($basePath === '.' || $basePath === '\\' || $basePath === '/' || $basePath === '') {
    $basePath = '';
}
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$rootPattern = '#^' . preg_quote((string) ($basePath ?? ''), '#') . '/?$#';
$isRoot = preg_match($rootPattern, $requestUri) === 1;
$isNuevaVentaPage = strpos($requestUri, '/admin/pos/nueva-venta') !== false;
$menuItems = (array) ($GLOBALS['config']['menu']['admin'] ?? []);
?>
<div class="d-flex">
    <aside class="sidebar sidebar-desktop d-none d-lg-block">
        <div class="brand d-flex align-items-center gap-2">
            <div class="logo"><i class="bi bi-bag"></i></div>
            <div>
                <div style="font-weight:800;letter-spacing:.2px;">POS SaaS</div>
                <div style="font-size:12px;opacity:.85;">MVP - Hito 1</div>
            </div>
        </div>
        <a class="btn-nueva-venta <?= $isNuevaVentaPage ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/pos/nueva-venta', ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-plus-lg"></i> Nueva venta
        </a>
        <nav class="mt-2">
            <?php foreach ($menuItems as $item): ?>
                <?php
                if (!empty($item['roles']) && is_array($item['roles']) && !Auth::hasAnyRole($item['roles'])) {
                    continue;
                }
                if (!empty($item['children']) && is_array($item['children'])) {
                    ?>
                    <div class="d-flex align-items-center gap-2 px-3 pt-3 pb-1 small text-uppercase" style="opacity:.55;letter-spacing:.04em;">
                        <i class="<?= htmlspecialchars((string) ($item['icon'] ?? 'bi bi-dot'), ENT_QUOTES, 'UTF-8') ?>"></i>
                        <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php foreach ($item['children'] as $ch): ?>
                        <?php
                        $chHref = (string) ($ch['href'] ?? '');
                        if ($chHref === '') {
                            continue;
                        }
                        $active = false;
                        foreach ((array) ($ch['activeSubstrings'] ?? []) as $sub) {
                            $sub = (string) $sub;
                            if ($sub !== '' && strpos($requestUri, $sub) !== false) {
                                $active = true;
                                break;
                            }
                        }
                        ?>
                        <a class="menu-link ps-4 <?= $active ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . $chHref, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-chevron-right" style="font-size:.75rem;opacity:.6;"></i>
                            <?= htmlspecialchars((string) ($ch['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                    <?php
                    continue;
                }
                $href = (string) ($item['href'] ?? '');
                if ($href === '') {
                    continue;
                }
                $active = false;
                if (($item['activeRoot'] ?? false) && $isRoot) {
                    $active = true;
                }
                foreach ((array) ($item['activeSubstrings'] ?? []) as $sub) {
                    $sub = (string) $sub;
                    if ($sub !== '' && strpos($requestUri, $sub) !== false) {
                        $active = true;
                        break;
                    }
                }
                ?>
                <a class="menu-link <?= $active ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . $href, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="<?= htmlspecialchars((string) ($item['icon'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></i>
                    <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Menú móvil -->
    <div class="sidebar-mobile d-lg-none">
        <div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header brand d-flex align-items-center gap-2">
                <div class="logo"><i class="bi bi-bag"></i></div>
                <div class="flex-grow-1">
                    <div id="mobileSidebarLabel" style="font-weight:800;letter-spacing:.2px;">POS SaaS</div>
                    <div style="font-size:12px;opacity:.85;">MVP - Hito 1</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
            </div>
            <div class="offcanvas-body p-0">
                <a class="btn-nueva-venta <?= $isNuevaVentaPage ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . '/admin/pos/nueva-venta', ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-plus-lg"></i> Nueva venta
                </a>
                <nav class="mt-2">
                    <?php foreach ($menuItems as $item): ?>
                        <?php
                        if (!empty($item['roles']) && is_array($item['roles']) && !Auth::hasAnyRole($item['roles'])) {
                            continue;
                        }
                        if (!empty($item['children']) && is_array($item['children'])) {
                            ?>
                            <div class="d-flex align-items-center gap-2 px-3 pt-3 pb-1 small text-uppercase" style="opacity:.55;letter-spacing:.04em;">
                                <i class="<?= htmlspecialchars((string) ($item['icon'] ?? 'bi bi-dot'), ENT_QUOTES, 'UTF-8') ?>"></i>
                                <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <?php foreach ($item['children'] as $ch): ?>
                                <?php
                                $chHref = (string) ($ch['href'] ?? '');
                                if ($chHref === '') {
                                    continue;
                                }
                                $active = false;
                                foreach ((array) ($ch['activeSubstrings'] ?? []) as $sub) {
                                    $sub = (string) $sub;
                                    if ($sub !== '' && strpos($requestUri, $sub) !== false) {
                                        $active = true;
                                        break;
                                    }
                                }
                                ?>
                                <a class="menu-link ps-4 <?= $active ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . $chHref, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-chevron-right" style="font-size:.75rem;opacity:.6;"></i>
                                    <?= htmlspecialchars((string) ($ch['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endforeach; ?>
                            <?php
                            continue;
                        }
                        $href = (string) ($item['href'] ?? '');
                        if ($href === '') {
                            continue;
                        }
                        $active = false;
                        if (($item['activeRoot'] ?? false) && $isRoot) {
                            $active = true;
                        }
                        foreach ((array) ($item['activeSubstrings'] ?? []) as $sub) {
                            $sub = (string) $sub;
                            if ($sub !== '' && strpos($requestUri, $sub) !== false) {
                                $active = true;
                                break;
                            }
                        }
                        ?>
                        <a class="menu-link <?= $active ? 'active' : '' ?>" href="<?= htmlspecialchars($basePath . $href, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="<?= htmlspecialchars((string) ($item['icon'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></i>
                            <?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </div>

    <div class="flex-grow-1">
        <header class="topbar">
            <div class="container-fluid py-3 px-3 px-lg-4 d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Abrir menú">
                        <i class="bi bi-list"></i>
                    </button>
                    <i class="bi bi-shop" style="color:var(--teal);"></i>
                    <div>
                        <div class="fw-semibold" style="line-height:1.1;"><?= htmlspecialchars($shopName ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="text-muted" style="font-size:12px;">Sesión activa</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill" style="background:rgba(20,184,166,.12);color:#0f766e;border:1px solid rgba(20,184,166,.25);">
                        <i class="bi bi-person"></i>
                        <?= htmlspecialchars($userName ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($basePath . '/logout', ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </header>

        <main class="container-fluid py-4 px-3 px-lg-4">
            <?php if (!empty($flash) && isset($flash['type'], $flash['message'])): ?>
                <div class="mb-4">
                    <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show card-shadow" role="alert">
                        <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                </div>
            <?php endif; ?>

            <?= $content ?? '' ?>
        </main>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Solución defensiva: si un modal/backdrop queda pegado del host o de una navegación anterior,
// eliminamos el overlay para que el UI no quede “deshabilitado”.
(function () {
  try {
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
    document.body.style.overflow = '';
  } catch (e) {}
})();
</script>
</body>
</html>

