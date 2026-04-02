<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class DashboardRepository
{
    private const EXCLUDE_SALE_STATUS = "('CANCELLED','REFUNDED')";

    /**
     * @return array{total:float,count:int}
     */
    public function getSalesTotalsForDay(int $shopId, string $dateYmd): array
    {
        $row = Database::fetch(
            'SELECT COALESCE(SUM(s.total), 0) AS total, COUNT(*) AS cnt
             FROM sales s
             WHERE s.shop_id = :shop_id
               AND s.status NOT IN ' . self::EXCLUDE_SALE_STATUS . '
               AND DATE(s.occurred_at) = :d',
            ['shop_id' => $shopId, 'd' => $dateYmd]
        );
        return [
            'total' => (float) ($row['total'] ?? 0),
            'count' => (int) ($row['cnt'] ?? 0),
        ];
    }

    /**
     * Ventas del mes calendario actual.
     *
     * @return array{total:float,count:int}
     */
    public function getSalesTotalsCurrentMonth(int $shopId): array
    {
        $row = Database::fetch(
            'SELECT COALESCE(SUM(s.total), 0) AS total, COUNT(*) AS cnt
             FROM sales s
             WHERE s.shop_id = :shop_id
               AND s.status NOT IN ' . self::EXCLUDE_SALE_STATUS . '
               AND YEAR(s.occurred_at) = YEAR(CURDATE())
               AND MONTH(s.occurred_at) = MONTH(CURDATE())',
            ['shop_id' => $shopId]
        );
        return [
            'total' => (float) ($row['total'] ?? 0),
            'count' => (int) ($row['cnt'] ?? 0),
        ];
    }

    /**
     * @return list<array{id:int,folio:int,total:float,status:string,occurred_at:string,customer_name:?string,seller_name:?string}>
     */
    public function getRecentSales(int $shopId, int $limit = 6): array
    {
        $limit = max(1, min(20, $limit));
        $sql = 'SELECT s.id, s.folio, s.total, s.status, s.occurred_at,
                       c.name AS customer_name,
                       TRIM(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) AS seller_name
                FROM sales s
                LEFT JOIN customers c ON c.id = s.customer_id
                LEFT JOIN users u ON u.id = s.created_by
                WHERE s.shop_id = :shop_id
                  AND s.status NOT IN ' . self::EXCLUDE_SALE_STATUS . '
                ORDER BY s.occurred_at DESC, s.id DESC
                LIMIT ' . (int) $limit;
        $rows = Database::fetchAll($sql, ['shop_id' => $shopId]);
        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array{products:int,customers:int}
     */
    public function getCatalogCounts(int $shopId): array
    {
        $p = Database::fetch(
            'SELECT COUNT(*) AS c FROM products WHERE shop_id = :shop_id AND status = "ACTIVE"',
            ['shop_id' => $shopId]
        );
        $c = Database::fetch(
            'SELECT COUNT(*) AS c FROM customers WHERE shop_id = :shop_id AND status = "ACTIVE"',
            ['shop_id' => $shopId]
        );
        return [
            'products' => (int) ($p['c'] ?? 0),
            'customers' => (int) ($c['c'] ?? 0),
        ];
    }
}
