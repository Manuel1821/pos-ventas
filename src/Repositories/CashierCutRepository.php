<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class CashierCutRepository
{
    /**
     * Totales de cobros por método de pago para ventas registradas por un cajero en una sesión de caja.
     *
     * @return array<int, array{payment_method:string, total_amount:float}>
     */
    public function sumPaymentsByMethodForCashierSession(int $shopId, int $cashSessionId, int $cashierUserId): array
    {
        $rows = Database::fetchAll(
            'SELECT sp.payment_method, COALESCE(SUM(sp.amount), 0) AS total_amount
             FROM sale_payments sp
             INNER JOIN sales s ON s.id = sp.sale_id
             WHERE s.shop_id = :shop_id
               AND s.cash_session_id = :session_id
               AND s.created_by = :cashier_id
               AND s.status IN ("PAID", "OPEN")
             GROUP BY sp.payment_method
             ORDER BY sp.payment_method ASC',
            [
                'shop_id' => $shopId,
                'session_id' => $cashSessionId,
                'cashier_id' => $cashierUserId,
            ]
        );
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'payment_method' => (string) ($row['payment_method'] ?? ''),
                'total_amount' => round((float) ($row['total_amount'] ?? 0), 2),
            ];
        }
        return $out;
    }

    /**
     * Ventas con componente a crédito: saldo pendiente o cargo explícito a cuenta del cliente.
     *
     * @return array{credit_sales_count:int, credit_sales_total:float, credit_pending_total:float}
     */
    public function sumCreditSalesForCashierSession(int $shopId, int $cashSessionId, int $cashierUserId): array
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS credit_sales_count,
                    COALESCE(SUM(s.total), 0) AS credit_sales_total,
                    COALESCE(SUM(s.total - s.paid_total), 0) AS credit_pending_total
             FROM sales s
             WHERE s.shop_id = :shop_id
               AND s.cash_session_id = :session_id
               AND s.created_by = :cashier_id
               AND s.status IN ("PAID", "OPEN")
               AND (
                    s.total > s.paid_total + 0.00001
                    OR EXISTS (
                        SELECT 1 FROM sale_payments spc
                        WHERE spc.sale_id = s.id
                          AND spc.payment_method = "CUENTA_CREDITO"
                          AND spc.amount > 0.00001
                    )
               )',
            [
                'shop_id' => $shopId,
                'session_id' => $cashSessionId,
                'cashier_id' => $cashierUserId,
            ]
        );
        return [
            'credit_sales_count' => (int) ($row['credit_sales_count'] ?? 0),
            'credit_sales_total' => round((float) ($row['credit_sales_total'] ?? 0), 2),
            'credit_pending_total' => round((float) ($row['credit_pending_total'] ?? 0), 2),
        ];
    }

    /**
     * Clientes con ventas a crédito en la sesión (agrupado por cliente).
     *
     * @return array<int, array{customer_id:int|null, customer_name:string, sales_count:int, total_amount:float, pending_amount:float}>
     */
    public function listCreditCustomersForCashierSession(int $shopId, int $cashSessionId, int $cashierUserId): array
    {
        $rows = Database::fetchAll(
            'SELECT s.customer_id,
                    COALESCE(NULLIF(TRIM(c.name), ""), "Sin cliente") AS customer_name,
                    COUNT(*) AS sales_count,
                    COALESCE(SUM(s.total), 0) AS total_amount,
                    COALESCE(SUM(s.total - s.paid_total), 0) AS pending_amount
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id AND c.shop_id = s.shop_id
             WHERE s.shop_id = :shop_id
               AND s.cash_session_id = :session_id
               AND s.created_by = :cashier_id
               AND s.status IN ("PAID", "OPEN")
               AND (
                    s.total > s.paid_total + 0.00001
                    OR EXISTS (
                        SELECT 1 FROM sale_payments spc
                        WHERE spc.sale_id = s.id
                          AND spc.payment_method = "CUENTA_CREDITO"
                          AND spc.amount > 0.00001
                    )
               )
             GROUP BY s.customer_id, c.name
             ORDER BY total_amount DESC, customer_name ASC',
            [
                'shop_id' => $shopId,
                'session_id' => $cashSessionId,
                'cashier_id' => $cashierUserId,
            ]
        );
        $out = [];
        foreach ($rows as $row) {
            $cid = $row['customer_id'] ?? null;
            $out[] = [
                'customer_id' => $cid !== null ? (int) $cid : null,
                'customer_name' => (string) ($row['customer_name'] ?? 'Sin cliente'),
                'sales_count' => (int) ($row['sales_count'] ?? 0),
                'total_amount' => round((float) ($row['total_amount'] ?? 0), 2),
                'pending_amount' => round((float) ($row['pending_amount'] ?? 0), 2),
            ];
        }
        return $out;
    }

    /**
     * Resumen de ventas del cajero en la sesión (importe total de ventas y número de tickets).
     *
     * @return array{sales_count:int, sales_total:float}
     */
    public function sumSalesTotalsForCashierSession(int $shopId, int $cashSessionId, int $cashierUserId): array
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS sales_count, COALESCE(SUM(s.total), 0) AS sales_total
             FROM sales s
             WHERE s.shop_id = :shop_id
               AND s.cash_session_id = :session_id
               AND s.created_by = :cashier_id
               AND s.status IN ("PAID", "OPEN")',
            [
                'shop_id' => $shopId,
                'session_id' => $cashSessionId,
                'cashier_id' => $cashierUserId,
            ]
        );
        return [
            'sales_count' => (int) ($row['sales_count'] ?? 0),
            'sales_total' => round((float) ($row['sales_total'] ?? 0), 2),
        ];
    }

    /**
     * Ingresos y retiros manuales de caja registrados por el usuario en la sesión.
     *
     * @return array{in_total:float, out_total:float}
     */
    public function sumManualMovementsByUserSession(int $shopId, int $cashSessionId, int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT type, COALESCE(SUM(amount), 0) AS total
             FROM cash_movements
             WHERE shop_id = :shop_id
               AND cash_session_id = :session_id
               AND created_by = :user_id
             GROUP BY type',
            [
                'shop_id' => $shopId,
                'session_id' => $cashSessionId,
                'user_id' => $userId,
            ]
        );
        $in = 0.0;
        $out = 0.0;
        foreach ($rows as $row) {
            $t = (string) ($row['type'] ?? '');
            $v = round((float) ($row['total'] ?? 0), 2);
            if ($t === 'IN') {
                $in = $v;
            } elseif ($t === 'OUT') {
                $out = $v;
            }
        }
        return ['in_total' => $in, 'out_total' => $out];
    }

    /**
     * Nombre del usuario en la tienda (para el ticket).
     */
    public function getCashierName(int $userId, int $shopId): ?string
    {
        $row = Database::fetch(
            'SELECT TRIM(CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, ""))) AS full_name
             FROM users WHERE id = :id AND shop_id = :shop_id',
            ['id' => $userId, 'shop_id' => $shopId]
        );
        if ($row === null) {
            return null;
        }
        $name = trim((string) ($row['full_name'] ?? ''));
        return $name !== '' ? $name : ('Cajero #' . $userId);
    }
}
