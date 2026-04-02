<?php
$quotation = $quotation ?? [];
$items = $items ?? [];
$shopRow = $shopRow ?? [];
$quotationPrint = $quotationPrint ?? [
    'paper' => 'letter',
    'margin_mm' => 10,
    'scale_pct' => 100,
    'show_sku' => true,
    'show_tax_col' => true,
    'show_signatures' => true,
    'footer_note' => '',
];
$shopQuotationMigrationNeeded = $shopQuotationMigrationNeeded ?? false;
$basePath = $basePath ?? '';
$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
if ($basePath === '') {
    $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    if ($basePath === '.' || $basePath === '\\' || $basePath === '/') {
        $basePath = '';
    }
}
$folio = (int) ($quotation['folio'] ?? 0);
$st = (string) ($quotation['status'] ?? 'OPEN');
$pageTitle = $pageTitle ?? ('Cotización #' . $folio);
$customerName = trim((string) ($quotation['customer_name'] ?? ''));
$createdAt = !empty($quotation['created_at']) ? strtotime((string) $quotation['created_at']) : false;

$qpPaper = (string) ($quotationPrint['paper'] ?? 'letter');
if (!in_array($qpPaper, ['letter', 'a4'], true)) {
    $qpPaper = 'letter';
}
$qpMargin = (int) ($quotationPrint['margin_mm'] ?? 10);
$qpScale = (int) ($quotationPrint['scale_pct'] ?? 100);
$qpShowSku = !empty($quotationPrint['show_sku']);
$qpShowTax = !empty($quotationPrint['show_tax_col']);
$qpShowSig = !empty($quotationPrint['show_signatures']);
$qpFooter = trim((string) ($quotationPrint['footer_note'] ?? ''));
$qpPageSize = $qpPaper === 'a4' ? 'A4' : 'letter';
$qpScaleCss = max(0.85, min(1.15, $qpScale / 100));

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-file-earmark-text me-2" style="color:var(--teal);"></i> Cotización #<?= $folio ?></h1>
        <p class="text-muted small mb-0">Documento para el cliente. Usa <strong>Imprimir</strong> y elige «Guardar como PDF» en el navegador.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= htmlspecialchars($basePath . '/admin/cotizaciones', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Volver al listado</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Imprimir / PDF
        </button>
    </div>
</div>

<div class="card border-0 card-shadow rounded-4 mb-4 no-print">
    <div class="card-body p-4">
        <h2 class="h6 fw-semibold mb-2"><i class="bi bi-sliders me-2" style="color:var(--teal);"></i> Impresión avanzada</h2>
        <p class="text-muted small mb-3">Estas opciones se definen en la configuración de la tienda y se aplican al imprimir o guardar PDF.</p>
        <?php if (!empty($shopQuotationMigrationNeeded)): ?>
            <div class="alert alert-warning small mb-3 py-2">
                Aún no están activas las columnas en la base de datos. Se usan valores por defecto. Ejecuta <code>database/sql/hito17_quotation_print.sql</code> o <code>php bin/console.php migrate</code>.
            </div>
        <?php endif; ?>
        <div class="row g-2 small">
            <div class="col-6 col-md-4"><span class="text-muted">Papel:</span> <?= $qpPaper === 'a4' ? 'A4' : 'Carta (Letter)' ?></div>
            <div class="col-6 col-md-4"><span class="text-muted">Márgenes:</span> <?= (int) $qpMargin ?> mm</div>
            <div class="col-6 col-md-4"><span class="text-muted">Escala texto:</span> <?= (int) $qpScale ?>%</div>
            <div class="col-6 col-md-4"><span class="text-muted">SKU:</span> <?= $qpShowSku ? 'Sí' : 'No' ?></div>
            <div class="col-6 col-md-4"><span class="text-muted">Columna IVA:</span> <?= $qpShowTax ? 'Sí' : 'No' ?></div>
            <div class="col-6 col-md-4"><span class="text-muted">Firmas:</span> <?= $qpShowSig ? 'Sí' : 'No' ?></div>
        </div>
        <a href="<?= htmlspecialchars($basePath . '/admin/configuracion/tienda#cotizacion-impresion', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm rounded-3 mt-3">Editar en configuración de la tienda</a>
    </div>
</div>

<div id="quotationPrintArea" class="quotation-paper" style="--qp-scale: <?= htmlspecialchars((string) $qpScaleCss, ENT_QUOTES, 'UTF-8') ?>;">
    <div class="letterhead">
        <div class="letterhead-accent"></div>
        <div class="letterhead-inner">
            <div>
                <div class="store-name"><?= htmlspecialchars((string) ($shopRow['name'] ?? $shopName ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="store-meta">COTIZACION COMERCIAL</div>
                <?php if (!empty($shopRow['address'])): ?>
                    <div class="small text-white-50 mt-2"><?= nl2br(htmlspecialchars((string) $shopRow['address'], ENT_QUOTES, 'UTF-8')) ?></div>
                <?php endif; ?>
                <?php if (!empty($shopRow['phone'])): ?>
                    <div class="small text-white-50">Tel. <?= htmlspecialchars((string) $shopRow['phone'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <div class="doc-folio">#<?= $folio ?></div>
                <div class="small text-white-50"><?= $createdAt ? date('d/m/Y H:i', $createdAt) : '' ?></div>
                <div class="small mt-2">
                    <?php if ($st === 'OPEN'): ?>
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">Abierta</span>
                    <?php else: ?>
                        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Vendida</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="paper-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-title">Cliente</div>
                    <?php if ($customerName !== ''): ?>
                        <div class="fw-semibold fs-6"><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php else: ?>
                        <div class="text-muted">Cliente no especificado</div>
                    <?php endif; ?>
                    <?php if (!empty($quotation['customer_phone'])): ?>
                        <div class="small text-muted mt-1"><?= htmlspecialchars((string) $quotation['customer_phone'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($quotation['customer_email'])): ?>
                        <div class="small text-muted"><?= htmlspecialchars((string) $quotation['customer_email'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-title">Condiciones</div>
                    <div class="small"><span class="text-muted">Vigencia:</span> <?= !empty($quotation['valid_from']) ? date('d/m/Y', strtotime((string) $quotation['valid_from'])) : '—' ?> al <?= !empty($quotation['valid_to']) ? date('d/m/Y', strtotime((string) $quotation['valid_to'])) : 'Sin limite' ?></div>
                    <?php if (!empty($quotation['seller_name'])): ?>
                        <div class="small mt-1"><span class="text-muted">Vendedor:</span> <?= htmlspecialchars(trim((string) $quotation['seller_name']), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <?php if (!empty($quotation['delivery_address'])): ?>
                        <div class="small mt-2"><span class="text-muted">Entrega:</span> <?= nl2br(htmlspecialchars((string) $quotation['delivery_address'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="table-wrap mb-4">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Descripcion</th>
                        <th class="text-end">Cant.</th>
                        <th class="text-end">Precio Unit.</th>
                        <?php if ($qpShowTax): ?>
                            <th class="text-end">IVA</th>
                        <?php endif; ?>
                        <th class="text-end">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string) ($it['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if ($qpShowSku && !empty($it['sku'])): ?>
                                    <div class="small text-muted">SKU: <?= htmlspecialchars((string) $it['sku'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= rtrim(rtrim(number_format((float) ($it['quantity'] ?? 0), 3, '.', ''), '0'), '.') ?></td>
                            <td class="text-end">$<?= number_format((float) ($it['unit_price'] ?? 0), 2, '.', ',') ?></td>
                            <?php if ($qpShowTax): ?>
                                <td class="text-end"><?= number_format((float) ($it['tax_percent'] ?? 0), 2, '.', ',') ?>%</td>
                            <?php endif; ?>
                            <td class="text-end fw-semibold">$<?= number_format((float) ($it['line_total'] ?? 0), 2, '.', ',') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end mb-4">
            <div class="totals-box">
                <div class="d-flex justify-content-between small"><span>Subtotal</span><span>$<?= number_format((float) ($quotation['subtotal'] ?? 0), 2, '.', ',') ?></span></div>
                <div class="d-flex justify-content-between small"><span>Impuestos</span><span>$<?= number_format((float) ($quotation['tax_total'] ?? 0), 2, '.', ',') ?></span></div>
                <div class="d-flex justify-content-between total-row"><span>Total</span><span>$<?= number_format((float) ($quotation['total'] ?? 0), 2, '.', ',') ?></span></div>
            </div>
        </div>

        <?php if (!empty($quotation['note_to_customer'])): ?>
            <div class="note-box mb-4">
                <div class="info-title mb-2">Nota al cliente</div>
                <div class="small"><?= nl2br(htmlspecialchars((string) $quotation['note_to_customer'], ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($qpShowSig): ?>
            <div class="row g-4 mt-4 signature-zone">
                <div class="col-6">
                    <div class="sign-line"></div>
                    <div class="small text-muted text-center mt-2">Autorizado por</div>
                </div>
                <div class="col-6">
                    <div class="sign-line"></div>
                    <div class="small text-muted text-center mt-2">Recibido por cliente</div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($qpFooter !== ''): ?>
            <div class="quotation-footer-note mt-4 pt-3">
                <div class="small text-muted"><?= nl2br(htmlspecialchars($qpFooter, ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .quotation-paper {
        max-width: 900px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
        font-size: calc(14px * var(--qp-scale, 1));
    }
    .letterhead {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0f766e 100%);
        color: #fff;
        position: relative;
    }
    .letterhead-accent {
        height: 6px;
        background: linear-gradient(90deg, #14b8a6, #06b6d4, #22c55e);
    }
    .letterhead-inner {
        padding: 1.2rem 1.5rem 1.3rem;
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: flex-start;
        flex-wrap: wrap;
    }
    .store-name { font-size: 1.25rem; font-weight: 800; letter-spacing: .2px; }
    .store-meta { font-size: .72rem; letter-spacing: .12em; opacity: .9; margin-top: .2rem; }
    .doc-folio { font-size: 1.15rem; font-weight: 800; }
    .paper-body { padding: 1.2rem 1.4rem 1.4rem; }
    .info-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: .75rem .85rem;
        height: 100%;
        background: #f8fafc;
    }
    .info-title {
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: .35rem;
    }
    .table-wrap {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
    }
    .table-wrap thead th {
        background: #f1f5f9;
        color: #334155;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 1px solid #e2e8f0;
    }
    .table-wrap .table > :not(caption) > * > * { padding: .62rem .68rem; }
    .totals-box {
        min-width: 260px;
        border: 1px solid #dbeafe;
        background: #f8fbff;
        border-radius: 12px;
        padding: .8rem .9rem;
    }
    .totals-box .total-row {
        font-size: 1.05rem;
        font-weight: 800;
        border-top: 1px solid #cbd5e1;
        padding-top: .42rem;
        margin-top: .42rem;
        color: #0f172a;
    }
    .note-box {
        border: 1px solid #e2e8f0;
        border-left: 4px solid #14b8a6;
        border-radius: 10px;
        background: #fcfffe;
        padding: .8rem .9rem;
    }
    .sign-line {
        height: 1px;
        background: #94a3b8;
        width: 100%;
        margin-top: 1.2rem;
    }
    .quotation-footer-note {
        border-top: 1px solid #e2e8f0;
    }
    @media print {
        .no-print { display: none !important; }
        body * { visibility: hidden; }
        #quotationPrintArea, #quotationPrintArea * { visibility: visible; }
        #quotationPrintArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            max-width: 100%;
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        .table-wrap, .info-card, .totals-box, .note-box { break-inside: avoid; }
        @page { size: <?= htmlspecialchars($qpPageSize, ENT_QUOTES, 'UTF-8') ?>; margin: <?= (int) $qpMargin ?>mm; }
    }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
