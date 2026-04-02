<?php
$pageTitle = $pageTitle ?? 'Nueva venta';
$hasOpenCash = $hasOpenCash ?? false;
$cashSession = $cashSession ?? null;
$publicCustomer = $publicCustomer ?? null;

ob_start();
?>

<style>
    .pos-sale-page { --pos-radius: 14px; }
    .pos-trial-banner {
        background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%);
        color: rgba(255,255,255,.95);
        border-radius: var(--pos-radius);
        padding: 14px 18px;
        margin-bottom: 1.25rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .pos-trial-banner .progress { height: 6px; background: rgba(255,255,255,.12); }
    .pos-trial-banner .progress-bar { background: #14b8a6; }
    .pos-search-card, .pos-cart-panel {
        border-radius: var(--pos-radius);
    }
    .pos-quick-card {
        border-radius: 12px;
        transition: transform .12s ease, box-shadow .12s ease;
        cursor: pointer;
        border: 1px solid rgba(15,23,42,.08) !important;
    }
    .pos-quick-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(15,23,42,.08) !important;
    }
    .pos-cart-lines { max-height: min(52vh, 420px); overflow-y: auto; }
    .pos-cart-line {
        border-bottom: 1px solid rgba(15,23,42,.06);
        padding: 10px 0;
    }
    .pos-cart-line:last-child { border-bottom: none; }
    .pos-sale-page .dropdown-menu { z-index: 1080; }
    .pos-cart-thumb {
        width: 48px; height: 48px;
        object-fit: cover;
        border-radius: 10px;
        background: #f1f5f9;
        flex-shrink: 0;
    }
    .pos-mode-toggle .btn.active {
        background: #fff;
        box-shadow: 0 1px 4px rgba(15,23,42,.08);
        font-weight: 600;
    }
    .pos-mode-toggle .btn:not(.active) {
        background: #e2e8f0;
        color: #64748b;
        border: none;
    }
    .pos-cobrar-btn {
        font-weight: 700;
        letter-spacing: .02em;
        padding-top: .85rem;
        padding-bottom: .85rem;
    }
    #paymentModal .payment-mode-btn.active {
        background: rgba(20,184,166,.08) !important;
        border-color: rgba(20,184,166,.45) !important;
    }
    @media (max-width: 991.98px) {
        .pos-cart-lines { max-height: none; }
    }
</style>

