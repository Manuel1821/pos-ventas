<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class InventoryMovementRepository
{
    /**
     * Crea movimientos de inventario OUT ligados a una venta.
     */
    public function createOut(
        int $shopId,
        int $productId,
        int $saleId,
        ?int $cashMovementId,
        float $qty,
        ?string $note,
        int $createdBy
    ): void {
        Database::execute(
            'INSERT INTO inventory_movements (shop_id, product_id, sale_id, cash_movement_id, type, quantity_change, note, occurred_at, created_by, created_at)
             VALUES (:shop_id, :product_id, :sale_id, :cash_movement_id, "OUT", :qty, :note, NOW(), :created_by, NOW())',
            [
                'shop_id' => $shopId,
                'product_id' => $productId,
                'sale_id' => $saleId,
                'cash_movement_id' => $cashMovementId,
                'qty' => round($qty, 3),
                'note' => $note !== null && $note !== '' ? trim($note) : null,
                'created_by' => $createdBy,
            ]
        );
    }

    /**
     * Crea movimientos de inventario IN ligados a una venta/devolución.
     */
    public function createIn(
        int $shopId,
        int $productId,
        int $saleId,
        ?int $cashMovementId,
        float $qty,
        ?string $note,
        int $createdBy
    ): void {
        Database::execute(
            'INSERT INTO inventory_movements (shop_id, product_id, sale_id, cash_movement_id, type, quantity_change, note, occurred_at, created_by, created_at)
             VALUES (:shop_id, :product_id, :sale_id, :cash_movement_id, "IN", :qty, :note, NOW(), :created_by, NOW())',
            [
                'shop_id' => $shopId,
                'product_id' => $productId,
                'sale_id' => $saleId,
                'cash_movement_id' => $cashMovementId,
                'qty' => round($qty, 3),
                'note' => $note !== null && $note !== '' ? trim($note) : null,
                'created_by' => $createdBy,
            ]
        );
    }

    /**
     * Para enlazar inventario con el movimiento IN correspondiente a la venta.
     *
     * Nota: como CashService::addMovement no retorna el id del movimiento,
     * determinamos el id consultando por nota/importe (MVP).
     */
    public function getLastCashMovementInIdForSale(
        int $shopId,
        int $cashSessionId,
        string $note,
        float $amount,
        int $createdBy
    ): ?int {
        $row = Database::fetch(
            'SELECT cm.id
             FROM cash_movements cm
             WHERE cm.shop_id = :shop_id
               AND cm.cash_session_id = :session_id
               AND cm.type = "IN"
               AND cm.payment_method = "EFECTIVO"
               AND cm.amount = :amount
               AND cm.note = :note
               AND cm.created_by = :created_by
             ORDER BY cm.occurred_at DESC
             LIMIT 1',
            [
                'shop_id' => $shopId,
                'session_id' => $cashSessionId,
                'amount' => round($amount, 2),
                'note' => $note,
                'created_by' => $createdBy,
            ]
        );
        if (!$row) {
            return null;
        }
        $id = $row['id'] ?? null;
        return $id !== null ? (int) $id : null;
    }
}

