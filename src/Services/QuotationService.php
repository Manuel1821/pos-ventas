<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use App\Repositories\QuotationRepository;

class QuotationService
{
    private ProductRepository $productRepo;
    private QuotationRepository $quotationRepo;
    private CustomerRepository $customerRepo;

    public function __construct()
    {
        $this->productRepo = new ProductRepository();
        $this->quotationRepo = new QuotationRepository();
        $this->customerRepo = new CustomerRepository();
    }

    /**
     * @param array{
     *   items: array<int, array{product_id:int, quantity:float|string, unit_price?:float}>,
     *   customer_id?: int|null,
     *   seller_id?: int|null,
     *   valid_from?: string,
     *   valid_to?: string|null,
     *   delivery_address?: string|null,
     *   note_to_customer?: string|null
     * } $payload
     * @return array{success:bool, error?:string, quotation_id?:int, folio?:int}
     */
    public function create(array $payload, int $shopId, int $userId): array
    {
        $items = $payload['items'] ?? [];
        if (!is_array($items) || $items === []) {
            return ['success' => false, 'error' => 'Agrega al menos un producto a la cotización.'];
        }

        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : null;
        if ($customerId !== null && $customerId <= 0) {
            $customerId = null;
        }
        if ($customerId !== null) {
            $cust = $this->customerRepo->findById($customerId, $shopId);
            if (!$cust) {
                return ['success' => false, 'error' => 'Cliente no encontrado.'];
            }
        }

        $sellerId = isset($payload['seller_id']) ? (int) $payload['seller_id'] : null;
        if ($sellerId !== null && $sellerId <= 0) {
            $sellerId = null;
        }
        if ($sellerId !== null) {
            $u = Database::fetch(
                'SELECT id FROM users WHERE id = :id AND shop_id = :shop_id LIMIT 1',
                ['id' => $sellerId, 'shop_id' => $shopId]
            );
            if (!$u) {
                return ['success' => false, 'error' => 'Vendedor no válido para esta tienda.'];
            }
        }

        $validFrom = trim((string) ($payload['valid_from'] ?? ''));
        if ($validFrom === '') {
            $validFrom = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $validFrom)) {
            return ['success' => false, 'error' => 'Fecha "válida desde" inválida.'];
        }

        $validToRaw = $payload['valid_to'] ?? null;
        $validTo = null;
        if ($validToRaw !== null && $validToRaw !== '') {
            $vt = trim((string) $validToRaw);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $vt)) {
                $validTo = $vt;
            } else {
                return ['success' => false, 'error' => 'Fecha "válida hasta" inválida.'];
            }
        }
        if ($validTo !== null && $validTo < $validFrom) {
            return ['success' => false, 'error' => 'La fecha "válida hasta" no puede ser anterior a "válida desde".'];
        }

        $delivery = isset($payload['delivery_address']) ? trim((string) $payload['delivery_address']) : null;
        if ($delivery === '') {
            $delivery = null;
        }
        if ($delivery !== null && strlen($delivery) > 500) {
            $delivery = substr($delivery, 0, 500);
        }

        $note = isset($payload['note_to_customer']) ? trim((string) $payload['note_to_customer']) : null;
        if ($note === '') {
            $note = null;
        }

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
            return ['success' => false, 'error' => 'Productos o cantidades inválidas.'];
        }

        $lines = [];
        $subtotal = 0.0;
        foreach ($cartItems as $line) {
            $pid = (int) $line['product_id'];
            $qty = (float) $line['quantity'];
            $product = $this->productRepo->findById($pid, $shopId);
            if (!$product || (string) ($product['status'] ?? '') !== 'ACTIVE') {
                return ['success' => false, 'error' => 'Uno de los productos no está disponible.'];
            }

            $catalogUnit = (float) ($product['price'] ?? 0);
            $unitPrice = $catalogUnit;
            if (isset($line['unit_price'])) {
                $unitPrice = (float) $line['unit_price'];
            }
            $taxPercent = (float) ($product['tax_percent'] ?? 0);
            $lineGross = $this->round2($unitPrice * $qty);
            $subtotal += $lineGross;

            $taxLine = $this->round2($lineGross * ($taxPercent / 100));
            $lineTotal = $this->round2($lineGross + $taxLine);

            $lines[] = [
                'product_id' => $pid,
                'product_name' => (string) ($product['name'] ?? 'Producto'),
                'quantity' => $qty,
                'unit_price' => $this->round2($unitPrice),
                'tax_percent' => $this->round2($taxPercent),
                'line_subtotal' => $lineGross,
                'tax_amount' => $taxLine,
                'line_total' => $lineTotal,
            ];
        }

        $subtotal = $this->round2($subtotal);
        $discountTotal = 0.0;
        $taxTotal = 0.0;
        foreach ($lines as $ln) {
            $taxTotal = $this->round2($taxTotal + (float) $ln['tax_amount']);
        }
        $total = $this->round2($subtotal - $discountTotal + $taxTotal);

        $data = [
            'customer_id' => $customerId,
            'seller_id' => $sellerId,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
            'delivery_address' => $delivery,
            'note_to_customer' => $note,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'created_by' => $userId,
        ];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $folio = $this->quotationRepo->nextFolio($shopId);
            try {
                $qid = $this->quotationRepo->create($shopId, $folio, 'OPEN', $data, $lines);
                return ['success' => true, 'quotation_id' => $qid, 'folio' => $folio];
            } catch (\Throwable $e) {
                // Reintento por posible colisión de folio.
            }
        }

        return ['success' => false, 'error' => 'No se pudo guardar la cotización. Inténtalo de nuevo.'];
    }

    private function round2(float $n): float
    {
        return round($n, 2, PHP_ROUND_HALF_UP);
    }

    private function round3(float $n): float
    {
        return round($n, 3, PHP_ROUND_HALF_UP);
    }
}
