<?php
$product = $product ?? [];
$productImages = $productImages ?? [];
$categories = $categories ?? [];
$errors = $errors ?? [];
$old = $old ?? $product;
$basePath = $basePath ?? '';
$id = (int) ($product['id'] ?? 0);
ob_start();
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= htmlspecialchars($basePath . '/admin/productos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h4 mb-0"><i class="bi bi-pencil-square me-2" style="color:var(--teal);"></i> Editar producto</h1>
</div>

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
        <form action="<?= htmlspecialchars($basePath . '/admin/productos/actualizar/' . $id, ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($old['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required maxlength="200" autofocus>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="sku" class="form-label">SKU</label>
                            <input type="text" class="form-control" id="sku" name="sku" value="<?= htmlspecialchars($old['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="120">
                        </div>
                        <div class="col-6">
                            <label for="barcode" class="form-label">Código de barras</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="barcode" name="barcode" value="<?= htmlspecialchars($old['barcode'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="120" autocomplete="off" placeholder="Escanea o escribe">
                                <button type="button" class="btn btn-outline-secondary" id="productBarcodeOpenCameraBtn" title="Escanear con la cámara">
                                    <i class="bi bi-camera"></i>
                                </button>
                            </div>
                            <div class="form-text">Lector USB: haz clic en el campo y escanea. En el móvil, usa el botón de cámara.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="2" maxlength="2000"><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="category_id" class="form-label">Categoría</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= (int)($old['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="unit" class="form-label">Unidad</label>
                            <input type="text" class="form-control" id="unit" name="unit" value="<?= htmlspecialchars($old['unit'] ?? 'Unidad', ENT_QUOTES, 'UTF-8') ?>" maxlength="60">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <label for="price" class="form-label">Precio de venta <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="price" name="price" value="<?= htmlspecialchars($old['price'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-4">
                            <label for="cost" class="form-label">Costo</label>
                            <input type="text" class="form-control" id="cost" name="cost" value="<?= htmlspecialchars($old['cost'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-4">
                            <label for="tax_percent" class="form-label">% Impuesto</label>
                            <input type="text" class="form-control" id="tax_percent" name="tax_percent" value="<?= htmlspecialchars($old['tax_percent'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="text" class="form-control" id="stock" name="stock" value="<?= htmlspecialchars($old['stock'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-6 d-flex align-items-end pb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_inventory_item" name="is_inventory_item" value="1" <?= !empty($old['is_inventory_item']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_inventory_item">Es inventariable</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status">
                            <option value="ACTIVE" <?= ($old['status'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Activo</option>
                            <option value="INACTIVE" <?= ($old['status'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                        <div class="form-text">Los productos inactivos no estarán disponibles en el POS.</div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="mb-3">
                        <label class="form-label">Fotos del producto</label>
                        <p class="small text-muted mb-2">La <strong>principal</strong> se usa en POS, listados y como miniatura; el catálogo web puede mostrar todas.</p>
                        <?php if (!empty($productImages)): ?>
                            <div class="d-flex flex-column gap-2 mb-4">
                                <?php foreach ($productImages as $img): ?>
                                    <?php
                                    $imgId = (int) ($img['id'] ?? 0);
                                    $isPrimary = (int) ($img['is_primary'] ?? 0) === 1;
                                    $imgUrl = $basePath . '/admin/productos/imagen/' . $id . '/' . $imgId . '/miniatura';
                                    ?>
                                    <div class="d-flex gap-2 align-items-start p-2 rounded-3 border bg-white">
                                        <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="rounded" style="width:64px;height:64px;object-fit:cover;">
                                        <div class="flex-grow-1 small">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="primary_choice" id="pc_e_<?= $imgId ?>" value="e_<?= $imgId ?>" <?= $isPrimary ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="pc_e_<?= $imgId ?>">Principal</label>
                                            </div>
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" name="delete_image_ids[]" id="pc_del_<?= $imgId ?>" value="<?= $imgId ?>">
                                                <label class="form-check-label text-danger" for="pc_del_<?= $imgId ?>">Eliminar</label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small mb-2">Sin fotos aún.</div>
                        <?php endif; ?>
                        <label for="productImagesInput" class="form-label">Añadir más fotos</label>
                        <input type="file" class="form-control" id="productImagesInput" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                        <div class="form-text">JPG, PNG, GIF o WebP. Máx. 20 en total.</div>
                        <div id="productEditNewPrimarySlots" class="mt-2"></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/productos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_barcode_cam_modal.php'; ?>
<script>
(function () {
    var input = document.getElementById('productImagesInput');
    var box = document.getElementById('productEditNewPrimarySlots');
    if (!input || !box) return;
    function render() {
        box.innerHTML = '';
        var files = input.files;
        if (!files || files.length === 0) return;
        var t = document.createElement('div');
        t.className = 'small fw-semibold text-muted mb-1';
        t.textContent = 'Si añades fotos nuevas, elige cuál será la principal (o marca una de las actuales arriba):';
        box.appendChild(t);
        for (var i = 0; i < files.length; i++) {
            var id = 'pedit_n_' + i;
            var wrap = document.createElement('div');
            wrap.className = 'form-check';
            var inp = document.createElement('input');
            inp.type = 'radio';
            inp.className = 'form-check-input';
            inp.name = 'primary_choice';
            inp.id = id;
            inp.value = 'n_' + i;
            var lab = document.createElement('label');
            lab.className = 'form-check-label';
            lab.htmlFor = id;
            lab.textContent = 'Nueva imagen ' + (i + 1);
            wrap.appendChild(inp);
            wrap.appendChild(lab);
            box.appendChild(wrap);
        }
        if (!document.querySelector('input[name="primary_choice"]:checked')) {
            var pick = box.querySelector('input[name="primary_choice"]');
            if (pick) pick.checked = true;
        }
    }
    input.addEventListener('change', render);
})();
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Editar producto';
require __DIR__ . '/../../layouts/admin.php';
