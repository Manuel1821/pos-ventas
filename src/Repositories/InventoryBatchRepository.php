<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class InventoryBatchRepository
{
    private const PER_PAGE = 20;

    /**
     * @param 'todos'|'proximos_30'|'vencidos'|'sin_fecha' $vencimiento
     * @return array{items: array<int, array<string, mixed>>, total: int, page: int, per_page: int, total_pages: int}
     */
    public function listByShop(
        int $shopId,
        int $page = 1,
        ?int $productId = null,
        string $q = '',
        string $vencimiento = 'todos'
    ): array {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;
        $params = ['shop_id' => $shopId];
        $where = ['b.shop_id = :shop_id'];

        if ($productId !== null && $productId > 0) {
            $where[] = 'b.product_id = :product_id';
            $params['product_id'] = $productId;
        }
        if ($q !== '') {
            $where[] = '(b.lot_code LIKE :q OR p.name LIKE :q2 OR IFNULL(p.sku, "") LIKE :q3)';
            $term = '%' . $q . '%';
            $params['q'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
        }

        $today = date('Y-m-d');
        if ($vencimiento === 'proximos_30') {
            $where[] = 'b.expiry_date IS NOT NULL AND b.expiry_date >= :today_a AND b.expiry_date <= DATE_ADD(:today_b, INTERVAL 30 DAY)';
            $params['today_a'] = $today;
            $params['today_b'] = $today;
        } elseif ($vencimiento === 'vencidos') {
            $where[] = 'b.expiry_date IS NOT NULL AND b.expiry_date < :today_v';
            $params['today_v'] = $today;
        } elseif ($vencimiento === 'sin_fecha') {
            $where[] = 'b.expiry_date IS NULL';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS total FROM inventory_batches b INNER JOIN products p ON p.id = b.product_id WHERE {$whereSql}";
        $total = (int) (Database::fetch($countSql, $params)['total'] ?? 0);

        $sql = "SELECT b.id, b.shop_id, b.product_id, b.lot_code, b.expiry_date, b.quantity, b.notes, b.created_at, b.updated_at,
                       p.name AS product_name, p.sku AS product_sku, p.unit AS product_unit
                FROM inventory_batches b
                INNER JOIN products p ON p.id = b.product_id
                WHERE {$whereSql}
                ORDER BY
                  CASE WHEN b.expiry_date IS NULL THEN 1 ELSE 0 END,
                  b.expiry_date ASC,
                  b.lot_code ASC
                LIMIT " . self::PER_PAGE . ' OFFSET ' . (int) $offset;

        $items = Database::fetchAll($sql, $params);

        $totalPages = $total > 0 ? (int) ceil($total / self::PER_PAGE) : 1;

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => self::PER_PAGE,
            'total_pages' => $totalPages,
        ];
    }

    public function findById(int $id, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT b.id, b.shop_id, b.product_id, b.lot_code, b.expiry_date, b.quantity, b.notes, b.created_at, b.updated_at,
                    p.name AS product_name
             FROM inventory_batches b
             INNER JOIN products p ON p.id = b.product_id
             WHERE b.id = :id AND b.shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId]
        );
    }

    public function existsLotForProduct(int $shopId, int $productId, string $lotCode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM inventory_batches WHERE shop_id = :shop_id AND product_id = :product_id AND lot_code = :lot_code';
        $params = ['shop_id' => $shopId, 'product_id' => $productId, 'lot_code' => $lotCode];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';

        return Database::fetch($sql, $params) !== null;
    }

    public function create(int $shopId, int $productId, string $lotCode, float $quantity, ?string $expiryDate, ?string $notes): int
    {
        Database::execute(
            'INSERT INTO inventory_batches (shop_id, product_id, lot_code, expiry_date, quantity, notes, created_at)
             VALUES (:shop_id, :product_id, :lot_code, :expiry_date, :quantity, :notes, NOW())',
            [
                'shop_id' => $shopId,
                'product_id' => $productId,
                'lot_code' => $lotCode,
                'expiry_date' => $expiryDate,
                'quantity' => round($quantity, 3),
                'notes' => $notes,
            ]
        );

        return (int) Database::pdo()->lastInsertId();
    }

    public function update(int $id, int $shopId, int $productId, string $lotCode, float $quantity, ?string $expiryDate, ?string $notes): bool
    {
        $n = Database::execute(
            'UPDATE inventory_batches
             SET product_id = :product_id, lot_code = :lot_code, expiry_date = :expiry_date, quantity = :quantity, notes = :notes, updated_at = NOW()
             WHERE id = :id AND shop_id = :shop_id',
            [
                'id' => $id,
                'shop_id' => $shopId,
                'product_id' => $productId,
                'lot_code' => $lotCode,
                'expiry_date' => $expiryDate,
                'quantity' => round($quantity, 3),
                'notes' => $notes,
            ]
        );

        return $n > 0;
    }

    public function delete(int $id, int $shopId): bool
    {
        $n = Database::execute(
            'DELETE FROM inventory_batches WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId]
        );

        return $n > 0;
    }
}
