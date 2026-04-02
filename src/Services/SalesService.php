<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use App\Repositories\InventoryMovementRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SalesRepository;
use App\Repositories\ShopRepository;

class SalesService
{
    private CashService $cashService;
    private ProductRepository $productRepo;
    private SalesRepository $salesRepo;
    private InventoryMovementRepository $inventoryRepo;
    private ShopRepository $shopRepo;

    /**
     * MVP Hito 5:
     * - Caja abierta obligatoria
     * - Pagos: EFECTIVO, tarjeta, transferencia, cuenta crédito (sale_payments.payment_method)
     * - Descuento: percent aplicado sobre subtotal, distribuido proporcionalmente por línea para impuestos
     */
    public function __construct()
    {
        $this->cashService = new CashService();
        $this->productRepo = new ProductRepository();
        $this->salesRepo = new SalesRepository();
        $this->inventoryRepo = new InventoryMovementRepository();
        $this->shopRepo = new ShopRepository();
    }

    /**
     * @param array{
     *   items: array<int, array{product_id:int, quantity:float|string, unit_price?:float}>,
     *   customer_id: int|null,
     *   discount: array{type:string, value:float|string|null},
     *   notes?: string|null,
     *   payment_condition?: string, // CONTADO | CREDITO
     *   payments: array<int, array{payment_method:string, amount:float|string}>
     * } $payload
     *
     * @return array{success:bool, error?:string, sale_id?:int, folio?:int, ticket_html?:string, totals?:array<string,float|int>}
     */
    public function confirmSale(array $payload, int $shopId, int $userId): array
    {
        // Validación superficial antes de transacción: evita abrir transacciones con datos claramente inválidos.
        $items = $payload['items'] ?? [];
        if (!is_array($items) || $items === []) {
            return ['success' => false, 'error' => 'Carrito vacío. Agrega productos antes de confirmar.'];
        }

        $discount = $payload['discount'] ?? ['type' => 'percent', 'value' => 0];
        $discountType = strtoupper((string) ($discount['type'] ?? 'percent'));
        if (!in_array($discountType, ['PERCENT', 'AMOUNT'], true)) {
            $discountType = 'PERCENT';
        }

        $discountValueRaw = $discount['value'] ?? 0;
        $discountValue = $discountValueRaw === null ? 0.0 : (float) $discountValueRaw;
        if (!is_finite($discountValue)) {
            $discountValue = 0.0;
        }
        if ($discountType === 'PERCENT') {
            if ($discountValue < 0) {
                $discountValue = 0.0;
            }
            if ($discountValue > 100) {
                $discountValue = 100.0;
            }
        } else {
            if ($discountValue < 0) {
                $discountValue = 0.0;
            }
        }

        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : null;
        if ($customerId !== null && $customerId <= 0) {
            $customerId = null;
        }

        $notes = isset($payload['notes']) ? trim((string) $payload['notes']) : null;
        if ($notes !== null && $notes === '') {
            $notes = null;
        }

        $paymentCondition = strtoupper(trim((string) ($payload['payment_condition'] ?? 'CONTADO')));
        if (!in_array($paymentCondition, ['CONTADO', 'CREDITO'], true)) {
            $paymentCondition = 'CONTADO';
        }

        $payments = $payload['payments'] ?? [];
        if (!is_array($payments)) {
            return ['success' => false, 'error' => 'Datos de pago inválidos.'];
        }
        if ($payments === [] && $paymentCondition === 'CONTADO') {
            return ['success' => false, 'error' => 'Debe registrar al menos un método de pago.'];
        }

        $allowedMethods = [
            'EFECTIVO',
            'TARJETA_CREDITO',
            'TARJETA_DEBITO',
            'TRANSFERENCIA',
            'CUENTA_CREDITO',
            'OTRO',
        ];
        $normalizedPayments = [];
        foreach ($payments as $p) {
            if (!is_array($p)) {
                continue;
            }
            $method = strtoupper(trim((string) ($p['payment_method'] ?? '')));
            $amount = (float) ($p['amount'] ?? 0);
            if (!in_array($method, $allowedMethods, true)) {
                return ['success' => false, 'error' => 'Método de pago no permitido.'];
            }
            if (!is_finite($amount) || $amount < 0) {
                return ['success' => false, 'error' => 'Importe de pago inválido.'];
            }
            // En contado el importe debe ser > 0; en crédito permitimos 0 para “crédito puro”.
            if ($amount <= 0 && $paymentCondition !== 'CREDITO') {
                return ['success' => false, 'error' => 'Importe de pago inválido.'];
            }
            $ref = $this->normalizePaymentReference($p['reference'] ?? null);
            $normalizedPayments[] = [
                'payment_method' => $method,
                'amount' => $this->round2($amount),
                'reference' => $ref,
            ];
        }
        if ($normalizedPayments === [] && $paymentCondition === 'CONTADO') {
            return ['success' => false, 'error' => 'Importes de pago inválidos.'];
        }

        // Validación de caja abierta (en transacción también para consistencia).
        $openSession = $this->cashService->getOpenSession($shopId);
        if (!$openSession) {
            return ['success' => false, 'error' => 'No hay una caja abierta. Abra una sesión de caja antes de confirmar la venta.'];
        }

        // Normalizar items.
        $cartItems = [];
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $productId = (int) ($it['product_id'] ?? 0);
            $qtyRaw = $it['quantity'] ?? 0;
            $qty = is_string($qtyRaw) ? (float) $qtyRaw : (float) $qtyRaw;
            if ($productId <= 0) {
                continue;
            }
            if (!is_finite($qty) || $qty <= 0) {
                continue;
            }
            $row = [
                'product_id' => $productId,
                'quantity' => $this->round3($qty),
            ];
            if (array_key_exists('unit_price', $it)) {
                $up = (float) ($it['unit_price'] ?? 0);
                if (is_finite($up) && $up >= 0) {
                    $row['unit_price'] = $this->round2($up);
                }
            }
            $cartItems[] = $row;
        }
        if ($cartItems === []) {
            return ['success' => false, 'error' => 'Carrito con cantidades inválidas.'];
        }

