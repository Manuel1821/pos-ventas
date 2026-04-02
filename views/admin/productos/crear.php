<?php
$categories = $categories ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$basePath = $basePath ?? '';
ob_start();
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="<?= htmlspecialchars($basePath . '/admin/productos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="h4 mb-0"><i class="bi bi-box me-2" style="color:var(--teal);"></i> Nuevo producto</h1>
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
        <form action="<?= htmlspecialchars($basePath . '/admin/productos/guardar', ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
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
                            <label for="stock" class="form-label">Stock inicial</label>
                            <input type="text" class="form-control" id="stock" name="stock" value="<?= htmlspecialchars($old['stock'] ?? '0', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-6 d-flex align-items-end pb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_inventory_item" name="is_inventory_item" value="1" <?= !empty($old['is_inventory_item']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_inventory_item">Es inventariable</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="mb-3">
                        <label for="productImagesInput" class="form-label">Fotos del producto</label>
                        <input type="file" class="form-control" id="productImagesInput" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                        <div class="form-text">Varias imágenes JPG, PNG, GIF o WebP (máx. 20). Opcional.</div>
                        <div id="productCreatePrimaryChoice" class="mt-2"></div>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Guardar producto</button>
                <a href="<?= htmlspecialchars($basePath . '/admin/productos', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/_barcode_cam_modal.php'; ?>
<script>
(function () {
    var input = document.getElementById('productImagesInput');
    var box = document.getElementById('productCreatePrimaryChoice');
    if (!input || !box) return;
    function render() {
        box.innerHTML = '';
        var files = input.files;
        if (!files || files.length === 0) return;
        var t = document.createElement('div');
        t.className = 'small fw-semibold text-muted mb-1';
        t.textContent = 'Foto principal (POS, listados y catálogo web):';
        box.appendChild(t);
        for (var i = 0; i < files.length; i++) {
            var id = 'pcreate_n_' + i;
            var wrap = document.createElement('div');
            wrap.className = 'form-check';
            var inp = document.createElement('input');
            inp.type = 'radio';
            inp.className = 'form-check-input';
            inp.name = 'primary_choice';
            inp.id = id;
            inp.value = 'n_' + i;
            if (i === 0) inp.checked = true;
            var lab = document.createElement('label');
            lab.className = 'form-check-label';
            lab.htmlFor = id;
            lab.textContent = 'Imagen ' + (i + 1);
            wrap.appendChild(inp);
            wrap.appendChild(lab);
            box.appendChild(wrap);
        }
    }
    input.addEventListener('change', render);
})();
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Nuevo producto';
require __DIR__ . '/../../layouts/admin.php';
