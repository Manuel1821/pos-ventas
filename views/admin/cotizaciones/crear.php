<?php
$customers = $customers ?? [];
$sellers = $sellers ?? [];
$defaultValidFrom = $defaultValidFrom ?? date('Y-m-d');
$basePath = $basePath ?? '';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}
$pageTitle = $pageTitle ?? 'Crear cotización';
ob_start();
?>

<div class="mb-3">
    <a href="<?= htmlspecialchars($basePath . '/admin/cotizaciones', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-3" title="Volver">
        <i class="bi bi-arrow-left"></i>
    </a>
</div>

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1">Crear cotización</h1>
        <p class="text-muted small mb-0">Agrega productos y define vigencia y cliente.</p>
    </div>
</div>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-7">
            <div class="card border-0 card-shadow rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="h6 mb-0 fw-semibold">Productos</h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Próximamente"><i class="bi bi-three-dots"></i></button>
                    </div>
                    <div class="position-relative mb-3">
                        <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted"><i class="bi bi-search"></i></span>
                        <input type="search" id="productSearch" class="form-control ps-5 rounded-3" placeholder="Buscar por nombre, SKU o código de barras" autocomplete="off" />
                        <span class="position-absolute top-50 end-0 translate-middle-y pe-3 text-muted"><i class="bi bi-upc-scan"></i></span>
                    </div>
                    <div id="productResults" class="list-group list-group-flush border rounded-3 mb-3" style="max-height: 220px; overflow-y: auto;"></div>
                    <div class="small text-muted mb-2">Líneas</div>
                    <div id="cartLines" class="border rounded-3 p-2 bg-light" style="min-height:120px;">
                        <div id="cartEmpty" class="text-muted small text-center py-4">Sin productos aún</div>
                    </div>
                </div>
            </div>
            <div class="card border-0 card-shadow rounded-4 mt-3">
                <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Total a pagar</span>
                    <span class="h5 mb-0" id="totalDisplay">$0.00</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5">
            <div class="card border-0 card-shadow rounded-4">
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cliente</label>
                        <select id="customerId" class="form-select rounded-3">
                            <option value="">Selecciona un cliente</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= (int) ($c['id'] ?? 0) ?>"><?= htmlspecialchars((string) ($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Válida desde</label>
                            <input type="date" id="validFrom" class="form-control rounded-3" value="<?= htmlspecialchars($defaultValidFrom, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Válida hasta</label>
                            <input type="date" id="validTo" class="form-control rounded-3" value="">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Vendedor</label>
                        <select id="sellerId" class="form-select rounded-3">
                            <option value="">Selecciona un vendedor</option>
                            <?php foreach ($sellers as $u): ?>
                                <option value="<?= (int) ($u['id'] ?? 0) ?>"><?= htmlspecialchars(trim((string) ($u['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dirección de entrega</label>
                        <textarea id="deliveryAddress" class="form-control rounded-3" rows="2" placeholder="Opcional"></textarea>
                        <div class="small text-muted mt-1" id="deliveryHint">Sin asignar si no escribes nada.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nota al cliente</label>
                        <textarea id="noteToCustomer" class="form-control rounded-3" rows="3" placeholder="Añade detalles de la cotización para tu cliente"></textarea>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="<?= htmlspecialchars($basePath . '/admin/cotizaciones', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary rounded-3">Cancelar</a>
                <button type="button" class="btn btn-primary rounded-3" id="btnCrear"><i class="bi bi-check2 me-1"></i> Crear cotización</button>
            </div>
        </div>
    </div>

<script>
(function(){
    const basePath = <?= json_encode($basePath, JSON_UNESCAPED_UNICODE) ?>;
    const searchUrl = basePath + '/admin/pos/productos/buscar';
    const saveUrl = basePath + '/admin/cotizaciones/guardar';

    /** @type {Array<{product_id:number,name:string,price:number,tax_percent:number,qty:number}>} */
    let cart = [];
    let searchTimer = null;

    function money(n) {
        const x = Number(n);
        if (!isFinite(x)) return '$0.00';
        return '$' + x.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function lineTotal(line) {
        const gross = line.price * line.qty;
        const tax = gross * (line.tax_percent / 100);
        return gross + tax;
    }

    function recalcTotals() {
        let t = 0;
        cart.forEach(function(line) { t += lineTotal(line); });
        document.getElementById('totalDisplay').textContent = money(t);
    }

    function renderCart() {
        const el = document.getElementById('cartLines');
        const empty = document.getElementById('cartEmpty');
        if (cart.length === 0) {
            el.innerHTML = '<div id="cartEmpty" class="text-muted small text-center py-4">Sin productos aún</div>';
            recalcTotals();
            return;
        }
        empty && empty.remove();
        el.innerHTML = cart.map(function(line, idx) {
            return '<div class="d-flex align-items-center gap-2 py-2 border-bottom cart-line" data-idx="' + idx + '">' +
                '<div class="flex-grow-1 small">' +
                    '<div class="fw-semibold">' + escapeHtml(line.name) + '</div>' +
                    '<div class="text-muted">' + money(line.price) + ' c/u · IVA ' + line.tax_percent + '%</div>' +
                '</div>' +
                '<div class="input-group input-group-sm" style="width:110px;">' +
                    '<button type="button" class="btn btn-outline-secondary qty-minus">−</button>' +
                    '<input type="text" class="form-control text-center qty-input" value="' + line.qty + '">' +
                    '<button type="button" class="btn btn-outline-secondary qty-plus">+</button>' +
                '</div>' +
                '<div class="text-end small fw-semibold" style="min-width:72px;">' + money(lineTotal(line)) + '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger line-remove" title="Quitar"><i class="bi bi-trash"></i></button>' +
            '</div>';
        }).join('');

        el.querySelectorAll('.qty-minus').forEach(function(btn, i) {
            btn.addEventListener('click', function() {
                if (cart[i].qty > 0.001) cart[i].qty = Math.max(0.001, round3(cart[i].qty - 1));
                if (cart[i].qty <= 0.001) cart.splice(i, 1);
                renderCart();
            });
        });
        el.querySelectorAll('.qty-plus').forEach(function(btn, i) {
            btn.addEventListener('click', function() {
                cart[i].qty = round3(cart[i].qty + 1);
                renderCart();
            });
        });
        el.querySelectorAll('.qty-input').forEach(function(inp, i) {
            inp.addEventListener('change', function() {
                let v = parseFloat(String(inp.value).replace(',', '.'));
                if (!isFinite(v) || v <= 0) v = 1;
                cart[i].qty = round3(v);
                renderCart();
            });
        });
        el.querySelectorAll('.line-remove').forEach(function(btn, i) {
            btn.addEventListener('click', function() {
                cart.splice(i, 1);
                renderCart();
            });
        });
        recalcTotals();
    }

    function round3(x) {
        return Math.round(x * 1000) / 1000;
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function addProduct(p) {
        const id = parseInt(p.id, 10);
        const existing = cart.find(function(x) { return x.product_id === id; });
        const price = parseFloat(p.price) || 0;
        const tax = parseFloat(p.tax_percent) || 0;
        if (existing) {
            existing.qty = round3(existing.qty + 1);
        } else {
            cart.push({
                product_id: id,
                name: p.name || 'Producto',
                price: price,
                tax_percent: tax,
                qty: 1
            });
        }
        renderCart();
        document.getElementById('productResults').innerHTML = '';
        document.getElementById('productSearch').value = '';
    }

    document.getElementById('productSearch').addEventListener('input', function(e) {
        const q = e.target.value.trim();
        clearTimeout(searchTimer);
        const box = document.getElementById('productResults');
        if (q.length < 2) {
            box.innerHTML = '';
            return;
        }
        searchTimer = setTimeout(function() {
            fetch(searchUrl + '?query=' + encodeURIComponent(q) + '&limit=20', { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    box.innerHTML = '';
                    if (!data.success || !data.items || !data.items.length) {
                        return;
                    }
                    data.items.forEach(function(p) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                        btn.innerHTML = '<span>' + escapeHtml(p.name) + '</span><span class="text-muted small">' + money(p.price) + '</span>';
                        btn.addEventListener('click', function() { addProduct(p); });
                        box.appendChild(btn);
                    });
                });
        }, 250);
    });

    document.getElementById('btnCrear').addEventListener('click', function() {
        if (cart.length === 0) {
            alert('Agrega al menos un producto.');
            return;
        }
        const payload = {
            customer_id: document.getElementById('customerId').value ? parseInt(document.getElementById('customerId').value, 10) : null,
            seller_id: document.getElementById('sellerId').value ? parseInt(document.getElementById('sellerId').value, 10) : null,
            valid_from: document.getElementById('validFrom').value,
            valid_to: document.getElementById('validTo').value || null,
            delivery_address: document.getElementById('deliveryAddress').value.trim() || null,
            note_to_customer: document.getElementById('noteToCustomer').value.trim() || null,
            items: cart.map(function(line) {
                return {
                    product_id: line.product_id,
                    quantity: line.qty,
                    unit_price: line.price
                };
            })
        };
        const btn = document.getElementById('btnCrear');
        btn.disabled = true;
        fetch(saveUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success && res.quotation_id) {
                window.location.href = basePath + '/admin/cotizaciones/documento/' + res.quotation_id;
            } else {
                alert(res.error || 'No se pudo guardar.');
                btn.disabled = false;
            }
        })
        .catch(function() {
            alert('Error de red.');
            btn.disabled = false;
        });
    });
})();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