<div class="pos-sale-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0"><i class="bi bi-basket me-2" style="color:var(--teal);"></i> Nueva venta</h1>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <?php if (!$hasOpenCash): ?>
                <a href="<?= htmlspecialchars($basePath . '/admin/caja/apertura', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-box-arrow-in-down-right"></i> Abrir caja
                </a>
            <?php else: ?>
                <span class="badge rounded-pill" style="background:rgba(20,184,166,.12);color:#0f766e;border:1px solid rgba(20,184,166,.25);">
                    <i class="bi bi-cash-coin"></i> Caja abierta
                </span>
                <a href="<?= htmlspecialchars($basePath . '/admin/caja/corte-cajero', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm" title="Corte por cajero e imprimir ticket">
                    <i class="bi bi-person-badge"></i> Corte cajero
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($flash) && isset($flash['type'], $flash['message'])): ?>
        <div class="mb-3">
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show card-shadow" role="alert">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$hasOpenCash): ?>
        <div class="alert alert-warning card-shadow rounded-4 mb-3" role="alert">
            <div class="d-flex align-items-start gap-2">
                <i class="bi bi-exclamation-triangle fs-4" style="color:#f59e0b;"></i>
                <div>
                    <div class="fw-semibold mb-1">No hay caja abierta</div>
                    <div class="small text-muted">Para confirmar una venta se requiere una sesión de caja activa.</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="pos-trial-banner">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <div class="fw-semibold">Te quedan 14 días de prueba</div>
                <div class="small" style="opacity:.85;">Explora todas las funciones del POS sin compromiso.</div>
            </div>
            <div style="min-width: 140px; flex: 1;">
                <div class="progress rounded-pill">
                    <div class="progress-bar rounded-pill" style="width: 60%;" role="progressbar"></div>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm text-white border border-light border-opacity-25" style="background:rgba(255,255,255,.08);" disabled title="Próximamente">
            Elegir mi Plan
        </button>
    </div>

    <div class="row g-4 align-items-start">
        <!-- Izquierda: búsqueda y acciones -->
        <div class="col-12 col-xl-7">
            <div class="card border-0 card-shadow pos-search-card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3 fw-semibold">Buscar productos</h2>
                    <div class="position-relative mb-3">
                        <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted"><i class="bi bi-upc-scan"></i></span>
                        <input id="productSearch" class="form-control form-control-lg ps-5 rounded-3" placeholder="Buscar por nombre, SKU o código de barras" autocomplete="off" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1" for="barcodeInput">Lector / escaneo</label>
                        <div class="input-group">
                            <input id="barcodeInput" class="form-control rounded-3" placeholder="Escanea y presiona Enter" autocomplete="off" />
                            <button class="btn btn-outline-secondary rounded-3" type="button" id="openCameraScannerBtn" title="Usar cámara">
                                <i class="bi bi-camera"></i>
                            </button>
                        </div>
                        <div class="small text-muted mt-1" id="scannerHint">Compatible con lectores USB/Bluetooth tipo teclado.</div>
                    </div>

                    <div class="mb-4">
                        <div class="small text-muted mb-2">Resultados</div>
                        <div id="productResults" class="list-group list-group-flush border rounded-3 overflow-auto" style="max-height: 320px;">
                        </div>
                    </div>

                    <h3 class="h6 fw-semibold mb-3">Acciones rápidas</h3>
                    <div class="row g-2 row-cols-2 row-cols-md-3">
                        <div class="col">
                            <a href="<?= htmlspecialchars($basePath . '/admin/productos/crear', ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-dark">
                                <div class="card pos-quick-card h-100 shadow-sm">
                                    <div class="card-body text-center py-3 px-2">
                                        <i class="bi bi-plus-circle fs-3" style="color:#22c55e;"></i>
                                        <div class="small fw-semibold mt-2">Crear producto</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <button type="button" class="card pos-quick-card h-100 w-100 shadow-sm bg-white" id="quickDiscountBtn">
                                <div class="card-body text-center py-3 px-2">
                                    <i class="bi bi-tag fs-3" style="color:#3b82f6;"></i>
                                    <div class="small fw-semibold mt-2">Añadir descuento</div>
                                </div>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="card pos-quick-card h-100 w-100 shadow-sm bg-white" id="quickBarcodeBtn">
                                <div class="card-body text-center py-3 px-2">
                                    <i class="bi bi-calculator fs-3" style="color:#6366f1;"></i>
                                    <div class="small fw-semibold mt-2">Entrada manual</div>
                                </div>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="card pos-quick-card h-100 w-100 shadow-sm bg-white" id="quickCustomerBtn">
                                <div class="card-body text-center py-3 px-2">
                                    <i class="bi bi-person-plus fs-3" style="color:#ef4444;"></i>
                                    <div class="small fw-semibold mt-2">Añadir cliente</div>
                                </div>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="card pos-quick-card h-100 w-100 shadow-sm bg-white" id="quickSellerBtn" title="Próximamente">
                                <div class="card-body text-center py-3 px-2">
                                    <i class="bi bi-person-badge fs-3" style="color:#eab308;"></i>
                                    <div class="small fw-semibold mt-2">Añadir vendedor</div>
                                </div>
                            </button>
                        </div>
                        <div class="col">
                            <button type="button" class="card pos-quick-card h-100 w-100 shadow-sm bg-white position-relative" id="quickServicesBtn" title="Próximamente">
                                <span class="position-absolute top-0 end-0 badge rounded-pill bg-primary m-2" style="font-size:9px;">Nuevo</span>
                                <div class="card-body text-center py-3 px-2">
                                    <i class="bi bi-phone fs-3" style="color:#0ea5e9;"></i>
                                    <div class="small fw-semibold mt-2">Recargas y servicios</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Derecha: carrito y cobro -->
        <div class="col-12 col-xl-5">
            <div class="card border-0 card-shadow pos-cart-panel h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
                                <i class="bi bi-receipt me-1"></i> Nueva venta
                            </span>
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" id="clearCartBtn" disabled title="Vaciar carrito">
                                <i class="bi bi-trash"></i>
                            </button>
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" id="cartMoreBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Más opciones">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end rounded-3 shadow" aria-labelledby="cartMoreBtn">
                                    <li><button type="button" class="dropdown-item" id="dropdownResetDiscount"><i class="bi bi-x-circle me-2"></i> Quitar descuento global</button></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="btn-group w-100 pos-mode-toggle mb-3 p-1 rounded-3" style="background:#e2e8f0;">
                        <button type="button" class="btn btn-sm rounded-3 active" id="posModeVenta"><i class="bi bi-cash-stack me-1"></i> Venta</button>
                        <button type="button" class="btn btn-sm rounded-3" id="posModePedido" disabled data-bs-toggle="tooltip" data-bs-placement="top" title="Próximamente"><i class="bi bi-truck me-1"></i> Pedido</button>
                    </div>

                    <div id="cartLines" class="pos-cart-lines mb-3">
                        <div class="text-center text-muted py-5" id="cartEmptyState">Agrega productos para comenzar.</div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted mb-1">Cliente</div>
                        <div class="d-flex gap-2 align-items-center flex-wrap mb-2">
                            <span class="badge bg-light text-dark border px-3 py-2" id="selectedCustomerBadge">
                                <?= htmlspecialchars($publicCustomer ? (string) $publicCustomer['name'] : 'Cliente genérico', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <label class="form-label small text-muted mb-1" for="customerSearch">Buscar cliente (opcional)</label>
                        <input id="customerSearch" class="form-control form-control-sm rounded-3" placeholder="Nombre, teléfono o correo" autocomplete="off" />
                        <div id="customerResults" class="list-group mt-2 overflow-auto rounded-3 border" style="max-height: 140px; display:none;"></div>
                    </div>

                    <div class="mb-3" id="discountPanel">
                        <label class="form-label small text-muted" for="discountPercent">Descuento % (sobre subtotal)</label>
                        <div class="input-group input-group-sm">
                            <input id="discountPercent" type="number" class="form-control rounded-3" min="0" max="100" step="0.01" value="0" />
                            <button class="btn btn-outline-secondary rounded-3" id="applyDiscountBtn" type="button"><i class="bi bi-check2"></i></button>
                        </div>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none px-0 d-none" id="resetDiscountBtn" disabled>Quitar descuento</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted" for="saleNotes">Observaciones (opcional)</label>
                        <textarea id="saleNotes" class="form-control form-control-sm rounded-3" rows="2" placeholder="Ej: sin cambio / dejar en recepción"></textarea>
                    </div>

                    <div class="mb-3 pb-2 border-top pt-3">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted" id="subtotalLabel">Subtotal (0)</span>
                            <span class="fw-semibold" id="subtotalValue">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Descuento</span>
                            <span class="fw-semibold text-danger" id="discountValue">-$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Impuesto</span>
                            <span class="fw-semibold" id="taxValue">$0.00</span>
                        </div>
                        <hr class="my-2" />
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Total</span>
                            <span class="fw-bold fs-4" style="color:var(--teal);" id="totalValue">$0.00</span>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 pos-cobrar-btn rounded-3" id="openPaymentBtn" type="button" disabled>
                        Cobrar $0.00
                    </button>
                    <div class="text-muted small mt-2 text-center" id="payHint">Habilitado con caja abierta y productos en el carrito.</div>
                    <div class="text-muted small mt-1 text-center" id="posDebug" style="min-height:18px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal escáner por cámara -->
<div class="modal fade" id="cameraScannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upc-scan me-2"></i> Escanear con cámara</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="cameraScannerStatus" class="small text-muted mb-2">Iniciando cámara...</div>
                <video id="cameraScannerVideo" playsinline autoplay muted style="width:100%; border-radius:12px; background:#0f172a;"></video>
                <div class="small text-muted mt-2">Apunta al código de barras. Se agregará al carrito al detectarlo.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal precio por línea -->
<div class="modal fade" id="linePriceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-currency-dollar me-2"></i> Cambiar precio</h5>
                <button type="button" class="btn-close" id="linePriceModalCloseX" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted small mb-2" id="linePriceProductName"></p>
                <label class="form-label" for="linePriceInput">Precio unitario</label>
                <input type="number" id="linePriceInput" class="form-control form-control-lg rounded-3" min="0" step="0.01" />
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-3" id="linePriceCancelBtn">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-3" id="linePriceSaveBtn">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal descuento por línea -->
<div class="modal fade" id="lineDiscountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-tag me-2"></i> Añadir descuento</h5>
                <button type="button" class="btn-close" id="lineDiscountModalCloseX" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted small mb-2" id="lineDiscountProductName"></p>
                <label class="form-label" for="lineDiscountInput">Descuento sobre precio de lista (%)</label>
                <input type="number" id="lineDiscountInput" class="form-control form-control-lg rounded-3" min="0" max="100" step="0.01" value="0" />
                <div class="form-text">Se aplica al precio del catálogo y fija un precio unitario efectivo en esta línea.</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-3" id="lineDiscountCancelBtn">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-3" id="lineDiscountSaveBtn">Aplicar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle producto -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i> Detalle del producto</h5>
                <button type="button" class="btn-close" id="productDetailModalCloseX" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2" id="productDetailBody"></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-3" id="productDetailCloseBtn">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de cobro -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="paymentModalTitle">Registrar venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="text-center mb-3">
                    <div class="text-muted small">Total a cobrar</div>
                    <div class="fs-2 fw-bold" style="color:var(--teal);" id="dueAmount">$0.00</div>
                </div>
                <div id="paymentError" class="mb-2"></div>

                <div class="small text-muted mb-2">Forma de pago</div>
                <div class="d-flex flex-column gap-2" id="paymentMethodList">
                    <button type="button" class="btn text-start d-flex align-items-center gap-3 px-3 py-3 rounded-3 border bg-white payment-mode-btn active" data-payment-mode="efectivo">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width:42px;height:42px;"><i class="bi bi-cash-stack fs-5"></i></span>
                        <span class="fw-semibold flex-grow-1">Efectivo</span>
                        <i class="bi bi-chevron-down text-muted"></i>
                    </button>
                    <button type="button" class="btn text-start d-flex align-items-center gap-3 px-3 py-3 rounded-3 border bg-white payment-mode-btn" data-payment-mode="credito">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success" style="width:42px;height:42px;"><i class="bi bi-bank fs-5"></i></span>
                        <span class="fw-semibold flex-grow-1">A crédito</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>
                    <button type="button" class="btn text-start d-flex align-items-center gap-3 px-3 py-3 rounded-3 border bg-white payment-mode-btn" data-payment-mode="tarjeta_credito">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning" style="width:42px;height:42px;"><i class="bi bi-credit-card fs-5"></i></span>
                        <span class="fw-semibold flex-grow-1">Tarjeta de crédito</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>
                    <button type="button" class="btn text-start d-flex align-items-center gap-3 px-3 py-3 rounded-3 border bg-white payment-mode-btn" data-payment-mode="tarjeta_debito">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info" style="width:42px;height:42px;"><i class="bi bi-credit-card-2-front fs-5"></i></span>
                        <span class="fw-semibold flex-grow-1">Tarjeta de débito</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>
                    <button type="button" class="btn text-start d-flex align-items-center gap-3 px-3 py-3 rounded-3 border bg-white payment-mode-btn" data-payment-mode="transferencia">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger" style="width:42px;height:42px;"><i class="bi bi-arrow-left-right fs-5"></i></span>
                        <span class="fw-semibold flex-grow-1">Transferencia</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>
                    <button type="button" class="btn text-start d-flex align-items-center gap-3 px-3 py-3 rounded-3 border bg-white payment-mode-btn" data-payment-mode="multiple">
                        <span class="rounded-3 d-inline-flex align-items-center justify-content-center text-white" style="width:42px;height:42px;background:linear-gradient(135deg,#14b8a6,#0d9488);"><i class="bi bi-intersect fs-5"></i></span>
                        <span class="fw-semibold flex-grow-1">Usar múltiples métodos</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </button>
                </div>

                <div id="paymentDetailEfectivo" class="mt-3 p-3 rounded-3 bg-light border">
                    <label class="form-label small text-muted mb-1" for="tenderedAmount">Importe recibido</label>
                    <div class="input-group input-group-lg mb-2">
                        <span class="input-group-text rounded-start-3">$</span>
                        <input id="tenderedAmount" type="number" class="form-control rounded-end-3" min="0" step="0.01" value="0" />
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-sm rounded-pill btn-outline-secondary quick-pay-btn" data-quick-pay="exact">Exacto <span id="quickExactLabel"></span></button>
                        <button type="button" class="btn btn-sm rounded-pill btn-outline-secondary quick-pay-btn" data-quick-pay="round50" id="quickPayRound50Btn" data-amount="0"><span id="quickPayRound50Label">$0.00</span></button>
                        <button type="button" class="btn btn-sm rounded-pill btn-outline-secondary quick-pay-btn" data-quick-pay="round100" id="quickPayRound100Btn" data-amount="0"><span id="quickPayRound100Label">$0.00</span></button>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted" id="paymentBalanceLabel">Cambio</span>
                        <span class="fw-semibold" id="changeAmount">$0.00</span>
                    </div>
                </div>

                <div id="paymentDetailCredito" class="mt-3 p-3 rounded-3 bg-light border d-none">
                    <div class="mb-3 p-2 rounded-3 border bg-white" id="creditoCustomerPanel">
                        <div class="small text-muted mb-1">Cliente en esta venta</div>
                        <div id="creditoCustomerDisplay" class="small"></div>
                    </div>
                    <p class="small text-muted mb-2">Venta a crédito: puedes registrar un anticipo en efectivo (opcional).</p>
                    <label class="form-label small text-muted" for="tenderedCreditoAmount">Anticipo recibido (opcional)</label>
                    <div class="input-group mb-2">
                        <span class="input-group-text">$</span>
                        <input id="tenderedCreditoAmount" type="number" class="form-control" min="0" step="0.01" value="0" />
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">Saldo pendiente</span>
                        <span class="fw-semibold" id="creditoPendingAmount">$0.00</span>
                    </div>
                </div>

                <div id="paymentDetailSimple" class="mt-3 p-3 rounded-3 bg-light border d-none">
                    <p class="small text-muted mb-2" id="paymentSimpleHint">Se registrará el cobro por el total mostrado arriba.</p>
                    <div id="paymentSimpleFolioWrap" class="d-none">
                        <label class="form-label small text-muted mb-1" for="paymentFolioSimple">Folio / autorización</label>
                        <input type="text" id="paymentFolioSimple" class="form-control" maxlength="120" autocomplete="off" placeholder="Opcional" />
                    </div>
                    <div id="paymentTransferenciaWrap" class="d-none">
                        <label class="form-label small text-muted mb-1" for="paymentRefTransferencia">Folio o referencia</label>
                        <textarea id="paymentRefTransferencia" class="form-control" rows="2" maxlength="300" placeholder="Folio de transferencia u observación (opcional)"></textarea>
                    </div>
                </div>

                <div id="paymentDetailMultiple" class="mt-3 p-3 rounded-3 bg-light border d-none">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted" for="multiMethod1">Método 1</label>
                            <select id="multiMethod1" class="form-select form-select-sm">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TARJETA_CREDITO">Tarjeta crédito</option>
                                <option value="TARJETA_DEBITO">Tarjeta débito</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted" for="multiAmount1">Importe</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" id="multiAmount1" class="form-control" min="0" step="0.01" value="0" />
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted" for="multiMethod2">Método 2</label>
                            <select id="multiMethod2" class="form-select form-select-sm">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TARJETA_CREDITO">Tarjeta crédito</option>
                                <option value="TARJETA_DEBITO">Tarjeta débito</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small text-muted" for="multiAmount2">Importe</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" id="multiAmount2" class="form-control" min="0" step="0.01" value="0" />
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label small text-muted mb-1" for="paymentObsMultiple">Observaciones del pago</label>
                        <textarea id="paymentObsMultiple" class="form-control" rows="2" maxlength="500" placeholder="Opcional"></textarea>
                    </div>
                    <div class="small text-muted mt-2">La suma de ambos importes debe igualar el total a cobrar.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-3" id="confirmPaymentBtn">Confirmar venta</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ticket -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-printer me-2"></i> Ticket</h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3" id="printTicketBtn">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="ticketPrintArea" class="border rounded-3 p-2" style="background:#fff;"></div>
                <div class="mt-3 text-muted small">Puedes reimprimir desde el módulo de ventas.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary rounded-3" id="newSaleBtn">
                    <i class="bi bi-basket me-2"></i> Nueva venta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Área exclusiva para impresión (evita duplicados del modal en preview) -->
<div id="ticketPrintOnlyRoot" style="display:none;"></div>

<style>
    @media print {
        body * { visibility: hidden !important; }
        #ticketPrintOnlyRoot, #ticketPrintOnlyRoot * { visibility: visible !important; }
        #ticketPrintOnlyRoot {
            display: block !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            max-width: 100%;
            background: #fff !important;
            color: #000 !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        #ticketPrintOnlyRoot .pos-ticket {
            margin: 0 !important;
            max-width: none !important;
            width: 100% !important;
        }
        @page { size: auto; margin: 1.2mm; }
    }
