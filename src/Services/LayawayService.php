<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use App\Repositories\CustomerRepository;
use App\Repositories\LayawayRepository;
use App\Repositories\ProductRepository;

class LayawayService
{
    private ProductRepository $productRepo;
    private LayawayRepository $layawayRepo;
    private CustomerRepository $customerRepo;

    public function __construct()
    {
        $this->productRepo = new ProductRepository();
        $this->layawayRepo = new LayawayRepository();
        $this->customerRepo = new CustomerRepository();
    }

    /**
     * @param array{
     *   items: array<int, array{product_id:int, quantity:float|string, unit_price?:float}>,
     *   customer_id?: int|null,
     *   seller_id?: int|null,
     *   starts_at?: string,
     *   due_date?: string|null,
     *   note_to_customer?: string|null,
     *   down_payment?: float|string|null
     * } $payload
     * @return array{success:bool, error?:string, layaway_id?:int, folio?:int}
     */
    public function create(array $payload, int $shopId, int $userId): array
    {
        $items = $payload['items'] ?? [];
        if (!is_array($items) || $items === []) {
            return ['success' => false, 'error' => 'Agrega al menos un producto al apartado.'];
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

        $startsAt = trim((string) ($payload['starts_at'] ?? ''));
        if ($startsAt === '') {
            $startsAt = date('Y-m-d');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startsAt)) {
            return ['success' => false, 'error' => 'Fecha de inicio inválida.'];
        }

        $dueDateRaw = $payload['due_date'] ?? null;
        $dueDate = null;
        if ($dueDateRaw !== null && $dueDateRaw !== '') {
            $tmp = trim((string) $dueDateRaw);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tmp)) {
                return ['success' => false, 'error' => 'Fecha límite inválida.'];
            }
            $dueDate = $tmp;
            if ($dueDate < $startsAt) {
                return ['success' => false, 'error' => 'La fecha límite no puede ser anterior a la fecha de inicio.'];
            }
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
            if ($productId <= 0 || !is_finite($qty) || $qty <= 0) {
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
            if ((float) ($product['stock'] ?? 0) + 0.0001 < $qty) {
                return ['success' => false, 'error' => 'Stock insuficiente para "' . (string) ($product['name'] ?? 'producto') . '".'];
            }

            $catalogUnit = (float) ($product['price'] ?? 0);
            $unitPrice = isset($line['unit_price']) ? (float) $line['unit_price'] : $catalogUnit;
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

        $downPayment = round((float) ($payload['down_payment'] ?? 0), 2);
        if ($downPayment < 0) {
            return ['success' => false, 'error' => 'El anticipo no puede ser negativo.'];
        }
        if ($downPayment > $total) {
            return ['success' => false, 'error' => 'El anticipo no puede ser mayor al total del apartado.'];
        }

        $status = $downPayment >= $total - 0.01 ? 'PAID' : 'OPEN';
        $data = [
            'customer_id' => $customerId,
            'seller_id' => $sellerId,
            'starts_at' => $startsAt,
            'due_date' => $dueDate,
            'note_to_customer' => $note,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'down_payment' => $downPayment,
            'paid_total' => $downPayment,
            'created_by' => $userId,
        ];

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $folio = $this->layawayRepo->nextFolio($shopId);
            try {
                $layawayId = $this->layawayRepo->create($shopId, $folio, $status, $data, $lines);
                return ['success' => true, 'layaway_id' => $layawayId, 'folio' => $folio];
            } catch (\Throwable $e) {
            }
        }

        return ['success' => false, 'error' => 'No se pudo guardar el apartado. Inténtalo de nuevo.'];
    }

    /**
     * @return array{success:bool,error?:string}
     */
    public function registerPayment(
        int $shopId,
        int $layawayId,
        int $userId,
        float $amount,
        string $paymentMethod,
        ?string $reference
    ): array {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'El monto del abono debe ser mayor a 0.'];
        }

        $method = strtoupper(trim($paymentMethod));
        $allowed = ['EFECTIVO', 'TRANSFERENCIA', 'TARJETA_DEBITO', 'TARJETA_CREDITO', 'OTRO'];
        if (!in_array($method, $allowed, true)) {
            return ['success' => false, 'error' => 'Forma de pago inválida.'];
        }

        $ok = $this->layawayRepo->registerPayment($layawayId, $shopId, $userId, $amount, $method, $reference);
        if (!$ok) {
            return ['success' => false, 'error' => 'No fue posible registrar el abono.'];
        }

        return ['success' => true];
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

