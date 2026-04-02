<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class QuotationRepository
{
    private const PER_PAGE = 20;

    /**
     * @param array{q?:string,status?:string,sort?:string} $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,total_pages:int}
     */
    public function listForShop(int $shopId, int $page, array $filters): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        $q = trim((string) ($filters['q'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        if (!in_array($status, ['', 'OPEN', 'SOLD'], true)) {
            $status = '';
        }

        $sort = (string) ($filters['sort'] ?? 'date_desc');
        $orderSql = 'q.created_at DESC, q.id DESC';
        if ($sort === 'date_asc') {
            $orderSql = 'q.created_at ASC, q.id ASC';
        } elseif ($sort === 'total_desc') {
            $orderSql = 'q.total DESC, q.id DESC';
        } elseif ($sort === 'total_asc') {
            $orderSql = 'q.total ASC, q.id ASC';
        }

        $where = ['q.shop_id = :shop_id'];
        $params = ['shop_id' => $shopId];

        if ($status !== '') {
            $where[] = 'q.status = :status';
            $params['status'] = $status;
        }

        if ($q !== '') {
            $where[] = '(
                q.folio LIKE :qfol
                OR c.name LIKE :qcust
                OR CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) LIKE :quser
                OR CONCAT(COALESCE(cb.first_name, ""), " ", COALESCE(cb.last_name, "")) LIKE :qcb
                OR EXISTS (
                    SELECT 1 FROM quotation_items qi
                    INNER JOIN products p ON p.id = qi.product_id AND p.shop_id = q.shop_id
                    WHERE qi.quotation_id = q.id
                      AND (p.name LIKE :qprod OR p.sku LIKE :qsku OR qi.product_name LIKE :qpin)
                )
            )';
            $term = '%' . $q . '%';
            $params['qfol'] = $term;
            $params['qcust'] = $term;
            $params['quser'] = $term;
            $params['qcb'] = $term;
            $params['qprod'] = $term;
            $params['qsku'] = $term;
            $params['qpin'] = $term;
        }

        $whereSql = implode(' AND ', $where);

        $countRow = Database::fetch(
            "SELECT COUNT(*) AS c
             FROM quotations q
             LEFT JOIN customers c ON c.id = q.customer_id
             LEFT JOIN users u ON u.id = q.seller_id
             LEFT JOIN users cb ON cb.id = q.created_by
             WHERE {$whereSql}",
            $params
        );
        $total = (int) ($countRow['c'] ?? 0);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        $lim = (int) self::PER_PAGE;
        $off = (int) $offset;

        $items = Database::fetchAll(
            "SELECT q.*,
                    c.name AS customer_name,
                    TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS seller_name,
                    TRIM(CONCAT(COALESCE(cb.first_name, ''), ' ', COALESCE(cb.last_name, ''))) AS created_by_name
             FROM quotations q
             LEFT JOIN customers c ON c.id = q.customer_id
             LEFT JOIN users u ON u.id = q.seller_id
             LEFT JOIN users cb ON cb.id = q.created_by
             WHERE {$whereSql}
             ORDER BY {$orderSql}
             LIMIT {$lim} OFFSET {$off}",
            $params
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
        ];
    }

    public function nextFolio(int $shopId): int
    {
        $row = Database::fetch(
            'SELECT COALESCE(MAX(folio), 0) + 1 AS n FROM quotations WHERE shop_id = :shop_id',
            ['shop_id' => $shopId]
        );
        return max(1, (int) ($row['n'] ?? 1));
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findById(int $id, int $shopId): ?array
    {
        $row = Database::fetch(
            'SELECT q.*,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    c.email AS customer_email,
                    TRIM(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) AS seller_name,
                    TRIM(CONCAT(COALESCE(cb.first_name, ""), " ", COALESCE(cb.last_name, ""))) AS created_by_name
             FROM quotations q
             LEFT JOIN customers c ON c.id = q.customer_id
             LEFT JOIN users u ON u.id = q.seller_id
             LEFT JOIN users cb ON cb.id = q.created_by
             WHERE q.id = :id AND q.shop_id = :shop_id
             LIMIT 1',
            ['id' => $id, 'shop_id' => $shopId]
        );
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getItems(int $quotationId): array
    {
        return Database::fetchAll(
            'SELECT qi.*, p.sku, p.barcode
             FROM quotation_items qi
             INNER JOIN products p ON p.id = qi.product_id
             WHERE qi.quotation_id = :qid
             ORDER BY qi.id ASC',
            ['qid' => $quotationId]
        );
    }

    /**
     * @param array{
     *   customer_id:?int,
     *   seller_id:?int,
     *   valid_from:string,
     *   valid_to:?string,
     *   delivery_address:?string,
     *   note_to_customer:?string,
     *   subtotal:float,
     *   discount_total:float,
     *   tax_total:float,
     *   total:float,
     *   created_by:int
     * } $data
     * @param array<int,array<string,mixed>> $lines
     */
    public function create(int $shopId, int $folio, string $status, array $data, array $lines): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::execute(
                'INSERT INTO quotations (
                    shop_id, folio, customer_id, seller_id, status,
                    valid_from, valid_to, delivery_address, note_to_customer,
                    subtotal, discount_total, tax_total, total, created_by
                ) VALUES (
                    :shop_id, :folio, :customer_id, :seller_id, :status,
                    :valid_from, :valid_to, :delivery_address, :note_to_customer,
                    :subtotal, :discount_total, :tax_total, :total, :created_by
                )',
                [
                    'shop_id' => $shopId,
                    'folio' => $folio,
                    'customer_id' => $data['customer_id'],
                    'seller_id' => $data['seller_id'],
                    'status' => $status,
                    'valid_from' => $data['valid_from'],
                    'valid_to' => $data['valid_to'],
                    'delivery_address' => $data['delivery_address'],
                    'note_to_customer' => $data['note_to_customer'],
                    'subtotal' => $data['subtotal'],
                    'discount_total' => $data['discount_total'],
                    'tax_total' => $data['tax_total'],
                    'total' => $data['total'],
                    'created_by' => $data['created_by'],
                ]
            );
            $quotationId = (int) $pdo->lastInsertId();

            foreach ($lines as $ln) {
                Database::execute(
                    'INSERT INTO quotation_items (
                        quotation_id, product_id, product_name, quantity, unit_price, tax_percent,
                        line_subtotal, tax_amount, line_total
                    ) VALUES (
                        :quotation_id, :product_id, :product_name, :quantity, :unit_price, :tax_percent,
                        :line_subtotal, :tax_amount, :line_total
                    )',
                    [
                        'quotation_id' => $quotationId,
                        'product_id' => (int) $ln['product_id'],
                        'product_name' => (string) $ln['product_name'],
                        'quantity' => (float) $ln['quantity'],
                        'unit_price' => (float) $ln['unit_price'],
                        'tax_percent' => (float) $ln['tax_percent'],
                        'line_subtotal' => (float) $ln['line_subtotal'],
                        'tax_amount' => (float) $ln['tax_amount'],
                        'line_total' => (float) $ln['line_total'],
                    ]
                );
            }

            $pdo->commit();
            return $quotationId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function markSold(int $id, int $shopId): bool
    {
        $affected = Database::execute(
            'UPDATE quotations SET status = "SOLD", updated_at = NOW()
             WHERE id = :id AND shop_id = :shop_id AND status = "OPEN"',
            ['id' => $id, 'shop_id' => $shopId]
        );
        return $affected > 0;
    }
}