</style>

<script>
    (function () {
        const basePath = <?= json_encode($basePath ?? '', JSON_UNESCAPED_UNICODE) ?>;
        const endpoints = {
            buscarProductos: basePath + '/admin/pos/productos/buscar',
            buscarClientes: basePath + '/admin/pos/clientes/buscar',
            confirmarVenta: basePath + '/admin/pos/nueva-venta/confirmar'
        };

        const hasOpenCash = <?= $hasOpenCash ? 'true' : 'false' ?>;
        const publicCustomer = <?= json_encode($publicCustomer ?? null, JSON_UNESCAPED_UNICODE) ?>;

        const round2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;
        const round3 = (n) => Math.round((n + Number.EPSILON) * 1000) / 1000;

        const cart = new Map();
        let selectedCustomer = publicCustomer && publicCustomer.id ? publicCustomer : null;

        let pendingLineProductId = null;

        const ui = {
            productSearch: document.getElementById('productSearch'),
            barcodeInput: document.getElementById('barcodeInput'),
            openCameraScannerBtn: document.getElementById('openCameraScannerBtn'),
            scannerHint: document.getElementById('scannerHint'),
            productResults: document.getElementById('productResults'),
            cartLines: document.getElementById('cartLines'),
            cartEmptyState: document.getElementById('cartEmptyState'),
            cartCount: null,
            clearCartBtn: document.getElementById('clearCartBtn'),
            resetDiscountBtn: document.getElementById('resetDiscountBtn'),
            subtotalLabel: document.getElementById('subtotalLabel'),
            subtotalValue: document.getElementById('subtotalValue'),
            discountValue: document.getElementById('discountValue'),
            taxValue: document.getElementById('taxValue'),
            totalValue: document.getElementById('totalValue'),
            openPaymentBtn: document.getElementById('openPaymentBtn'),
            payHint: document.getElementById('payHint'),
            posDebug: document.getElementById('posDebug'),
            discountPercent: document.getElementById('discountPercent'),
            applyDiscountBtn: document.getElementById('applyDiscountBtn'),
            customerSearch: document.getElementById('customerSearch'),
            customerResults: document.getElementById('customerResults'),
            selectedCustomerBadge: document.getElementById('selectedCustomerBadge'),
            saleNotes: document.getElementById('saleNotes'),
            paymentError: document.getElementById('paymentError'),
            dueAmount: document.getElementById('dueAmount'),
            tenderedAmount: document.getElementById('tenderedAmount'),
            changeAmount: document.getElementById('changeAmount'),
            paymentBalanceLabel: document.getElementById('paymentBalanceLabel'),
            confirmPaymentBtn: document.getElementById('confirmPaymentBtn'),
            ticketPrintArea: document.getElementById('ticketPrintArea'),
            printTicketBtn: document.getElementById('printTicketBtn'),
            newSaleBtn: document.getElementById('newSaleBtn'),
            cameraScannerStatus: document.getElementById('cameraScannerStatus'),
            cameraScannerVideo: document.getElementById('cameraScannerVideo'),
            quickDiscountBtn: document.getElementById('quickDiscountBtn'),
            quickBarcodeBtn: document.getElementById('quickBarcodeBtn'),
            quickCustomerBtn: document.getElementById('quickCustomerBtn'),
            quickSellerBtn: document.getElementById('quickSellerBtn'),
            quickServicesBtn: document.getElementById('quickServicesBtn'),
            dropdownResetDiscount: document.getElementById('dropdownResetDiscount'),
            linePriceModalEl: document.getElementById('linePriceModal'),
            linePriceProductName: document.getElementById('linePriceProductName'),
            linePriceInput: document.getElementById('linePriceInput'),
            linePriceSaveBtn: document.getElementById('linePriceSaveBtn'),
            lineDiscountModalEl: document.getElementById('lineDiscountModal'),
            lineDiscountProductName: document.getElementById('lineDiscountProductName'),
            lineDiscountInput: document.getElementById('lineDiscountInput'),
            lineDiscountSaveBtn: document.getElementById('lineDiscountSaveBtn'),
            productDetailModalEl: document.getElementById('productDetailModal'),
            productDetailBody: document.getElementById('productDetailBody'),
        };

        let currentTotals = { subtotal: 0, discountTotal: 0, taxTotal: 0, totalDue: 0, paidTotal: 0, change: 0 };
        let activePaymentMode = 'efectivo';

        if (ui.posDebug) ui.posDebug.textContent = 'JS inicializado';

        function currency(n) {
            const val = Number(n) || 0;
            return '$' + val.toFixed(2);
        }

        const nextRoundedUp = (total, step) => {
            const t = Math.max(0, Number(total) || 0);
            const s = Math.max(0.01, step);
            let c = Math.ceil(t / s) * s;
            if (c <= t + 1e-9) c += s;
            return round2(c);
        };
        function getQuickPaySuggestions(total) {
            const t = round2(Number(total) || 0);
            let a50 = nextRoundedUp(t, 50);
            let a100 = nextRoundedUp(t, 100);
            if (Math.abs(a50 - a100) < 0.02) {
                a100 = round2(a100 + 50);
            }
            return { a50, a100 };
        }
        function updateQuickPayButtons() {
            const total = round2(Number(currentTotals.totalDue) || 0);
            const { a50, a100 } = getQuickPaySuggestions(total);
            const b50 = document.getElementById('quickPayRound50Btn');
            const b100 = document.getElementById('quickPayRound100Btn');
            const l50 = document.getElementById('quickPayRound50Label');
            const l100 = document.getElementById('quickPayRound100Label');
            if (b50) b50.dataset.amount = String(a50);
            if (b100) b100.dataset.amount = String(a100);
            if (l50) l50.textContent = currency(a50);
            if (l100) l100.textContent = currency(a100);
        }

        function getPaymentConditionFromMode() {
            return activePaymentMode === 'credito' ? 'CREDITO' : 'CONTADO';
        }

        function setActivePaymentMode(mode) {
            activePaymentMode = mode;
            document.querySelectorAll('#paymentModal .payment-mode-btn').forEach((btn) => {
                const m = btn.getAttribute('data-payment-mode');
                btn.classList.toggle('active', m === mode);
            });

            const dEf = document.getElementById('paymentDetailEfectivo');
            const dCr = document.getElementById('paymentDetailCredito');
            const dSi = document.getElementById('paymentDetailSimple');
            const dMu = document.getElementById('paymentDetailMultiple');
            if (dEf) dEf.classList.toggle('d-none', mode !== 'efectivo');
            if (dCr) dCr.classList.toggle('d-none', mode !== 'credito');
            if (dMu) dMu.classList.toggle('d-none', mode !== 'multiple');

            const simpleModes = ['tarjeta_credito', 'tarjeta_debito', 'transferencia'];
            if (dSi) {
                dSi.classList.toggle('d-none', !simpleModes.includes(mode));
                const hint = document.getElementById('paymentSimpleHint');
                if (hint) {
                    const labels = {
                        tarjeta_credito: 'Se registrará el cobro con tarjeta de crédito por el total indicado arriba.',
                        tarjeta_debito: 'Se registrará el cobro con tarjeta de débito por el total indicado arriba.',
                        transferencia: 'Se registrará el cobro por transferencia por el total indicado arriba.'
                    };
                    hint.textContent = labels[mode] || '';
                }
            }
            updateSimplePaymentFieldsVisibility();
            if (mode === 'credito') {
                updateCreditoCustomerDisplay();
            }
            updatePaymentBalanceUI();
        }

        function updateSimplePaymentFieldsVisibility() {
            const mode = activePaymentMode;
            const fw = document.getElementById('paymentSimpleFolioWrap');
            const tw = document.getElementById('paymentTransferenciaWrap');
            if (!fw || !tw) return;
            if (mode === 'tarjeta_credito' || mode === 'tarjeta_debito') {
                fw.classList.remove('d-none');
                tw.classList.add('d-none');
            } else if (mode === 'transferencia') {
                fw.classList.add('d-none');
                tw.classList.remove('d-none');
            } else {
                fw.classList.add('d-none');
                tw.classList.add('d-none');
            }
        }

        function updateCreditoCustomerDisplay() {
            const el = document.getElementById('creditoCustomerDisplay');
            if (!el) return;
            const c = selectedCustomer;
            if (!c || !c.id) {
                el.innerHTML = '<span class="text-danger">Selecciona un cliente con <b>Añadir cliente</b> antes de cobrar a crédito.</span>';
                return;
            }
            if (Number(c.is_public) === 1) {
                el.innerHTML = '<span class="text-warning">Para venta a crédito debes elegir un cliente de la lista (no el cliente genérico).</span>';
                return;
            }
            el.innerHTML = '<span class="fw-semibold">' + escapeHtml(String(c.name || 'Cliente')) + '</span>'
                + (c.phone ? '<div class="text-muted">' + escapeHtml(String(c.phone)) + '</div>' : '');
        }

        function updatePaymentBalanceUI() {
            const totalDue = Number(currentTotals.totalDue) || 0;

            if (activePaymentMode === 'efectivo') {
                if (ui.paymentBalanceLabel) ui.paymentBalanceLabel.textContent = 'Cambio';
                const tendered = round2(Number(ui.tenderedAmount && ui.tenderedAmount.value) || 0);
                const change = round2(Math.max(0, tendered - totalDue));
                if (ui.changeAmount) ui.changeAmount.textContent = currency(change);
            } else if (activePaymentMode === 'credito') {
                const t = round2(Number(document.getElementById('tenderedCreditoAmount')?.value) || 0);
                const pending = round2(Math.max(0, totalDue - t));
                const elP = document.getElementById('creditoPendingAmount');
                if (elP) elP.textContent = currency(pending);
            }
        }

        function effectiveUnitPrice(item) {
            if (item.unitPriceOverride != null && isFinite(Number(item.unitPriceOverride))) {
                return round2(Number(item.unitPriceOverride));
            }
            return round2(Number(item.product.price) || 0);
        }

        function cartLineCount() {
            return Array.from(cart.values()).filter(v => v.quantity > 0).length;
        }

        function updateSubtotalLabel() {
            if (ui.subtotalLabel) {
                const c = cartLineCount();
                ui.subtotalLabel.textContent = 'Subtotal (' + c + ')';
            }
        }

        function computeTotals() {
            const lines = Array.from(cart.values()).map(v => {
                const unit = effectiveUnitPrice(v);
                const gross = round2(unit * Number(v.quantity));
                return {
                    product_id: v.product.id,
                    line_gross: gross,
                    quantity: v.quantity,
                    tax_percent: Number(v.product.tax_percent) || 0
                };
            });

            const subtotal = round2(lines.reduce((acc, l) => acc + l.line_gross, 0));

            const discountPercent = Number(ui.discountPercent.value) || 0;
            const clampedDiscountPercent = Math.max(0, Math.min(100, discountPercent));
            const discountTotal = round2(subtotal * clampedDiscountPercent / 100);

            let taxTotal = 0;
            let discountRemaining = discountTotal;
            for (let i = 0; i < lines.length; i++) {
                const ln = lines[i];
                const isLast = i === lines.length - 1;
                let allocDiscount = 0;
                if (isLast) {
                    allocDiscount = discountRemaining;
                } else if (subtotal > 0 && discountTotal > 0) {
                    allocDiscount = round2((ln.line_gross / subtotal) * discountTotal);
                    allocDiscount = Math.max(0, Math.min(allocDiscount, discountRemaining));
                }
                discountRemaining = round2(discountRemaining - allocDiscount);
                const net = round2(ln.line_gross - allocDiscount);
                const taxLine = round2(net * (ln.tax_percent / 100));
                taxTotal = round2(taxTotal + taxLine);
            }

            const totalDue = round2((subtotal - discountTotal) + taxTotal);
            currentTotals = { subtotal, discountTotal, taxTotal, totalDue, paidTotal: 0, change: 0 };

            ui.subtotalValue.textContent = currency(subtotal);
            ui.discountValue.textContent = '-' + currency(discountTotal);
            ui.taxValue.textContent = currency(taxTotal);
            ui.totalValue.textContent = currency(totalDue);
            if (ui.openPaymentBtn) {
                ui.openPaymentBtn.innerHTML = 'Cobrar ' + currency(totalDue);
            }

            ui.openPaymentBtn.disabled = !hasOpenCash || cart.size === 0 || totalDue <= 0;
            ui.payHint.textContent = (!hasOpenCash)
                ? 'Caja cerrada.'
                : (cart.size === 0 ? 'Carrito vacío.' : 'Listo para cobrar.');
            ui.resetDiscountBtn.disabled = clampedDiscountPercent <= 0;
            ui.resetDiscountBtn.classList.toggle('d-none', clampedDiscountPercent <= 0);
            updateSubtotalLabel();
            const qel = document.getElementById('quickExactLabel');
            if (qel) qel.textContent = currency(totalDue);
            updateQuickPayButtons();
            updatePaymentBalanceUI();
        }

        function productImageUrl(product) {
            if (product.image_path) {
                return basePath + '/admin/productos/' + encodeURIComponent(String(product.id)) + '/imagen-miniatura';
            }
            return '';
        }

        /** Evita que el menú se recorte dentro de .pos-cart-lines (overflow); Popper en modo fixed. */
        function initLineDropdowns() {
            if (!window.bootstrap || !bootstrap.Dropdown) return;
            ui.cartLines.querySelectorAll('.pos-cart-line-dropdown-toggle').forEach((btn) => {
                try {
                    const prev = bootstrap.Dropdown.getInstance(btn);
                    if (prev) prev.dispose();
                    new bootstrap.Dropdown(btn, {
                        popperConfig: {
                            strategy: 'fixed'
                        }
                    });
                } catch (e) {}
            });
        }

        function renderCart() {
            if (cart.size === 0) {
                if (ui.cartEmptyState) {
                    ui.cartEmptyState.style.display = 'block';
                    ui.cartEmptyState.textContent = 'Agrega productos para comenzar.';
                }
                ui.cartLines.querySelectorAll('.pos-cart-line').forEach(el => el.remove());
            } else {
                if (ui.cartEmptyState) ui.cartEmptyState.style.display = 'none';
                const rows = Array.from(cart.values()).map(({ product, quantity, unitPriceOverride }) => {
                    const unit = effectiveUnitPrice({ product, quantity, unitPriceOverride });
                    const gross = round2(unit * Number(quantity));
                    const qtyStr = String(quantity);
                    const maxStock = Math.max(0, Math.floor(Number(product.stock) || 0));
                    const maxAttr = (Number(product.is_inventory_item) === 1) ? (' max="' + maxStock + '"') : '';
                    const disabledQty = (Number(product.is_inventory_item) === 1 && Number(product.stock) <= 0);
                    const imgSrc = productImageUrl(product);
                    const thumb = imgSrc
                        ? `<img class="pos-cart-thumb" src="${escapeAttribute(imgSrc)}" alt="" loading="lazy" />`
                        : `<div class="pos-cart-thumb d-flex align-items-center justify-content-center text-muted small"><i class="bi bi-image"></i></div>`;
                    const pid = Number(product.id);
                    return `
                        <div class="pos-cart-line d-flex align-items-center gap-2" data-product-id="${pid}">
                            ${thumb}
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate">${escapeHtml(product.name || '')}</div>
                                <div class="small text-muted">${currency(unit)} · ${escapeHtml(product.sku ? 'SKU: ' + product.sku : '')}</div>
                            </div>
                            <div style="width:88px;">
                                <input type="number" step="1" min="1" ${maxAttr}
                                    class="form-control form-control-sm text-end cartQtyInput rounded-2"
                                    data-product-id="${pid}" value="${escapeAttribute(qtyStr)}" ${disabledQty ? 'disabled' : ''} />
                            </div>
                            <div class="text-end" style="min-width:72px;">
                                <div class="fw-semibold small">${currency(gross)}</div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm rounded-3 pos-cart-line-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Más">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-3 py-1">
                                    <li><button type="button" class="dropdown-item pos-line-action" data-action="price" data-product-id="${pid}"><i class="bi bi-currency-dollar me-2"></i>Cambiar precio</button></li>
                                    <li><button type="button" class="dropdown-item pos-line-action" data-action="discount" data-product-id="${pid}"><i class="bi bi-tag me-2"></i>Añadir descuento</button></li>
                                    <li><button type="button" class="dropdown-item pos-line-action" data-action="detail" data-product-id="${pid}"><i class="bi bi-eye me-2"></i>Ver detalles</button></li>
                                    <li><a class="dropdown-item" href="${escapeAttribute(basePath + '/admin/productos/editar/' + pid)}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2"></i>Editar producto</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><button type="button" class="dropdown-item text-danger pos-line-action" data-action="remove" data-product-id="${pid}"><i class="bi bi-trash me-2"></i>Eliminar producto</button></li>
                                </ul>
                            </div>
                        </div>
                    `;
                }).join('');
                ui.cartLines.querySelectorAll('.pos-cart-line').forEach(el => el.remove());
                ui.cartLines.insertAdjacentHTML('afterbegin', rows);
            }

            ui.cartLines.querySelectorAll('.cartQtyInput').forEach(inp => {
                inp.addEventListener('wheel', (e) => {
                    e.preventDefault();
                    inp.blur();
                }, { passive: false });
                inp.addEventListener('input', () => {
                    const pid = Number(inp.dataset.productId);
                    const item = cart.get(pid);
                    if (!item) return;
                    let qty = Number(inp.value);
                    if (!isFinite(qty) || qty <= 0) qty = 0;
                    qty = Math.floor(qty);
                    if (Number(item.product.is_inventory_item) === 1 && qty > Number(item.product.stock)) {
                        qty = Math.max(0, Math.floor(Number(item.product.stock) || 0));
                    }
                    item.quantity = qty;
                    cart.set(pid, item);
                    if (qty <= 0) cart.delete(pid);
                    renderCart();
                    computeTotals();
                    updateCartButtons();
                });
            });

            updateCartButtons();
            updateSubtotalLabel();
            initLineDropdowns();
        }

        function updateCartButtons() {
            const hasItems = cart.size > 0;
            ui.clearCartBtn.disabled = !hasItems;
        }

        function addToCart(product) {
            const pid = Number(product.id);
            const qtyInCart = cart.get(pid)?.quantity ?? 0;
            let nextQty = Math.max(1, Math.floor(Number(qtyInCart) || 0) + 1);

            if (Number(product.is_inventory_item) === 1) {
                const stock = Math.max(0, Math.floor(Number(product.stock) || 0));
                if (stock <= 0) {
                    alert('Este producto no tiene stock disponible.');
                    return;
                }
                if (nextQty > stock) nextQty = stock;
            }

            const prev = cart.get(pid);
            cart.set(pid, {
                product,
                quantity: nextQty,
                unitPriceOverride: prev && prev.unitPriceOverride != null ? prev.unitPriceOverride : null
            });
            renderCart();
            computeTotals();
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function escapeAttribute(str) {
            return escapeHtml(str).replace(/`/g, '&#096;');
        }

        let productsDebounce = null;
        async function fetchProducts(query) {
            const url = endpoints.buscarProductos + '?query=' + encodeURIComponent(query || '') + '&limit=20';
            const res = await fetch(url, { credentials: 'same-origin' });
            const data = await res.json();
            if (!data.success) return;
            const items = data.items || [];

            if (!items.length) {
                ui.productResults.innerHTML = '<div class="list-group-item text-muted border-0">Sin resultados</div>';
                return;
            }

            ui.productResults.innerHTML = items.map(p => {
                const inv = Number(p.is_inventory_item) === 1;
                const stockText = inv ? ('Stock: ' + Number(p.stock).toFixed(3)) : 'No inventariable';
                const stockClass = inv && Number(p.stock) <= 0 ? 'text-danger' : 'text-muted';
                const prodEnc = encodeURIComponent(JSON.stringify(p));
                return `
                    <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2 border-0 border-bottom">
                        <div style="min-width:0;">
                            <div class="fw-semibold text-truncate">${escapeHtml(p.name || '')}</div>
                            <div class="small text-muted text-truncate">
                                ${p.sku ? escapeHtml('SKU: ' + p.sku) : ''}
                                ${p.barcode ? ' ' + escapeHtml(p.barcode) : ''}
                            </div>
                            <div class="small ${stockClass}">${escapeHtml(stockText)}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">${currency(p.price || 0)}</div>
                            <button type="button" class="btn btn-sm btn-primary rounded-3 mt-2 addProductBtn" data-product-enc="${prodEnc}">
                                <i class="bi bi-plus-lg"></i> Agregar
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            ui.productResults.querySelectorAll('.addProductBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const rawEnc = btn.getAttribute('data-product-enc');
                    const prod = JSON.parse(decodeURIComponent(rawEnc));
                    addToCart(prod);
                });
            });
        }

        async function addProductByScannedCode(rawCode) {
            const code = String(rawCode || '').trim();
            if (!code) return false;

            try {
                const url = endpoints.buscarProductos + '?query=' + encodeURIComponent(code) + '&limit=25';
                const res = await fetch(url, { credentials: 'same-origin' });
                const data = await res.json();
                const items = (data && data.success && Array.isArray(data.items)) ? data.items : [];
                if (!items.length) {
                    if (ui.scannerHint) ui.scannerHint.textContent = 'No se encontró producto para código: ' + code;
                    return false;
                }

                const normalized = code.toLowerCase();
                const exact = items.find(p => String(p.barcode || '').toLowerCase() === normalized)
                    || items.find(p => String(p.sku || '').toLowerCase() === normalized)
                    || items[0];
                addToCart(exact);

                ui.productSearch.value = code;
                fetchProducts(code);
                if (ui.scannerHint) ui.scannerHint.textContent = 'Escaneado: ' + code;
                return true;
            } catch (e) {
                if (ui.scannerHint) ui.scannerHint.textContent = 'Error al escanear código.';
                return false;
            }
        }

        ui.productSearch.addEventListener('input', () => {
            const q = ui.productSearch.value;
            if (productsDebounce) clearTimeout(productsDebounce);
            productsDebounce = setTimeout(() => fetchProducts(q), 250);
        });

        ui.barcodeInput.addEventListener('keydown', async (ev) => {
            if (ev.key !== 'Enter') return;
            ev.preventDefault();
            const code = ui.barcodeInput.value;
            if (!String(code || '').trim()) return;
            await addProductByScannedCode(code);
            ui.barcodeInput.value = '';
            ui.barcodeInput.focus();
        });

        if (hasOpenCash) {
            fetchProducts('');
        } else {
            ui.productResults.innerHTML = '<div class="list-group-item text-muted border-0">Abre una caja para buscar productos.</div>';
        }

        ui.clearCartBtn.addEventListener('click', () => {
            cart.clear();
            renderCart();
            computeTotals();
            updateCartButtons();
        });

        ui.resetDiscountBtn.addEventListener('click', () => {
            ui.discountPercent.value = 0;
            computeTotals();
        });
        if (ui.dropdownResetDiscount) {
            ui.dropdownResetDiscount.addEventListener('click', () => {
                ui.discountPercent.value = 0;
                computeTotals();
            });
        }

        ui.applyDiscountBtn.addEventListener('click', () => computeTotals());

        ui.quickDiscountBtn.addEventListener('click', () => {
            document.getElementById('discountPanel')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => ui.discountPercent.focus(), 300);
        });
        ui.quickBarcodeBtn.addEventListener('click', () => {
            ui.barcodeInput.focus();
            ui.barcodeInput.select();
        });
        ui.quickCustomerBtn.addEventListener('click', () => {
            document.getElementById('customerSearch')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => ui.customerSearch.focus(), 300);
        });
        ui.quickSellerBtn.addEventListener('click', () => alert('Función de vendedor: próximamente.'));
        ui.quickServicesBtn.addEventListener('click', () => alert('Recargas y servicios: próximamente.'));

        let customersDebounce = null;
        async function fetchCustomers(query) {
            const url = endpoints.buscarClientes + '?query=' + encodeURIComponent(query || '');
            const res = await fetch(url, { credentials: 'same-origin' });
            const data = await res.json();
            if (!data.success) return;
            const items = data.items || [];
            ui.customerResults.style.display = 'block';
            ui.customerResults.innerHTML = items.slice(0, 12).map(c => {
                const label = c.is_public ? (c.name + ' (Genérico)') : c.name;
                const custEnc = encodeURIComponent(JSON.stringify(c));
                return `
                    <button type="button" class="list-group-item list-group-item-action border-0 border-bottom text-start customerPickBtn"
                        data-customer-enc="${custEnc}">
                        <div class="fw-semibold">${escapeHtml(label)}</div>
                        <div class="small text-muted">${escapeHtml(c.phone || c.email || '')}</div>
                    </button>
                `;
            }).join('');

            ui.customerResults.querySelectorAll('.customerPickBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const rawEnc = btn.getAttribute('data-customer-enc');
                    selectedCustomer = JSON.parse(decodeURIComponent(rawEnc));
                    ui.selectedCustomerBadge.textContent = selectedCustomer.name || 'Cliente genérico';
                    ui.customerResults.style.display = 'none';
                    ui.customerSearch.value = '';
                    updateCreditoCustomerDisplay();
                });
            });
        }

        ui.customerSearch.addEventListener('input', () => {
            const q = ui.customerSearch.value;
            if (customersDebounce) clearTimeout(customersDebounce);
            customersDebounce = setTimeout(() => fetchCustomers(q), 250);
        });

        // Evita que el backdrop del modal quede por encima del contenido (clics en Cancelar/X no llegaban).
        (function moveLineModalsToBody() {
            ['linePriceModal', 'lineDiscountModal', 'productDetailModal'].forEach((id) => {
                const el = document.getElementById(id);
                if (el && el.parentNode !== document.body) {
                    document.body.appendChild(el);
                }
            });
        })();

        const cameraScannerModalEl = document.getElementById('cameraScannerModal');
        const paymentModalEl = document.getElementById('paymentModal');
        const ticketModalEl = document.getElementById('ticketModal');

        let cameraScannerModal = null;
        let paymentModal = null;
        let ticketModal = null;
        let linePriceModal = null;
        let lineDiscountModal = null;
        let productDetailModal = null;

        try {
            if (window.bootstrap && bootstrap.Modal) {
                if (cameraScannerModalEl) cameraScannerModal = new bootstrap.Modal(cameraScannerModalEl);
                if (paymentModalEl) paymentModal = new bootstrap.Modal(paymentModalEl);
                if (ticketModalEl) ticketModal = new bootstrap.Modal(ticketModalEl);
                if (ui.linePriceModalEl) linePriceModal = new bootstrap.Modal(ui.linePriceModalEl);
                if (ui.lineDiscountModalEl) lineDiscountModal = new bootstrap.Modal(ui.lineDiscountModalEl);
                if (ui.productDetailModalEl) productDetailModal = new bootstrap.Modal(ui.productDetailModalEl);
            }
        } catch (e) {
            if (ui.posDebug) ui.posDebug.textContent = 'Bootstrap Modal no disponible.';
        }

        if (window.bootstrap && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        }

        function showModalFallback(el) {
            if (!el) return;
            el.style.display = 'block';
            el.classList.add('show');
            el.setAttribute('aria-hidden', 'false');
        }

        function hideModalFallback(el) {
            if (!el) return;
            el.style.display = 'none';
            el.classList.remove('show');
            el.setAttribute('aria-hidden', 'true');
        }

        function showPaymentModal() {
            if (paymentModal && typeof paymentModal.show === 'function') {
                paymentModal.show();
                return;
            }
            showModalFallback(paymentModalEl);
        }

        function showCameraScannerModal() {
            if (cameraScannerModal && typeof cameraScannerModal.show === 'function') {
                cameraScannerModal.show();
                return;
            }
            showModalFallback(cameraScannerModalEl);
        }

        function hideCameraScannerModal() {
            if (cameraScannerModal && typeof cameraScannerModal.hide === 'function') {
                cameraScannerModal.hide();
                return;
            }
            hideModalFallback(cameraScannerModalEl);
        }

        function showTicketModal() {
            if (ticketModal && typeof ticketModal.show === 'function') {
                ticketModal.show();
                return;
            }
            showModalFallback(ticketModalEl);
        }

        function hidePaymentModal() {
            if (paymentModal && typeof paymentModal.hide === 'function') {
                paymentModal.hide();
                return;
            }
            hideModalFallback(paymentModalEl);
        }

        function hideTicketModal() {
            if (ticketModal && typeof ticketModal.hide === 'function') {
                ticketModal.hide();
                return;
            }
            hideModalFallback(ticketModalEl);
        }

        function forceHideLineModal(el, inst) {
            if (!el) return;
            try {
                if (inst && typeof inst.hide === 'function') {
                    inst.hide();
                } else {
                    hideModalFallback(el);
                }
            } catch (e) {}
            setTimeout(() => {
                if (!el.classList.contains('show')) return;
                el.classList.remove('show');
                el.style.display = 'none';
                el.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((b) => b.remove());
            }, 0);
        }

        document.getElementById('lineDiscountCancelBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            forceHideLineModal(ui.lineDiscountModalEl, lineDiscountModal);
        });
        document.getElementById('lineDiscountModalCloseX')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            forceHideLineModal(ui.lineDiscountModalEl, lineDiscountModal);
        });
        document.getElementById('linePriceCancelBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            forceHideLineModal(ui.linePriceModalEl, linePriceModal);
        });
        document.getElementById('linePriceModalCloseX')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            forceHideLineModal(ui.linePriceModalEl, linePriceModal);
        });
        document.getElementById('productDetailCloseBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            forceHideLineModal(ui.productDetailModalEl, productDetailModal);
        });
        document.getElementById('productDetailModalCloseX')?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            forceHideLineModal(ui.productDetailModalEl, productDetailModal);
        });

        function closeOpenCartDropdowns() {
            document.querySelectorAll('.pos-cart-line .dropdown-menu.show').forEach((m) => m.classList.remove('show'));
            document.querySelectorAll('.pos-cart-line .dropdown-toggle.show').forEach((t) => t.classList.remove('show'));
        }

        ui.cartLines.addEventListener('click', (ev) => {
            const btn = ev.target.closest('.pos-line-action');
            if (!btn) return;
            const pid = Number(btn.dataset.productId);
            const action = btn.dataset.action;
            const item = cart.get(pid);
            if (!item) return;

            if (action === 'remove') {
                cart.delete(pid);
                renderCart();
                computeTotals();
                return;
            }
            if (action === 'price') {
                closeOpenCartDropdowns();
                pendingLineProductId = pid;
                ui.linePriceProductName.textContent = item.product.name || '';
                ui.linePriceInput.value = String(effectiveUnitPrice(item));
                if (linePriceModal) linePriceModal.show();
                else showModalFallback(ui.linePriceModalEl);
                return;
            }
            if (action === 'discount') {
                closeOpenCartDropdowns();
                pendingLineProductId = pid;
                ui.lineDiscountProductName.textContent = item.product.name || '';
                ui.lineDiscountInput.value = '0';
                if (lineDiscountModal) lineDiscountModal.show();
                else showModalFallback(ui.lineDiscountModalEl);
                return;
            }
            if (action === 'detail') {
                closeOpenCartDropdowns();
                const p = item.product;
                const unit = effectiveUnitPrice(item);
                ui.productDetailBody.innerHTML = `
                    <dl class="row small mb-0">
                        <dt class="col-4 text-muted">Nombre</dt><dd class="col-8">${escapeHtml(p.name || '')}</dd>
                        <dt class="col-4 text-muted">SKU</dt><dd class="col-8">${escapeHtml(p.sku || '—')}</dd>
                        <dt class="col-4 text-muted">Código</dt><dd class="col-8">${escapeHtml(p.barcode || '—')}</dd>
                        <dt class="col-4 text-muted">Precio lista</dt><dd class="col-8">${currency(p.price)}</dd>
                        <dt class="col-4 text-muted">Precio línea</dt><dd class="col-8">${currency(unit)}</dd>
                        <dt class="col-4 text-muted">IVA %</dt><dd class="col-8">${escapeHtml(String(p.tax_percent ?? 0))}</dd>
                        <dt class="col-4 text-muted">Stock</dt><dd class="col-8">${Number(p.is_inventory_item) === 1 ? Number(p.stock).toFixed(3) : 'N/A'}</dd>
                    </dl>
                `;
                if (productDetailModal) productDetailModal.show();
                else showModalFallback(ui.productDetailModalEl);
            }
        });

        ui.linePriceSaveBtn.addEventListener('click', () => {
            if (pendingLineProductId == null) return;
            const item = cart.get(pendingLineProductId);
            if (!item) return;
            let v = round2(Number(ui.linePriceInput.value));
            if (!isFinite(v) || v < 0) v = 0;
            item.unitPriceOverride = v;
            cart.set(pendingLineProductId, item);
            pendingLineProductId = null;
            if (linePriceModal) linePriceModal.hide();
            else hideModalFallback(ui.linePriceModalEl);
            renderCart();
            computeTotals();
        });

        ui.lineDiscountSaveBtn.addEventListener('click', () => {
            if (pendingLineProductId == null) return;
            const item = cart.get(pendingLineProductId);
            if (!item) return;
            let pct = Number(ui.lineDiscountInput.value) || 0;
            pct = Math.max(0, Math.min(100, pct));
            const list = round2(Number(item.product.price) || 0);
            const eff = round2(list * (1 - pct / 100));
            item.unitPriceOverride = eff;
            cart.set(pendingLineProductId, item);
            pendingLineProductId = null;
            if (lineDiscountModal) lineDiscountModal.hide();
            else hideModalFallback(ui.lineDiscountModalEl);
            renderCart();
            computeTotals();
        });

        let scannerStream = null;
        let scannerIntervalId = null;
        let scannerBusy = false;
        let scannerLastValue = '';
        let scannerLastAt = 0;
        let barcodeDetector = null;

        async function startCameraScanner() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                ui.cameraScannerStatus.textContent = 'Este navegador no soporta acceso a cámara.';
                return;
            }
            if (!('BarcodeDetector' in window)) {
                ui.cameraScannerStatus.textContent = 'Tu navegador no soporta BarcodeDetector. Usa lector físico o Chrome actualizado.';
                return;
            }

            try {
                barcodeDetector = new window.BarcodeDetector({
                    formats: [
                        'ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128',
                        'code_39', 'itf', 'codabar', 'qr_code'
                    ]
                });
            } catch (e) {
                ui.cameraScannerStatus.textContent = 'No se pudo iniciar detector de códigos.';
                return;
            }

            try {
                ui.cameraScannerStatus.textContent = 'Solicitando permiso de cámara...';
                scannerStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false
                });
                ui.cameraScannerVideo.srcObject = scannerStream;
                ui.cameraScannerStatus.textContent = 'Apunta al código de barras...';

                if (scannerIntervalId) clearInterval(scannerIntervalId);
                scannerIntervalId = setInterval(async () => {
                    if (scannerBusy || !barcodeDetector) return;
                    if (!ui.cameraScannerVideo || ui.cameraScannerVideo.readyState < 2) return;
                    scannerBusy = true;
                    try {
                        const codes = await barcodeDetector.detect(ui.cameraScannerVideo);
                        if (codes && codes.length > 0) {
                            const val = String(codes[0].rawValue || '').trim();
                            if (val) {
                                const now = Date.now();
                                if (val === scannerLastValue && (now - scannerLastAt) < 1500) {
                                    scannerBusy = false;
                                    return;
                                }
                                scannerLastValue = val;
                                scannerLastAt = now;
                                const ok = await addProductByScannedCode(val);
                                ui.cameraScannerStatus.textContent = ok ? ('Producto agregado: ' + val) : ('No encontrado: ' + val);
                            }
                        }
                    } catch (e) {
                    } finally {
                        scannerBusy = false;
                    }
                }, 300);
            } catch (e) {
                ui.cameraScannerStatus.textContent = 'No se pudo abrir la cámara.';
            }
        }

        function stopCameraScanner() {
            if (scannerIntervalId) {
                clearInterval(scannerIntervalId);
                scannerIntervalId = null;
            }
            if (scannerStream) {
                scannerStream.getTracks().forEach(t => t.stop());
                scannerStream = null;
            }
            if (ui.cameraScannerVideo) {
                ui.cameraScannerVideo.srcObject = null;
            }
            scannerBusy = false;
        }

        ui.openCameraScannerBtn.addEventListener('click', () => {
            showCameraScannerModal();
            startCameraScanner();
        });

        ui.openPaymentBtn.addEventListener('click', () => {
            if (!cart.size) return;
            ui.paymentError.innerHTML = '';
            ui.dueAmount.textContent = currency(currentTotals.totalDue);
            const qel = document.getElementById('quickExactLabel');
            if (qel) qel.textContent = currency(currentTotals.totalDue);
            if (ui.tenderedAmount) ui.tenderedAmount.value = currentTotals.totalDue;
            const tc = document.getElementById('tenderedCreditoAmount');
            if (tc) tc.value = '0';
            const m1 = document.getElementById('multiAmount1');
            const m2 = document.getElementById('multiAmount2');
            if (m1) m1.value = '0';
            if (m2) m2.value = '0';
            const pf = document.getElementById('paymentFolioSimple');
            if (pf) pf.value = '';
            const ptr = document.getElementById('paymentRefTransferencia');
            if (ptr) ptr.value = '';
            const pom = document.getElementById('paymentObsMultiple');
            if (pom) pom.value = '';
            updateQuickPayButtons();
            setActivePaymentMode('efectivo');
            updateCreditoCustomerDisplay();
            showPaymentModal();
        });

        document.querySelectorAll('#paymentModal .payment-mode-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const mode = btn.getAttribute('data-payment-mode');
                if (mode) setActivePaymentMode(mode);
            });
        });

        document.querySelectorAll('.quick-pay-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const q = btn.getAttribute('data-quick-pay');
                const total = round2(Number(currentTotals.totalDue) || 0);
                if (!ui.tenderedAmount) return;
                if (q === 'exact') ui.tenderedAmount.value = String(total);
                else if (q === 'round50' || q === 'round100') {
                    const v = btn.getAttribute('data-amount');
                    if (v) ui.tenderedAmount.value = String(v);
                }
                updatePaymentBalanceUI();
            });
        });

        ui.tenderedAmount.addEventListener('input', () => updatePaymentBalanceUI());
        document.getElementById('tenderedCreditoAmount')?.addEventListener('input', () => updatePaymentBalanceUI());
        document.getElementById('multiAmount1')?.addEventListener('input', () => updatePaymentBalanceUI());
        document.getElementById('multiAmount2')?.addEventListener('input', () => updatePaymentBalanceUI());

        function payRow(method, amount, ref) {
            const row = { payment_method: method, amount };
            const r = ref != null ? String(ref).trim() : '';
            if (r !== '') row.reference = r;
            return row;
        }

        function buildCombinedNotes() {
            const base = (ui.saleNotes.value || '').trim();
            const parts = [];
            if (base) parts.push(base);
            if (activePaymentMode === 'multiple') {
                const obs = (document.getElementById('paymentObsMultiple')?.value || '').trim();
                if (obs) parts.push('Pago mixto: ' + obs);
            }
            return parts.length ? parts.join('\n') : null;
        }

        let saleSubmitInFlight = false;
        let autoPrintTicketAfterShown = false;
        let lastTicketPrintAt = 0;
        let printedForCurrentTicket = false;
        let printInProgress = false;

        function safePrintTicket() {
            if (printInProgress || printedForCurrentTicket) {
                return;
            }
            const printRoot = document.getElementById('ticketPrintOnlyRoot');
            const ticketHtml = (ui.ticketPrintArea?.innerHTML || '').trim();
            if (!printRoot || ticketHtml === '') {
                return;
            }
            const now = Date.now();
            if (now - lastTicketPrintAt < 1200) {
                return;
            }
            printInProgress = true;
            lastTicketPrintAt = now;
            printedForCurrentTicket = true;
            printRoot.innerHTML = ticketHtml;
            setTimeout(() => {
                window.print();
                setTimeout(() => {
                    printRoot.innerHTML = '';
                    printInProgress = false;
                }, 700);
            }, 80);
        }

        if (ticketModalEl) {
            ticketModalEl.addEventListener('shown.bs.modal', () => {
                if (!autoPrintTicketAfterShown) {
                    return;
                }
                autoPrintTicketAfterShown = false;
                setTimeout(() => safePrintTicket(), 280);
            });
        }

        ui.confirmPaymentBtn.addEventListener('click', async () => {
            if (saleSubmitInFlight) {
                return;
            }
            saleSubmitInFlight = true;
            ui.confirmPaymentBtn.disabled = true;
            try {
            ui.paymentError.innerHTML = '';
            const condition = getPaymentConditionFromMode();
            const totalDue = round2(Number(currentTotals.totalDue) || 0);
            let payments = [];

            if (activePaymentMode === 'credito') {
                if (!selectedCustomer || !selectedCustomer.id || Number(selectedCustomer.is_public) === 1) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">Venta a crédito: selecciona un cliente concreto (no el genérico) con <b>Añadir cliente</b>.</div>`;
                    return;
                }
            }


            if (activePaymentMode === 'efectivo') {
                const tendered = round2(Number(ui.tenderedAmount.value) || 0);
                if (tendered <= 0) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">Ingresa el importe recibido en efectivo.</div>`;
                    return;
                }
                if (tendered + 0.0001 < totalDue) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">El efectivo recibido es menor al total a pagar.</div>`;
                    return;
                }
                payments = [{ payment_method: 'EFECTIVO', amount: tendered }];
            } else if (activePaymentMode === 'credito') {
                const ant = round2(Number(document.getElementById('tenderedCreditoAmount')?.value) || 0);
                if (ant - 0.0001 > totalDue) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">El anticipo no puede ser mayor al total.</div>`;
                    return;
                }
                payments = ant > 0 ? [payRow('EFECTIVO', ant, null)] : [];
            } else if (activePaymentMode === 'tarjeta_credito') {
                const folio = (document.getElementById('paymentFolioSimple')?.value || '').trim();
                payments = [payRow('TARJETA_CREDITO', totalDue, folio || null)];
            } else if (activePaymentMode === 'tarjeta_debito') {
                const folio = (document.getElementById('paymentFolioSimple')?.value || '').trim();
                payments = [payRow('TARJETA_DEBITO', totalDue, folio || null)];
            } else if (activePaymentMode === 'transferencia') {
                const refTr = (document.getElementById('paymentRefTransferencia')?.value || '').trim();
                payments = [payRow('TRANSFERENCIA', totalDue, refTr || null)];
            } else if (activePaymentMode === 'multiple') {
                const m1 = String(document.getElementById('multiMethod1')?.value || 'EFECTIVO');
                const m2 = String(document.getElementById('multiMethod2')?.value || 'EFECTIVO');
                const a1 = round2(Number(document.getElementById('multiAmount1')?.value) || 0);
                const a2 = round2(Number(document.getElementById('multiAmount2')?.value) || 0);
                const sum = round2(a1 + a2);
                if (Math.abs(sum - totalDue) > 0.02) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">La suma de los importes debe coincidir con el total (${currency(totalDue)}).</div>`;
                    return;
                }
                if (a1 > 0) payments.push(payRow(m1, a1, null));
                if (a2 > 0) payments.push(payRow(m2, a2, null));
                if (payments.length === 0) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">Indica al menos un importe mayor a cero.</div>`;
                    return;
                }
            }

            if (condition === 'CONTADO') {
                if (payments.length === 0) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">Debes indicar un método de pago válido.</div>`;
                    return;
                }
                const sumPay = round2(payments.reduce((s, p) => s + Number(p.amount), 0));
                if (sumPay + 0.0001 < totalDue) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">El total cobrado cubre menos que el total a pagar.</div>`;
                    return;
                }
            }

            const discountPercent = Number(ui.discountPercent.value) || 0;

            const itemsPayload = Array.from(cart.values()).map(v => {
                const row = {
                    product_id: Number(v.product.id),
                    quantity: Math.max(1, Math.floor(Number(v.quantity) || 1))
                };
                if (v.unitPriceOverride != null && isFinite(Number(v.unitPriceOverride))) {
                    row.unit_price = round2(Number(v.unitPriceOverride));
                }
                return row;
            });

            const customerId = selectedCustomer && selectedCustomer.id ? Number(selectedCustomer.id) : null;
            const notes = buildCombinedNotes();

            const payload = {
                items: itemsPayload,
                customer_id: customerId,
                discount: { type: 'percent', value: discountPercent },
                notes: notes,
                payment_condition: condition,
                payments: payments
            };

                const res = await fetch(endpoints.confirmarVenta, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (!data.success) {
                    ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">${escapeHtml(data.error || 'Error al confirmar venta')}</div>`;
                    return;
                }

                const hadCashPayment = Array.isArray(payments) && payments.some((p) => {
                    const m = String(p.payment_method || '').toUpperCase();
                    const a = Number(p.amount) || 0;
                    return m === 'EFECTIVO' && a > 0;
                });
                autoPrintTicketAfterShown = hadCashPayment;

                hidePaymentModal();
                ui.ticketPrintArea.innerHTML = data.ticket_html || '';
                printedForCurrentTicket = false;
                printInProgress = false;
                showTicketModal();

                cart.clear();
                ui.saleNotes.value = '';
                ui.discountPercent.value = 0;
                selectedCustomer = publicCustomer && publicCustomer.id ? publicCustomer : null;
                ui.selectedCustomerBadge.textContent = selectedCustomer ? (selectedCustomer.name || 'Cliente genérico') : 'Cliente genérico';

                renderCart();
                computeTotals();
                fetchProducts(ui.productSearch.value || '');
            } catch (e) {
                ui.paymentError.innerHTML = `<div class="alert alert-danger rounded-3">Error de comunicación con el servidor.</div>`;
            } finally {
                saleSubmitInFlight = false;
                ui.confirmPaymentBtn.disabled = false;
            }
        });

        ui.printTicketBtn.addEventListener('click', () => {
            safePrintTicket();
        });

        ui.newSaleBtn.addEventListener('click', () => {
            hideTicketModal();
            ui.saleNotes.value = '';
            ui.discountPercent.value = 0;
            if (publicCustomer && publicCustomer.id) {
                selectedCustomer = publicCustomer;
                ui.selectedCustomerBadge.textContent = publicCustomer.name || 'Cliente genérico';
            } else {
                selectedCustomer = null;
                ui.selectedCustomerBadge.textContent = 'Cliente genérico';
            }
            cart.clear();
            renderCart();
            computeTotals();
        });

        document.querySelectorAll('#paymentModal [data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => hidePaymentModal());
        });
        document.querySelectorAll('#ticketModal [data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => hideTicketModal());
        });
        document.querySelectorAll('#cameraScannerModal [data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', () => {
                stopCameraScanner();
                hideCameraScannerModal();
            });
        });
        if (cameraScannerModalEl) {
            cameraScannerModalEl.addEventListener('hidden.bs.modal', () => stopCameraScanner());
        }

        renderCart();
        computeTotals();
    })();
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
