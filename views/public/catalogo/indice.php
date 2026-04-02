<?php
ob_start();
?>

<div class="row g-3">
    <div class="col-12 col-lg-3">
        <div class="card border-0 card-shadow rounded-4">
            <div class="card-body p-4">
                <h2 class="h6 fw-semibold mb-3">
                    <i class="bi bi-search"></i> Buscar y filtrar
                </h2>

                <form method="get" action="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label">Búsqueda</label>
                        <input class="form-control" type="text" name="q" value="<?= htmlspecialchars($q ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre, SKU o código">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" name="categoria">
                            <option value="" <?= empty($categorySlug) ? 'selected' : '' ?>>Todas</option>
                            <?php foreach (($categories ?? []) as $cat): ?>
                                <?php
                                $slug = (string) ($cat['slug'] ?? '');
                                $selected = ($categorySlug ?? '') === $slug;
                                ?>
                                <option value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" <?= $selected ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bi bi-filter"></i> Aplicar
                    </button>
                </form>

                <hr class="my-4">

                <div class="text-muted" style="font-size:13px;">
                    <div class="mb-2">
                        <i class="bi bi-info-circle me-1" style="color:var(--teal);"></i>
                        Se muestran solo productos activos.
                    </div>
                    <div>
                        <i class="bi bi-box-seam me-1" style="color:var(--teal);"></i>
                        El stock se actualiza desde el POS.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
            <div>
                <h1 class="h5 mb-1">
                    <i class="bi bi-grid-3x3-gap"></i> Catálogo
                </h1>
                <div class="text-muted" style="font-size:13px;">
                    <?= !empty($categoryName) ? 'Categoría: ' . htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') : 'Mostrando todas las categorías' ?>
                </div>
            </div>
            <div class="badge rounded-pill brand-badge">
                <?= htmlspecialchars((string) ($total ?? 0), ENT_QUOTES, 'UTF-8') ?> productos
            </div>
        </div>

        <?php if (empty($products ?? [])): ?>
            <div class="alert alert-secondary card-shadow">
                No hay productos para los filtros seleccionados.
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach (($products ?? []) as $p): ?>
                    <?php
                    $id = (int) ($p['id'] ?? 0);
                    $name = (string) ($p['name'] ?? '');
                    $price = (float) ($p['price'] ?? 0);
                    $stock = (float) ($p['stock'] ?? 0);
                    $isInv = (int) ($p['is_inventory_item'] ?? 0) === 1;
                    $imagePath = (string) ($p['image_path'] ?? '');
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card border-0 card-shadow rounded-4 h-100">
                            <div class="card-body p-4">
                                <?php if ($imagePath !== ''): ?>
                                    <img class="product-img mb-3"
                                         alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                                         src="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug . '/producto/' . $id . '/imagen-miniatura', ENT_QUOTES, 'UTF-8') ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="product-img mb-3 d-flex align-items-center justify-content-center text-muted">
                                        <i class="bi bi-image" style="font-size:34px;"></i>
                                    </div>
                                <?php endif; ?>

                                <h2 class="h6 fw-semibold mb-2">
                                    <a href="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug . '/producto/' . $id, ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none; color:inherit;">
                                        <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </h2>

                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <div class="fw-semibold">
                                        $<?= htmlspecialchars(number_format($price, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!$isInv): ?>
                                        <span class="badge text-bg-secondary">No inventariado</span>
                                    <?php elseif ($stock <= 0): ?>
                                        <span class="badge text-bg-warning">Agotado</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">
                                            Stock: <?= htmlspecialchars(number_format($stock, 3, '.', ','), ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid">
                                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug . '/producto/' . $id, ENT_QUOTES, 'UTF-8') ?>">
                                        Ver detalle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (($total_pages ?? 1) > 1): ?>
                <div class="mt-4 d-flex align-items-center justify-content-between gap-3">
                    <div class="text-muted" style="font-size:13px;">
                        Página <?= htmlspecialchars((string) ($page ?? 1), ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) ($total_pages ?? 1), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="d-flex gap-2">
                        <?php
                        $prevPage = max(1, (int) ($page ?? 1) - 1);
                        $nextPage = min((int) ($total_pages ?? 1), (int) ($page ?? 1) + 1);
                        $qEsc = htmlspecialchars((string) ($q ?? ''), ENT_QUOTES, 'UTF-8');
                        $catEsc = htmlspecialchars((string) ($categorySlug ?? ''), ENT_QUOTES, 'UTF-8');
                        ?>
                        <a class="btn btn-outline-secondary btn-sm <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>"
                           href="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug . '?pagina=' . $prevPage . '&q=' . rawurlencode((string) ($q ?? '')) . '&categoria=' . rawurlencode((string) ($categorySlug ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            Anterior
                        </a>
                        <a class="btn btn-outline-secondary btn-sm <?= ($page ?? 1) >= (int) ($total_pages ?? 1) ? 'disabled' : '' ?>"
                           href="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug . '?pagina=' . $nextPage . '&q=' . rawurlencode((string) ($q ?? '')) . '&categoria=' . rawurlencode((string) ($categorySlug ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                            Siguiente
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = (string) ($pageTitle ?? 'Catálogo web');
require __DIR__ . '/../../layouts/public.php';