        $payloadForTransaction = [
            'discountType' => $discountType,
            'discountValue' => $discountValue,
            'customerId' => $customerId,
            'notes' => $notes,
            'paymentCondition' => $paymentCondition,
            'items' => $cartItems,
            'payments' => $normalizedPayments,
        ];

        try {
            $result = Database::transaction(function () use ($payloadForTransaction, $shopId, $userId) {
            $openSessionTx = $this->cashService->getOpenSession($shopId);
            if (!$openSessionTx) {
                return ['success' => false, 'error' => 'No hay caja abierta.'];
            }
            $cashSessionId = (int) $openSessionTx['id'];

            // Bloqueo de productos para evitar oversell (INVENTARIO).
            $productIds = array_values(array_unique(array_map(fn ($x) => (int) $x['product_id'], $payloadForTransaction['items'])));
            $products = $this->productRepo->lockByIdsForSale($productIds, $shopId);
            if (count($products) !== count($productIds)) {
                return ['success' => false, 'error' => 'Alguno de los productos ya no está disponible para venta.'];
            }

            // Recalcular precios/impuestos desde el catálogo actual y persistir snapshots en sale_items.
            $lines = [];
            $subtotal = 0.0;
            foreach ($payloadForTransaction['items'] as $line) {
                $pid = (int) $line['product_id'];
                $qty = (float) $line['quantity'];
                $product = $products[$pid] ?? null;
                if (!$product) {
                    return ['success' => false, 'error' => 'Producto no encontrado para venta.'];
                }
                // Validación de stock para inventariables.
                if ((int) ($product['is_inventory_item'] ?? 0) === 1) {
                    $stock = (float) ($product['stock'] ?? 0);
                    if ($qty <= 0) {
                        return ['success' => false, 'error' => 'Cantidad inválida.'];
                    }
                    // Tolerancia por precisión float/DECIMAL.
                    if ($stock + 0.000001 < $qty) {
                        return [
                            'success' => false,
                            'error' => 'Stock insuficiente para "' . (string) ($product['name'] ?? 'Producto') . '".',
                        ];
                    }
                }

                $catalogUnit = (float) ($product['price'] ?? 0);
                $unitPrice = $catalogUnit;
                if (isset($line['unit_price'])) {
                    $ov = (float) $line['unit_price'];
                    if (is_finite($ov) && $ov >= 0) {
                        $unitPrice = $this->round2($ov);
                    }
                }
                $taxPercent = (float) ($product['tax_percent'] ?? 0);
                $costSnapshot = (float) ($product['cost'] ?? 0);
                $lineGross = $this->round2($unitPrice * $qty);
                $subtotal += $lineGross;

                $lines[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'unit_price' => $this->round2($unitPrice),
                    'tax_percent' => $this->round2($taxPercent),
                    'cost_snapshot' => $this->round2($costSnapshot),
                    'line_gross' => $lineGross,
                    'is_inventory_item' => (int) ($product['is_inventory_item'] ?? 0) === 1,
                ];
            }
            $subtotal = $this->round2($subtotal);

            // Calcular descuento.
            $discountTotal = 0.0;
            $discountType = (string) $payloadForTransaction['discountType'];
            $discountValue = (float) $payloadForTransaction['discountValue'];
            if ($subtotal > 0) {
                if ($discountType === 'PERCENT') {
                    $discountTotal = $this->round2($subtotal * ($discountValue / 100));
                } else {
                    $discountTotal = $this->round2($discountValue);
                }
                if ($discountTotal < 0) {
                    $discountTotal = 0.0;
                }
                if ($discountTotal > $subtotal) {
                    $discountTotal = $subtotal;
                }
            }

            // Calcular descuento e impuesto por línea para mantener historial fiel.
            $taxTotal = 0.0;
            $discountRemaining = $discountTotal;
            $subtotalForAlloc = $subtotal > 0 ? $subtotal : 0.0;
            $lineCount = count($lines);
            foreach ($lines as $idx => &$ln) {
                $lineGross = (float) $ln['line_gross'];
                $allocDiscount = 0.0;
                if ($idx === $lineCount - 1) {
                    $allocDiscount = $discountRemaining;
                } else {
                    if ($subtotalForAlloc > 0 && $discountTotal > 0) {
                        $allocDiscount = (float) $this->round2(($lineGross / $subtotalForAlloc) * $discountTotal);
                    } else {
                        $allocDiscount = 0.0;
                    }
                    $allocDiscount = max(0.0, min($allocDiscount, $discountRemaining));
                }
                $discountRemaining = $this->round2($discountRemaining - $allocDiscount);

                $netLine = $this->round2($lineGross - $allocDiscount);
                $taxPercent = (float) $ln['tax_percent'];
                $taxLine = $this->round2($netLine * ($taxPercent / 100));
                $taxTotal = $this->round2($taxTotal + $taxLine);

                $ln['line_subtotal'] = $netLine;
                $ln['discount_amount'] = $allocDiscount;
                $ln['tax_amount'] = $taxLine;
                $ln['line_total_with_tax'] = $this->round2($netLine + $taxLine);
            }
            unset($ln);

            $totalDue = $this->round2(($subtotal - $discountTotal) + $taxTotal);
            if ($totalDue < 0) {
                $totalDue = 0.0;
            }

            $paidTenderedTotal = 0.0;
            foreach ($payloadForTransaction['payments'] as $p) {
                $paidTenderedTotal += (float) $p['amount'];
            }
            $paidTenderedTotal = $this->round2($paidTenderedTotal);

            $paidNetTotal = $this->round2(min($paidTenderedTotal, $totalDue));
            $pendingTotal = $this->round2(max(0.0, $totalDue - $paidNetTotal));

            $paymentConditionTx = (string) ($payloadForTransaction['paymentCondition'] ?? 'CONTADO');
            $saleStatus = 'PAID';
            if ($paymentConditionTx === 'CONTADO') {
                if ($paidTenderedTotal + 0.0001 < $totalDue) {
                    return ['success' => false, 'error' => 'Cobro inconsistente: en contado el pago debe cubrir el total.'];
                }
                $saleStatus = 'PAID';
            } else {
                // En crédito el recibido no puede exceder el total; lo restante queda como saldo pendiente.
                if ($paidTenderedTotal - 0.0001 > $totalDue) {
                    return ['success' => false, 'error' => 'Cobro inconsistente: en crédito el recibido no puede exceder el total.'];
                }
                $saleStatus = $paidTenderedTotal + 0.0001 < $totalDue ? 'OPEN' : 'PAID';
            }

            $change = $this->round2(max(0.0, $paidTenderedTotal - $totalDue));

            // Generar folio (retry por colisión).
            $folio = null;
            $saleId = null;
            $lastError = null;
            for ($attempt = 0; $attempt < 3; $attempt++) {
                $folioCandidate = $this->salesRepo->nextFolio($shopId);
                try {
                    $folio = $folioCandidate;
                    $saleId = $this->salesRepo->createSale(
                        $shopId,
                        $payloadForTransaction['customerId'],
                        $cashSessionId,
                        (int) $folio,
                        $saleStatus,
                        $subtotal,
                        $discountTotal,
                        $taxTotal,
                        $totalDue,
                        $paidNetTotal,
                        $payloadForTransaction['notes'],
                        $userId
                    );
                    break;
                } catch (\Throwable $e) {
                    $lastError = $e;
                    // Si colisiona el folio, reintentamos.
                    continue;
                }
            }
            if ($saleId === null || $folio === null) {
                $msg = 'No se pudo confirmar la venta. Inténtalo nuevamente.';
                if ($lastError instanceof \Throwable) {
                    // No exponemos detalles internos.
                }
                return ['success' => false, 'error' => $msg];
            }

            // Persistir items.
            $itemsToInsert = array_map(function ($ln) {
                return [
                    'product_id' => (int) $ln['product_id'],
                    'quantity' => (float) $ln['quantity'],
                    'unit_price' => (float) $ln['unit_price'],
                    'cost_snapshot' => (float) $ln['cost_snapshot'],
                    'tax_percent' => (float) $ln['tax_percent'],
                    'line_subtotal' => (float) ($ln['line_subtotal'] ?? 0),
                    'discount_amount' => (float) ($ln['discount_amount'] ?? 0),
                    'tax_amount' => (float) ($ln['tax_amount'] ?? 0),
                    'line_total' => (float) $ln['line_gross'],
                ];
            }, $lines);
            $this->salesRepo->insertSaleItems($saleId, $itemsToInsert);

            // Persistir pagos:
            // En el cálculo de caja esperada (`CashService`) se suman `sale_payments.amount`.
            // Por eso, guardamos el NETO que realmente se queda en caja (total a pagar),
            // aunque el usuario haya registrado un recibido mayor (cambio).
            $paymentsToInsert = [];
            $tenderedSum = $paidTenderedTotal > 0 ? $paidTenderedTotal : 0.0;
            $remainingNet = $paidNetTotal;
            $paymentCount = count($payloadForTransaction['payments']);
            foreach ($payloadForTransaction['payments'] as $idx => $p) {
                $method = (string) ($p['payment_method'] ?? 'EFECTIVO');
                $amountTendered = (float) ($p['amount'] ?? 0);
                $ref = isset($p['reference']) ? $this->normalizePaymentReference($p['reference']) : null;
                $netAmount = 0.0;
                if ($idx === $paymentCount - 1) {
                    $netAmount = $remainingNet;
                } else {
                    if ($tenderedSum > 0) {
                        $netAmount = $this->round2(($amountTendered / $tenderedSum) * $paidNetTotal);
                    }
                    $netAmount = max(0.0, min($netAmount, $remainingNet));
                }
                $remainingNet = $this->round2($remainingNet - $netAmount);
                if ($netAmount > 0.0001) {
                    $paymentsToInsert[] = [
                        'payment_method' => $method,
                        'amount' => $netAmount,
                        'reference' => $ref,
                    ];
                }
            }
            if ($paymentsToInsert !== []) {
                $this->salesRepo->insertSalePayments($saleId, $paymentsToInsert);
            }

            // Para inventario, enlazamos cash_movement_id solo si existe.
            // En este MVP no generamos cash_movements desde la venta (evitamos doble conteo).
            $cashMovementInId = null;

            // Registrar movimientos de inventario + actualizar stock.
            foreach ($lines as $ln) {
                if (!(bool) $ln['is_inventory_item']) {
                    continue;
                }
                $productId = (int) $ln['product_id'];
                $qty = (float) $ln['quantity'];
                $this->productRepo->decrementStock($shopId, $productId, $qty);
                $this->inventoryRepo->createOut(
                    $shopId,
                    $productId,
                    $saleId,
                    $cashMovementInId,
                    $qty,
                    'Salida por venta POS folio #' . $folio,
                    $userId
                );
            }

            return [
                'success' => true,
                'sale_id' => $saleId,
                'folio' => (int) $folio,
                'totals' => [
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'total' => $totalDue,
                    'paid_total' => $paidNetTotal,
                    'change' => $change,
                    'pending' => $pendingTotal,
                ],
            ];
            });
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage() ?: 'No se pudo confirmar la venta.'];
        }

        if (!($result['success'] ?? false)) {
            return $result;
        }

        $saleId = (int) ($result['sale_id'] ?? 0);
        $folio = (int) ($result['folio'] ?? 0);
        $saleData = $this->salesRepo->getSaleForTicket($saleId, $shopId);
        $shopRow = $this->shopRepo->findById($shopId);
        $ticketHtml = $this->buildTicketHtml($saleData, $folio, $result['totals'] ?? [], $shopRow);

        return [
            'success' => true,
            'sale_id' => $saleId,
            'folio' => $folio,
            'ticket_html' => $ticketHtml,
            'totals' => $result['totals'] ?? [],
        ];
    }

    /**
     * Genera el HTML del ticket para reimpresión (historial de ventas / vista ticket).
     */
    public function htmlTicketForSale(int $saleId, int $shopId): ?string
    {
        $saleData = $this->salesRepo->getSaleForTicket($saleId, $shopId);
        if (!$saleData) {
            return null;
        }
        $shopRow = $this->shopRepo->findById($shopId);
        $folio = (int) (($saleData['sale']['folio'] ?? 0));
        $s = $saleData['sale'] ?? [];
        $totalDue = (float) ($s['total'] ?? 0);
        $paidTotal = (float) ($s['paid_total'] ?? 0);
        $totals = [
            'subtotal' => (float) ($s['subtotal'] ?? 0),
            'discount_total' => (float) ($s['discount_total'] ?? 0),
            'tax_total' => (float) ($s['tax_total'] ?? 0),
            'total' => $totalDue,
            'paid_total' => $paidTotal,
            'change' => 0.0,
        ];

        return $this->buildTicketHtml($saleData, $folio, $totals, $shopRow);
    }

    /**
     * @return array{
     *   width_mm:int,
     *   base_pt:float,
     *   small_pt:float,
     *   title_pt:float,
     *   total_pt:float,
     *   ref_pt:float,
     *   footer_pt:float,
     *   font_family:string,
     *   body_weight:string,
     *   th_weight:int
     * }
     */
    private function ticketAppearanceFromShop(?array $shop): array
    {
        $w = 80;
        $preset = 'sans_bold';
        $base = 13.0;
        if ($shop !== null) {
            $w = (int) ($shop['ticket_paper_width_mm'] ?? 80);
            if (!in_array($w, [58, 72, 80], true)) {
                $w = 80;
            }
            $preset = (string) ($shop['ticket_font_preset'] ?? 'sans_bold');
            $allowed = ['system', 'sans', 'sans_bold', 'mono', 'mono_bold', 'serif'];
            if (!in_array($preset, $allowed, true)) {
                $preset = 'sans_bold';
            }
            $base = (float) ($shop['ticket_font_size_pt'] ?? 13.0);
            if (!is_finite($base) || $base < 8.0) {
                $base = 8.0;
            }
            if ($base > 24.0) {
                $base = 24.0;
            }
            $base = round($base, 1);
        }

        $ff = $this->ticketFontFamilyStack($preset);
        $bodyW = in_array($preset, ['sans_bold', 'mono_bold'], true) ? '600' : '500';
        $thW = in_array($preset, ['sans_bold', 'mono_bold'], true) ? 700 : 600;

        return [
            'width_mm' => $w,
            'base_pt' => $base,
            'small_pt' => max(8.0, round($base * 0.92, 1)),
            'title_pt' => round($base * 1.12, 1),
            'total_pt' => round($base * 1.22, 1),
            'ref_pt' => max(8.0, round($base * 0.9, 1)),
            'footer_pt' => max(8.0, round($base * 0.88, 1)),
            'font_family' => $ff,
            'body_weight' => $bodyW,
            'th_weight' => $thW,
        ];
    }

    private function ticketFontFamilyStack(string $preset): string
    {
        return match ($preset) {
            'system' => 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'sans' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
            'sans_bold' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
            'mono' => '"Courier New", Courier, "Liberation Mono", monospace',
            'mono_bold' => '"Courier New", Courier, "Liberation Mono", monospace',
            'serif' => 'Georgia, "Times New Roman", Times, serif',
            default => 'Arial, Helvetica, sans-serif',
        };
    }

    /**
     * HTML del ticket con estilos según configuración de la tienda (ancho, fuente, tamaño).
     *
     * @param array|null $saleData
     * @param array<string,float|int> $totals
     * @param array<string,mixed>|null $shopRow
     */
    private function buildTicketHtml(?array $saleData, int $folio, array $totals, ?array $shopRow = null): string
    {
        $saleData = $saleData ?? [];
        $T = $this->ticketAppearanceFromShop($shopRow);
        $brandName = trim((string) (($shopRow ?? [])['name'] ?? ''));
        if ($brandName === '') {
            $brandName = 'POS SaaS';
        }
        $wmm = $T['width_mm'];
        $ff = $T['font_family'];
        $fwBody = $T['body_weight'];
        $thW = $T['th_weight'];
        $basePt = $T['base_pt'];
        $smallPt = $T['small_pt'];
        $titlePt = $T['title_pt'] + 1.8;
        $totalPt = $T['total_pt'];
        $refPt = $T['ref_pt'];
        $footerPt = $T['footer_pt'];

        $customerName = (string) ($saleData['customer_name'] ?? 'Cliente');
        $sellerName = (string) ($saleData['seller_name'] ?? 'Usuario');
        $occurredAt = (string) ($saleData['occurred_at'] ?? '');

        $items = $saleData['items'] ?? [];
        $payments = $saleData['payments'] ?? [];

        $change = (float) ($totals['change'] ?? 0);

        $subtotal = (float) ($totals['subtotal'] ?? 0);
        $discountTotal = (float) ($totals['discount_total'] ?? 0);
        $taxTotal = (float) ($totals['tax_total'] ?? 0);
        $totalDue = (float) ($totals['total'] ?? 0);
        $paidTotal = (float) ($totals['paid_total'] ?? 0);
        $pending = max(0.0, $totalDue - $paidTotal);

        $customerDebtBefore = 0.0;
        $customerDebtAfter = 0.0;

        $saleRow = $saleData['sale'] ?? [];
        $customerId = (int) ($saleRow['customer_id'] ?? 0);
        $shopId = (int) ($saleRow['shop_id'] ?? 0);
        $currentSaleId = (int) ($saleRow['id'] ?? 0);
        $saleOccurredAtDb = (string) ($saleRow['occurred_at'] ?? $occurredAt);

        if ($customerId > 0 && $shopId > 0 && $currentSaleId > 0 && $saleOccurredAtDb !== '') {
            $debtBeforeRow = Database::fetch(
                'SELECT COALESCE(SUM(s.total - s.paid_total), 0) AS debt
                 FROM sales s
                 WHERE s.shop_id = :shop_id
                   AND s.customer_id = :customer_id
                   AND s.status = "OPEN"
                   AND s.total > s.paid_total
                   AND (
                        s.occurred_at < :occurred_at_before
                        OR (s.occurred_at = :occurred_at_equal_before AND s.id < :sale_id)
                   )',
                [
                    'shop_id' => $shopId,
                    'customer_id' => $customerId,
                    'occurred_at_before' => $saleOccurredAtDb,
                    'occurred_at_equal_before' => $saleOccurredAtDb,
                    'sale_id' => $currentSaleId,
                ]
            );
            $debtAfterRow = Database::fetch(
                'SELECT COALESCE(SUM(s.total - s.paid_total), 0) AS debt
                 FROM sales s
                 WHERE s.shop_id = :shop_id
                   AND s.customer_id = :customer_id
                   AND s.status = "OPEN"
                   AND s.total > s.paid_total
                   AND (
                        s.occurred_at < :occurred_at_after
                        OR (s.occurred_at = :occurred_at_equal_after AND s.id <= :sale_id)
                   )',
                [
                    'shop_id' => $shopId,
                    'customer_id' => $customerId,
                    'occurred_at_after' => $saleOccurredAtDb,
                    'occurred_at_equal_after' => $saleOccurredAtDb,
                    'sale_id' => $currentSaleId,
                ]
            );
            $customerDebtBefore = (float) ($debtBeforeRow['debt'] ?? 0);
            $customerDebtAfter = (float) ($debtAfterRow['debt'] ?? 0);
        }

        ob_start();
        ?>
        <div class="pos-ticket" style="max-width:<?= (int) $wmm ?>mm;width:100%;margin:0 auto;box-sizing:border-box;font-family:<?= htmlspecialchars($ff, ENT_QUOTES, 'UTF-8') ?>;font-size:<?= htmlspecialchars((string) $basePt, ENT_QUOTES, 'UTF-8') ?>pt;font-weight:<?= htmlspecialchars((string) $fwBody, ENT_QUOTES, 'UTF-8') ?>;line-height:1.35;color:#000;padding:0.5mm 1mm;-webkit-print-color-adjust:exact;print-color-adjust:exact;">
            <div style="text-align:center;margin-bottom:0.5em;">
                <div style="font-weight:800;font-size:<?= htmlspecialchars((string) $titlePt, ENT_QUOTES, 'UTF-8') ?>pt;color:#000;"><?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?></div>
                <div style="font-size:<?= htmlspecialchars((string) $smallPt, ENT_QUOTES, 'UTF-8') ?>pt;color:#000;margin-top:0.2em;">Comprobante de venta</div>
            </div>
            <div style="display:flex;justify-content:space-between;gap:8px;font-size:<?= htmlspecialchars((string) $basePt, ENT_QUOTES, 'UTF-8') ?>pt;margin-bottom:0.6em;">
                <div>
                    <div><b>Folio:</b> #<?= htmlspecialchars((string) $folio, ENT_QUOTES, 'UTF-8') ?></div>
                    <div><?= htmlspecialchars((string) $occurredAt, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div style="text-align:right;">
                    <div><b>Vendedor:</b> <?= htmlspecialchars($sellerName, ENT_QUOTES, 'UTF-8') ?></div>
                    <div><b>Cliente:</b> <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <div style="border-top:1px dashed #999;border-bottom:1px dashed #999;padding:0.4em 0;">
                <div style="display:flex;justify-content:space-between;font-size:<?= htmlspecialchars((string) $basePt, ENT_QUOTES, 'UTF-8') ?>pt;">
                    <div><b>Detalle</b></div>
                    <div><b><?= htmlspecialchars((string) count($items), ENT_QUOTES, 'UTF-8') ?> partidas</b></div>
                </div>
            </div>

            <table style="width:100%;border-collapse:collapse;font-size:<?= htmlspecialchars((string) $basePt, ENT_QUOTES, 'UTF-8') ?>pt;margin-top:0.5em;">
                <thead>
                <tr>
                    <th style="text-align:left;padding-bottom:4px;font-weight:<?= (int) $thW ?>;">Producto</th>
                    <th style="text-align:right;padding-bottom:4px;font-weight:<?= (int) $thW ?>;">Cant.</th>
                    <th style="text-align:right;padding-bottom:4px;font-weight:<?= (int) $thW ?>;">P. unit.</th>
                    <th style="text-align:right;padding-bottom:4px;font-weight:<?= (int) $thW ?>;">Importe</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td style="padding: 2px 0;"><?= htmlspecialchars((string) ($it['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 2px 0; text-align:right;"><?= htmlspecialchars(number_format((float) ($it['quantity'] ?? 0), 3, '.', ','), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 2px 0; text-align:right;"><?= htmlspecialchars('$' . number_format((float) ($it['unit_price'] ?? 0), 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding: 2px 0; text-align:right;"><?= htmlspecialchars('$' . number_format((float) ($it['line_total'] ?? 0), 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:0.6em;font-size:<?= htmlspecialchars((string) $basePt, ENT_QUOTES, 'UTF-8') ?>pt;">
                <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><b>$<?= htmlspecialchars(number_format($subtotal, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></b></div>
                <div style="display:flex;justify-content:space-between;"><span>Descuento</span><b>$<?= htmlspecialchars(number_format($discountTotal, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></b></div>
                <div style="display:flex;justify-content:space-between;"><span>Impuesto</span><b>$<?= htmlspecialchars(number_format($taxTotal, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></b></div>
                <div style="display:flex;justify-content:space-between;border-top:1px solid #999;padding-top:0.35em;margin-top:0.2em;"><span>Total</span><b style="font-size:<?= htmlspecialchars((string) $totalPt, ENT_QUOTES, 'UTF-8') ?>pt;">$<?= htmlspecialchars(number_format($totalDue, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></b></div>
            </div>

            <div style="margin-top:0.6em;font-size:<?= htmlspecialchars((string) $basePt, ENT_QUOTES, 'UTF-8') ?>pt;">
                <div style="font-weight:700;margin-bottom:0.25em;">Pagos</div>
                <?php if (empty($payments)): ?>
                    <div style="color:#000;">—</div>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <div style="display:flex; justify-content:space-between;">
                            <span><?= htmlspecialchars((string) ($pay['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <b>$<?= htmlspecialchars(number_format((float) ($pay['amount'] ?? 0), 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></b>
                        </div>
                        <?php if (!empty($pay['reference'])): ?>
                            <div style="font-size:<?= htmlspecialchars((string) $refPt, ENT_QUOTES, 'UTF-8') ?>pt;color:#444;margin:0.15em 0 0.25em;">
                                Ref.: <?= htmlspecialchars((string) $pay['reference'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($pending > 0.009): ?>
                    <div style="display:flex; justify-content:space-between; margin-top: 6px;">
                        <span>Saldo pendiente</span>
                        <b style="color:#000;">
                            $<?= htmlspecialchars(number_format((float) $pending, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?>
                        </b>
                    </div>
                <?php else: ?>
                    <div style="display:flex; justify-content:space-between; margin-top: 6px;">
                        <span>Cambio</span>
                        <b style="color:#000;">
                            $<?= htmlspecialchars(number_format($change, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?>
                        </b>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($customerId > 0): ?>
                <div style="margin-top:0.6em;font-size:<?= htmlspecialchars((string) $basePt, ENT_QUOTES, 'UTF-8') ?>pt;">
                    <div style="font-weight:700;margin-bottom:0.25em;">Deuda del cliente</div>
                    <div style="display:flex; justify-content:space-between;">
                        <span>Antes de esta venta</span>
                        <b>$<?= htmlspecialchars(number_format((float) $customerDebtBefore, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></b>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-top: 4px;">
                        <span>Total incluyendo esta venta</span>
                        <b style="color:#000;">$<?= htmlspecialchars(number_format((float) $customerDebtAfter, 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></b>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($saleData['notes'])): ?>
                <div style="margin-top:0.7em;font-size:<?= htmlspecialchars((string) $basePt, ENT_QUOTES, 'UTF-8') ?>pt;">
                    <div style="font-weight:700;margin-bottom:0.25em;">Observaciones</div>
                    <div style="color:#000;"><?= htmlspecialchars((string) $saleData['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <div style="margin-top:0.7em;font-size:<?= htmlspecialchars((string) $footerPt, ENT_QUOTES, 'UTF-8') ?>pt;color:#000;text-align:center;">
                Gracias por su compra.
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function normalizePaymentReference(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }
        if (function_exists('mb_strlen') && mb_strlen($s) > 120) {
            return mb_substr($s, 0, 120);
        }
        if (strlen($s) > 120) {
            return substr($s, 0, 120);
        }

        return $s;
    }

    private function round2(float $n): float
    {
        return round($n, 2);
    }

    private function round3(float $n): float
    {
        return round($n, 3);
    }
}

