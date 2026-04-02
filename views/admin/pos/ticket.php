<?php
$pageTitle = $pageTitle ?? 'Ticket';
$sale = $sale ?? null;
$ticketHtml = $ticketHtml ?? null;

ob_start();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-receipt me-2" style="color:var(--teal);"></i> Ticket de venta</h1>
        <p class="text-muted small mb-0">Comprobante imprimible. El formato sigue la configuración de impresión en <a href="<?= htmlspecialchars($basePath . '/admin/configuracion/tienda', ENT_QUOTES, 'UTF-8') ?>">Configuración de la tienda</a>.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary" type="button" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Imprimir
        </button>
    </div>
</div>

<?php if (!$sale): ?>
    <div class="alert alert-danger">Venta no encontrada.</div>
<?php elseif ($ticketHtml !== null && $ticketHtml !== ''): ?>
    <div id="ticketPrintArea" class="border rounded-3 p-2" style="background:#fff;">
        <?= $ticketHtml ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning">No se pudo generar el ticket.</div>
<?php endif; ?>

<style>
    @media print {
        body * { visibility: hidden; }
        #ticketPrintArea, #ticketPrintArea * { visibility: visible; }
        #ticketPrintArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            max-width: 100%;
            background: #fff;
            border: none !important;
            padding: 0 !important;
        }
        #ticketPrintArea .pos-ticket {
            margin: 0 !important;
            max-width: none !important;
            width: 100% !important;
        }
        @page { size: auto; margin: 1.2mm; }
    }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../../layouts/admin.php';
?>
