<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use App\Repositories\CashMovementRepository;
use App\Repositories\InventoryMovementRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SalesRepository;

class SaleAdjustmentService
{
    private SalesRepository $salesRepo;
    private ProductRepository $productRepo;
    private InventoryMovementRepository $inventoryRepo;
    private CashMovementRepository $cashMovementRepo;

    public function __construct()
    {
        $this->salesRepo = new SalesRepository();
        $this->productRepo = new ProductRepository();
        $this->inventoryRepo = new InventoryMovementRepository();
        $this->cashMovementRepo = new CashMovementRepository();
    }

    /**
     * @return array{success:bool,error?:string}
     */
    public function cancelSale(
        int $shopId,
        int $saleId,
        int $userId,
        string $reason,
        ?string $notes
    ): array {
        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'error' => 'Debes capturar el motivo de cancelacion.'];
        }

        try {
            return Database::transaction(function () use ($shopId, $saleId, $userId, $reason, $notes) {
                $sale = $this->salesRepo->lockSaleForAdjustment($saleId, $shopId);
                if (!$sale) {
                    return ['success' => false, 'error' => 'La venta no existe.'];
                }
                if ((string) ($sale['status'] ?? '') !== 'PAID') {
                    return ['success' => false, 'error' => 'Solo se pueden cancelar ventas pagadas y sin ajustes previos.'];
                }
                if ($this->salesRepo->hasCancellation($saleId)) {
                    return ['success' => false, 'error' => 'Esta venta ya fue cancelada.'];
                }

                $items = $this->salesRepo->getSaleItemsForAdjustment($saleId);
                $cashSessionId = (int) ($sale['cash_session_id'] ?? 0);
                $refundAmount = (float) ($sale['paid_total'] ?? 0);
                $cashMovementId = null;
                if ($cashSessionId > 0 && $refundAmount > 0) {
                    $cashMovementId = $this->cashMovementRepo->create(
                        $shopId,
                        $cashSessionId,
                        'OUT',
                        $refundAmount,
                        'Reembolso por cancelacion venta folio #' . (int) ($sale['folio'] ?? 0),
                        $userId
                    );
                }

                foreach ($items as $it) {
                    $qty = (float) ($it['quantity'] ?? 0);
                    if ((int) ($it['is_inventory_item'] ?? 0) !== 1 || $qty <= 0) {
                        continue;
                    }
                    $productId = (int) ($it['product_id'] ?? 0);
                    $this->productRepo->incrementStock($shopId, $productId, $qty);
                    $this->inventoryRepo->createIn(
                        $shopId,
                        $productId,
                        $saleId,
                        $cashMovementId,
                        $qty,
                        'Entrada por cancelacion de venta folio #' . (int) ($sale['folio'] ?? 0),
                        $userId
                    );
                }

                $this->salesRepo->createCancellation($shopId, $saleId, $reason, $notes, $refundAmount, $cashMovementId, $userId);
                $this->salesRepo->updateSaleStatus($saleId, 'CANCELLED');

                return ['success' => true];
            });
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'No se pudo cancelar la venta.'];
        }
    }

    /**
     * @param array<int,float|string|int> $quantities
     * @return array{success:bool,error?:string}
     */
    public function returnSaleItems(
        int $shopId,
        int $saleId,
        int $userId,
        string $reason,
        ?string $notes,
        array $quantities
    ): array {
        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'error' => 'Debes capturar el motivo de devolucion.'];
        }

        try {
            return Database::transaction(function () use ($shopId, $saleId, $userId, $reason, $notes, $quantities) {
                $sale = $this->salesRepo->lockSaleForAdjustment($saleId, $shopId);
                if (!$sale) {
                    return ['success' => false, 'error' => 'La venta no existe.'];
                }
                $saleStatus = (string) ($sale['status'] ?? '');
                if (!in_array($saleStatus, ['PAID', 'REFUNDED'], true)) {
                    return ['success' => false, 'error' => 'La venta no permite devoluciones en su estado actual.'];
                }
                if ($this->salesRepo->hasCancellation($saleId)) {
                    return ['success' => false, 'error' => 'No se puede devolver una venta cancelada.'];
                }

                $saleItems = $this->salesRepo->getSaleItemsForAdjustment($saleId);
                if ($saleItems === []) {
                    return ['success' => false, 'error' => 'La venta no tiene productos para devolver.'];
                }

                $saleItemIds = array_map(fn ($x) => (int) ($x['id'] ?? 0), $saleItems);
                $alreadyReturned = $this->salesRepo->getReturnedQtyBySaleItemIds($saleItemIds);

                $returnLines = [];
                $refundTotal = 0.0;
                foreach ($saleItems as $it) {
                    $saleItemId = (int) ($it['id'] ?? 0);
                    $soldQty = (float) ($it['quantity'] ?? 0);
                    $returnedQty = (float) ($alreadyReturned[$saleItemId] ?? 0);
                    $remainingQty = round(max(0.0, $soldQty - $returnedQty), 3);
                    $raw = $quantities[$saleItemId] ?? 0;
                    $qtyToReturn = round((float) $raw, 3);
                    if ($qtyToReturn <= 0) {
                        continue;
                    }
                    if ($qtyToReturn > $remainingQty + 0.0001) {
                        return ['success' => false, 'error' => 'La devolucion excede la cantidad disponible de uno o mas productos.'];
                    }

                    $lineSubtotal = round(((float) ($it['unit_price'] ?? 0)) * $qtyToReturn, 2);
                    $taxAmount = round($lineSubtotal * (((float) ($it['tax_percent'] ?? 0)) / 100), 2);
                    $lineTotal = round($lineSubtotal + $taxAmount, 2);
                    $refundTotal = round($refundTotal + $lineTotal, 2);

                    $returnLines[] = [
                        'sale_item_id' => $saleItemId,
                        'product_id' => (int) ($it['product_id'] ?? 0),
                        'quantity' => $qtyToReturn,
                        'unit_price' => (float) ($it['unit_price'] ?? 0),
                        'tax_percent' => (float) ($it['tax_percent'] ?? 0),
                        'line_subtotal' => $lineSubtotal,
                        'tax_amount' => $taxAmount,
                        'line_total' => $lineTotal,
                        'is_inventory_item' => (int) ($it['is_inventory_item'] ?? 0) === 1,
                    ];
                }

                if ($returnLines === []) {
                    return ['success' => false, 'error' => 'Debes indicar al menos una cantidad valida para devolver.'];
                }

                $cashSessionId = (int) ($sale['cash_session_id'] ?? 0);
                $cashMovementId = null;
                if ($cashSessionId > 0 && $refundTotal > 0) {
                    $cashMovementId = $this->cashMovementRepo->create(
                        $shopId,
                        $cashSessionId,
                        'OUT',
                        $refundTotal,
                        'Reembolso por devolucion venta folio #' . (int) ($sale['folio'] ?? 0),
                        $userId
                    );
                }

                $saleReturnId = $this->salesRepo->createReturn($shopId, $saleId, $reason, $notes, $refundTotal, $cashMovementId, $userId);
                $this->salesRepo->insertReturnItems($saleReturnId, $returnLines);

                foreach ($returnLines as $line) {
                    if (!$line['is_inventory_item']) {
                        continue;
                    }
                    $this->productRepo->incrementStock($shopId, (int) $line['product_id'], (float) $line['quantity']);
                    $this->inventoryRepo->createIn(
                        $shopId,
                        (int) $line['product_id'],
                        $saleId,
                        $cashMovementId,
                        (float) $line['quantity'],
                        'Entrada por devolucion de venta folio #' . (int) ($sale['folio'] ?? 0),
                        $userId
                    );
                }

                $this->salesRepo->updateSaleStatus($saleId, 'REFUNDED');
                return ['success' => true];
            });
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'No se pudo registrar la devolucion.'];
        }
    }
}

