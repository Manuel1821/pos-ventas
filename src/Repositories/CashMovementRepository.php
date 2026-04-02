<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class CashMovementRepository
{
    /**
     * Registra un movimiento manual (ingreso o retiro).
     */
    public function create(
        int $shopId,
        int $cashSessionId,
        string $type,
        float $amount,
        ?string $note,
        int $createdBy
    ): int {
        if (!in_array($type, ['IN', 'OUT'], true)) {
            throw new \InvalidArgumentException('Tipo de movimiento debe ser IN u OUT');
        }
        Database::execute(
            'INSERT INTO cash_movements (shop_id, cash_session_id, type, payment_method, amount, note, occurred_at, created_by, created_at)
             VALUES (:shop_id, :cash_session_id, :type, "EFECTIVO", :amount, :note, NOW(), :created_by, NOW())',
            [
                'shop_id' => $shopId,
                'cash_session_id' => $cashSessionId,
                'type' => $type,
                'amount' => round($amount, 2),
                'note' => $note !== '' && $note !== null ? trim($note) : null,
                'created_by' => $createdBy,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Lista movimientos de una sesión (para vista de caja actual).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBySession(int $cashSessionId, int $shopId): array
    {
        return Database::fetchAll(
            'SELECT cm.id, cm.cash_session_id, cm.type, cm.amount, cm.note, cm.occurred_at, cm.created_by,
                    u.first_name AS creator_name
             FROM cash_movements cm
             LEFT JOIN users u ON u.id = cm.created_by
             WHERE cm.cash_session_id = :session_id AND cm.shop_id = :shop_id
             ORDER BY cm.occurred_at DESC',
            ['session_id' => $cashSessionId, 'shop_id' => $shopId]
        );
    }

    /**
     * Suma de montos por tipo para una sesión (solo movimientos manuales).
     *
     * @return array{IN: float, OUT: float}
     */
    public function sumBySession(int $cashSessionId): array
    {
        $rows = Database::fetchAll(
            'SELECT type, COALESCE(SUM(amount), 0) AS total FROM cash_movements WHERE cash_session_id = :id GROUP BY type',
            ['id' => $cashSessionId]
        );
        $result = ['IN' => 0.0, 'OUT' => 0.0];
        foreach ($rows as $row) {
            $result[$row['type']] = (float) $row['total'];
        }
        return $result;
    }

    /**
     * Total cobrado por ventas en esta sesión (sale_payments de ventas con cash_session_id).
     * Para el monto esperado: initial + ventas_cobradas + IN - OUT.
     */
    public function sumSalesPaidBySession(int $cashSessionId): float
    {
        $row = Database::fetch(
            'SELECT COALESCE(SUM(sp.amount), 0) AS total
             FROM sale_payments sp
             INNER JOIN sales s ON s.id = sp.sale_id AND s.cash_session_id = :session_id AND s.status IN ("PAID","OPEN")',
            ['session_id' => $cashSessionId]
        );
        return (float) ($row['total'] ?? 0);
    }
}
