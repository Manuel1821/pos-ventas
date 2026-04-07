<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class LayawayRepository
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
        if (!in_array($status, ['', 'OPEN', 'PAID', 'CANCELLED'], true)) {
            $status = '';
        }

        $sort = (string) ($filters['sort'] ?? 'date_desc');

        $where = ['l.shop_id = :shop_id'];
        $params = ['shop_id' => $shopId];

        if ($status !== '') {
            $where[] = 'l.status = :status';
            $params['status'] = $status;
        }

        if ($q !== '') {
            $where[] = '(
                l.folio LIKE :qfol
                OR c.name LIKE :qcust
                OR CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) LIKE :quser
                OR EXISTS (
                    SELECT 1 FROM layaway_items li
                    INNER JOIN products p ON p.id = li.product_id AND p.shop_id = l.shop_id
                    WHERE li.layaway_id = l.id
                      AND (p.name LIKE :qprod OR p.sku LIKE :qsku OR li.product_name LIKE :qpin)
                )
            )';
            $term = '%' . $q . '%';
            $params['qfol'] = $term;
            $params['qcust'] = $term;
            $params['quser'] = $term;
            $params['qprod'] = $term;
            $params['qsku'] = $term;
            $params['qpin'] = $term;
        }

        $whereSql = implode(' AND ', $where);

        // En "Todos": solo cuentas con saldo pendiente (sin apartados ya liquidados a nivel de cuenta).
        $havingBalance = '';
        if ($status === '') {
            $havingBalance = ' HAVING (SUM(l.total) - SUM(l.paid_total)) > 0.009';
        }

        $countRow = Database::fetch(
            "SELECT COUNT(*) AS c
             FROM (
                 SELECT 1
                 FROM layaways l
                 LEFT JOIN customers c ON c.id = l.customer_id
                 LEFT JOIN users u ON u.id = l.created_by
                 WHERE {$whereSql}
                 GROUP BY IFNULL(l.customer_id, l.id)
                 {$havingBalance}
             ) grouped",
            $params
        );
        $total = (int) ($countRow['c'] ?? 0);
        $totalPages = max(1, (int) ceil($total / self::PER_PAGE));

        $lim = (int) self::PER_PAGE;
        $off = (int) $offset;

        $sortOuter = 'g.last_created_at DESC, g.grp DESC';
        if ($sort === 'date_asc') {
            $sortOuter = 'g.last_created_at ASC, g.grp ASC';
        } elseif ($sort === 'total_desc') {
            $sortOuter = 'g.total_sum DESC, g.grp DESC';
        } elseif ($sort === 'total_asc') {
            $sortOuter = 'g.total_sum ASC, g.grp ASC';
        }

        $items = Database::fetchAll(
            "SELECT g.grp,
                    g.customer_name,
                    g.layaway_count,
                    g.layaway_count_debt,
                    g.total_sum AS total,
                    g.paid_sum AS paid_total,
                    g.last_created_at AS created_at,
                    g.folios_csv,
                    g.layaway_ids_csv,
                    g.folios_debt_csv,
                    g.layaway_ids_debt_csv,
                    g.status_distinct_count,
                    g.status_sample
             FROM (
                 SELECT IFNULL(l.customer_id, l.id) AS grp,
                        MAX(TRIM(COALESCE(c.name, ''))) AS customer_name,
                        COUNT(*) AS layaway_count,
                        SUM(CASE
                            WHEN (l.total - l.paid_total) > 0.009
                                 AND l.status NOT IN ('PAID', 'CANCELLED')
                            THEN 1 ELSE 0 END) AS layaway_count_debt,
                        SUM(l.total) AS total_sum,
                        SUM(l.paid_total) AS paid_sum,
                        MAX(l.created_at) AS last_created_at,
                        GROUP_CONCAT(l.folio ORDER BY l.created_at DESC, l.id DESC SEPARATOR ',') AS folios_csv,
                        GROUP_CONCAT(l.id ORDER BY l.created_at DESC, l.id DESC SEPARATOR ',') AS layaway_ids_csv,
                        GROUP_CONCAT(IF((l.total - l.paid_total) > 0.009 AND l.status NOT IN ('PAID', 'CANCELLED'), l.folio, NULL)
                                     ORDER BY l.created_at DESC, l.id DESC SEPARATOR ',') AS folios_debt_csv,
                        GROUP_CONCAT(IF((l.total - l.paid_total) > 0.009 AND l.status NOT IN ('PAID', 'CANCELLED'), l.id, NULL)
                                     ORDER BY l.created_at DESC, l.id DESC SEPARATOR ',') AS layaway_ids_debt_csv,
                        COUNT(DISTINCT l.status) AS status_distinct_count,
                        MAX(l.status) AS status_sample
                 FROM layaways l
                 LEFT JOIN customers c ON c.id = l.customer_id
                 LEFT JOIN users u ON u.id = l.created_by
                 WHERE {$whereSql}
                 GROUP BY IFNULL(l.customer_id, l.id)
                 {$havingBalance}
             ) g
             ORDER BY {$sortOuter}
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
            'SELECT COALESCE(MAX(folio), 0) + 1 AS n FROM layaways WHERE shop_id = :shop_id',
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
            'SELECT l.*,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    c.email AS customer_email,
                    TRIM(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) AS seller_name,
                    TRIM(CONCAT(COALESCE(cb.first_name, ""), " ", COALESCE(cb.last_name, ""))) AS created_by_name
             FROM layaways l
             LEFT JOIN customers c ON c.id = l.customer_id
             LEFT JOIN users u ON u.id = l.seller_id
             LEFT JOIN users cb ON cb.id = l.created_by
             WHERE l.id = :id AND l.shop_id = :shop_id
             LIMIT 1',
            ['id' => $id, 'shop_id' => $shopId]
        );
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getItems(int $layawayId): array
    {
        return Database::fetchAll(
            'SELECT li.*, p.sku, p.barcode
             FROM layaway_items li
             INNER JOIN products p ON p.id = li.product_id
             WHERE li.layaway_id = :id
             ORDER BY li.id ASC',
            ['id' => $layawayId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPayments(int $layawayId): array
    {
        return Database::fetchAll(
            'SELECT lp.*,
                    TRIM(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) AS created_by_name
             FROM layaway_payments lp
             LEFT JOIN users u ON u.id = lp.created_by
             WHERE lp.layaway_id = :id
             ORDER BY lp.id ASC',
            ['id' => $layawayId]
        );
    }

    /**
     * @param array{
     *   customer_id:?int,
     *   seller_id:?int,
     *   starts_at:string,
     *   due_date:?string,
     *   note_to_customer:?string,
     *   subtotal:float,
     *   discount_total:float,
     *   tax_total:float,
     *   total:float,
     *   down_payment:float,
     *   paid_total:float,
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
                'INSERT INTO layaways (
                    shop_id, folio, customer_id, seller_id, status,
                    starts_at, due_date, note_to_customer,
                    subtotal, discount_total, tax_total, total, down_payment, paid_total, created_by
                ) VALUES (
                    :shop_id, :folio, :customer_id, :seller_id, :status,
                    :starts_at, :due_date, :note_to_customer,
                    :subtotal, :discount_total, :tax_total, :total, :down_payment, :paid_total, :created_by
                )',
                [
                    'shop_id' => $shopId,
                    'folio' => $folio,
                    'customer_id' => $data['customer_id'],
                    'seller_id' => $data['seller_id'],
                    'status' => $status,
                    'starts_at' => $data['starts_at'],
                    'due_date' => $data['due_date'],
                    'note_to_customer' => $data['note_to_customer'],
                    'subtotal' => $data['subtotal'],
                    'discount_total' => $data['discount_total'],
                    'tax_total' => $data['tax_total'],
                    'total' => $data['total'],
                    'down_payment' => $data['down_payment'],
                    'paid_total' => $data['paid_total'],
                    'created_by' => $data['created_by'],
                ]
            );
            $layawayId = (int) $pdo->lastInsertId();

            foreach ($lines as $ln) {
                Database::execute(
                    'INSERT INTO layaway_items (
                        layaway_id, product_id, product_name, quantity, unit_price, tax_percent,
                        line_subtotal, tax_amount, line_total
                    ) VALUES (
                        :layaway_id, :product_id, :product_name, :quantity, :unit_price, :tax_percent,
                        :line_subtotal, :tax_amount, :line_total
                    )',
                    [
                        'layaway_id' => $layawayId,
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

            if ((float) $data['down_payment'] > 0) {
                Database::execute(
                    'INSERT INTO layaway_payments (layaway_id, payment_method, amount, reference, created_by)
                     VALUES (:layaway_id, :payment_method, :amount, :reference, :created_by)',
                    [
                        'layaway_id' => $layawayId,
                        'payment_method' => 'EFECTIVO',
                        'amount' => (float) $data['down_payment'],
                        'reference' => 'Anticipo inicial',
                        'created_by' => (int) $data['created_by'],
                    ]
                );
            }

            $pdo->commit();
            return $layawayId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function registerPayment(int $layawayId, int $shopId, int $createdBy, float $amount, string $paymentMethod, ?string $reference): bool
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $row = Database::fetch(
                'SELECT id, total, paid_total, status
                 FROM layaways
                 WHERE id = :id AND shop_id = :shop_id
                 LIMIT 1
                 FOR UPDATE',
                ['id' => $layawayId, 'shop_id' => $shopId]
            );
            if (!$row) {
                $pdo->rollBack();
                return false;
            }

            $status = (string) ($row['status'] ?? 'OPEN');
            if ($status !== 'OPEN') {
                $pdo->rollBack();
                return false;
            }

            $currentPaid = round((float) ($row['paid_total'] ?? 0), 2);
            $total = round((float) ($row['total'] ?? 0), 2);
            $newPaid = round($currentPaid + $amount, 2);
            if ($newPaid > $total) {
                $amount = round(max(0.0, $total - $currentPaid), 2);
                $newPaid = round($currentPaid + $amount, 2);
            }
            if ($amount <= 0) {
                $pdo->rollBack();
                return false;
            }

            Database::execute(
                'INSERT INTO layaway_payments (layaway_id, payment_method, amount, reference, created_by)
                 VALUES (:layaway_id, :payment_method, :amount, :reference, :created_by)',
                [
                    'layaway_id' => $layawayId,
                    'payment_method' => $paymentMethod,
                    'amount' => $amount,
                    'reference' => $reference,
                    'created_by' => $createdBy,
                ]
            );

            $newStatus = $newPaid >= $total - 0.01 ? 'PAID' : 'OPEN';
            Database::execute(
                'UPDATE layaways
                 SET paid_total = :paid_total, status = :status, updated_at = NOW()
                 WHERE id = :id AND shop_id = :shop_id',
                [
                    'paid_total' => $newPaid,
                    'status' => $newStatus,
                    'id' => $layawayId,
                    'shop_id' => $shopId,
                ]
            );

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public function markCancelled(int $id, int $shopId): bool
    {
        $affected = Database::execute(
            'UPDATE layaways SET status = "CANCELLED", updated_at = NOW()
             WHERE id = :id AND shop_id = :shop_id AND status = "OPEN"',
            ['id' => $id, 'shop_id' => $shopId]
        );
        return $affected > 0;
    }
}

