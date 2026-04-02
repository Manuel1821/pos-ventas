<?php
ob_start();
?>

<?php
$name = (string) ($product['name'] ?? '');
$price = (float) ($product['price'] ?? 0);
$stock = (float) ($product['stock'] ?? 0);
$isInv = (int) ($product['is_inventory_item'] ?? 0) === 1;
$categoryName = (string) ($product['category_name'] ?? '');
$productImages = $productImages ?? [];
$pid = (int) ($product['id'] ?? 0);
$mainImgUrl = $basePath . '/catalogo/' . $shopSlug . '/producto/' . $pid . '/imagen';
?>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-4">
                <?php if (!empty($product['image_path'] ?? '')): ?>
                    <img
                        id="catalogProductMainImg"
                        class="product-img"
                        style="height: 320px;"
                        alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                        src="<?= htmlspecialchars($mainImgUrl, ENT_QUOTES, 'UTF-8') ?>"
                    >
                    <?php if (count($productImages) > 1): ?>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <?php foreach ($productImages as $im): ?>
                                <?php
                                $iid = (int) ($im['id'] ?? 0);
                                $thumbUrl = $basePath . '/catalogo/' . $shopSlug . '/producto/' . $pid . '/imagen/' . $iid . '/miniatura';
                                ?>
                                <button type="button" class="btn p-0 border rounded overflow-hidden catalog-gallery-thumb" style="width:68px;height:68px;" data-img-src="<?= htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8') ?>">
                                    <img src="<?= htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-100 h-100" style="object-fit:cover;">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <script>
                        (function () {
                            var main = document.getElementById('catalogProductMainImg');
                            document.querySelectorAll('.catalog-gallery-thumb').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    var u = btn.getAttribute('data-img-src');
                                    if (main && u) main.src = u;
                                });
                            });
                        })();
                        </script>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="product-img d-flex align-items-center justify-content-center text-muted" style="height:320px;">
                        <i class="bi bi-image" style="font-size:34px;"></i>
                    </div>
                <?php endif; ?>

                <div class="mt-3">
                    <?php if (!$isInv): ?>
                        <span class="badge text-bg-secondary">No inventariado</span>
                    <?php elseif ($stock <= 0): ?>
                        <span class="badge text-bg-warning">Agotado</span>
                    <?php else: ?>
                        <span class="badge text-bg-success">Stock: <?= htmlspecialchars(number_format($stock, 3, '.', ','), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card border-0 card-shadow rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                    <div>
                        <h1 class="h4 mb-1"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h1>
                        <?php if ($categoryName !== ''): ?>
                            <div class="text-muted" style="font-size:13px;">
                                <i class="bi bi-tag me-1" style="color:var(--teal);"></i>
                                <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold" style="font-size:18px;">
                            $<?= htmlspecialchars(number_format($price, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <div class="text-muted" style="font-size:12px;">Precio de catálogo</div>
                    </div>
                </div>

                <?php if (!empty($product['description'] ?? '')): ?>
                    <hr class="my-4">
                    <div class="text-muted" style="white-space: pre-wrap;">
                        <?= htmlspecialchars((string) ($product['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <hr class="my-4">

                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid rgba(15,23,42,.06);">
                            <div class="text-muted" style="font-size:12px;">SKU</div>
                            <div class="fw-semibold"><?= htmlspecialchars((string) ($product['sku'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid rgba(15,23,42,.06);">
                            <div class="text-muted" style="font-size:12px;">Unidad</div>
                            <div class="fw-semibold"><?= htmlspecialchars((string) ($product['unit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="alert alert-secondary">
                        <div class="fw-semibold mb-1"><i class="bi bi-cart"></i> Pedido en línea</div>
                        <div class="text-muted" style="font-size:13px;">
                            Esta demo termina el catálogo web (productos + detalle + stock).
                            El flujo de pedidos se implementará conectando a ventas del POS sin romper inventario/caja.
                        </div>
                    </div>

                    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($basePath . '/catalogo/' . $shopSlug, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-arrow-left"></i> Volver al catálogo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = (string) ($pageTitle ?? 'Detalle de producto');
require __DIR__ . '/../../layouts/public.php';

