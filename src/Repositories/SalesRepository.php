<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class SalesRepository
{
    private const HISTORY_PER_PAGE = 20;

    public function nextFolio(int $shopId): int
    {
        $row = Database::fetch(
            'SELECT COALESCE(MAX(folio), 0) AS max_folio
             FROM sales
             WHERE shop_id = :shop_id',
            ['shop_id' => $shopId]
        );
        $max = (int) (($row['max_folio'] ?? 0));
        return $max + 1;
    }

    public function createSale(
        int $shopId,
        ?int $customerId,
        ?int $cashSessionId,
        int $folio,
        string $status,
        float $subtotal,
        float $discountTotal,
        float $taxTotal,
        float $total,
        float $paidTotal,
        ?string $notes,
        int $createdBy
    ): int {
        Database::execute(
            'INSERT INTO sales (
                shop_id, customer_id, cash_session_id, folio, status, occurred_at,
                subtotal, discount_total, tax_total, total, paid_total, notes, created_by, created_at
             )
             VALUES (
                :shop_id, :customer_id, :cash_session_id, :folio, :status, NOW(),
                :subtotal, :discount_total, :tax_total, :total, :paid_total, :notes, :created_by, NOW()
             )',
            [
                'shop_id' => $shopId,
                'customer_id' => $customerId,
                'cash_session_id' => $cashSessionId,
                'folio' => $folio,
                'status' => $status,
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discountTotal, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($total, 2),
                'paid_total' => round($paidTotal, 2),
                'notes' => $notes !== null && $notes !== '' ? trim($notes) : null,
                'created_by' => $createdBy,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @param array<int, array{product_id:int, quantity:float, unit_price:float, cost_snapshot:float, tax_percent:float, line_total:float}> $items
     */
    public function insertSaleItems(int $saleId, array $items): void
    {
        foreach ($items as $it) {
            Database::execute(
                'INSERT INTO sale_items (
                    sale_id, product_id, quantity, unit_price, cost_snapshot, tax_percent,
                    line_subtotal, discount_amount, tax_amount, line_total, created_at
                 )
                 VALUES (
                    :sale_id, :product_id, :quantity, :unit_price, :cost_snapshot, :tax_percent,
                    :line_subtotal, :discount_amount, :tax_amount, :line_total, NOW()
                 )',
                [
                    'sale_id' => $saleId,
                    'product_id' => (int) $it['product_id'],
                    'quantity' => round((float) $it['quantity'], 3),
                    'unit_price' => round((float) $it['unit_price'], 2),
                    'cost_snapshot' => round((float) $it['cost_snapshot'], 2),
                    'tax_percent' => round((float) $it['tax_percent'], 2),
                    'line_subtotal' => round((float) ($it['line_subtotal'] ?? 0), 2),
                    'discount_amount' => round((float) ($it['discount_amount'] ?? 0), 2),
                    'tax_amount' => round((float) ($it['tax_amount'] ?? 0), 2),
                    'line_total' => round((float) $it['line_total'], 2),
                ]
            );
        }
    }

    /**
     * @param array<int, array{payment_method:string, amount:float, reference?:string|null}> $payments
     */
    public function insertSalePayments(int $saleId, array $payments): void
    {
        foreach ($payments as $p) {
            $ref = isset($p['reference']) ? trim((string) $p['reference']) : '';
            $refParam = $ref !== '' ? $ref : null;
            Database::execute(
                'INSERT INTO sale_payments (sale_id, payment_method, amount, reference, created_at)
                 VALUES (:sale_id, :payment_method, :amount, :reference, NOW())',
                [
                    'sale_id' => $saleId,
                    'payment_method' => (string) $p['payment_method'],
                    'amount' => round((float) $p['amount'], 2),
                    'reference' => $refParam,
                ]
            );
        }
    }

    public function getSaleForTicket(int $saleId, int $shopId): ?array
    {
        $sale = Database::fetch(
            'SELECT s.id, s.shop_id, s.folio, s.customer_id, s.cash_session_id,
                    s.status, s.occurred_at, s.subtotal, s.discount_total, s.tax_total, s.total, s.paid_total, s.notes,
                    u.first_name, u.last_name,
                    c.name AS customer_name,
                    cs.opened_at AS cash_session_opened_at
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.created_by
             LEFT JOIN cash_sessions cs ON cs.id = s.cash_session_id
             WHERE s.id = :id AND s.shop_id = :shop_id
             LIMIT 1',
            ['id' => $saleId, 'shop_id' => $shopId]
        );
        if (!$sale) {
            return null;
        }
        $sellerName = trim((string) ($sale['first_name'] ?? '') . ' ' . (string) ($sale['last_name'] ?? ''));

        $items = Database::fetchAll(
            'SELECT si.product_id, p.name AS product_name,
                    si.quantity, si.unit_price, si.tax_percent, si.line_subtotal, si.discount_amount, si.tax_amount, si.line_total
             FROM sale_items si
             INNER JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = :sale_id
             ORDER BY si.id ASC',
            ['sale_id' => $saleId]
        );

        $payments = Database::fetchAll(
            'SELECT sp.payment_method, sp.amount, sp.reference
             FROM sale_payments sp
             WHERE sp.sale_id = :sale_id
             ORDER BY sp.id ASC',
            ['sale_id' => $saleId]
        );

        return [
            'sale' => $sale,
            'seller_name' => $sellerName !== '' ? $sellerName : 'Usuario',
            'customer_name' => (string) ($sale['customer_name'] ?? 'Cliente'),
            'occurred_at' => (string) ($sale['occurred_at'] ?? ''),
            'items' => $items,
            'payments' => $payments,
            'notes' => $sale['notes'] ?? null,
        ];
    }

    /**
     * @param array{folio?:string,desde?:string,hasta?:string,customer_id?:int|string,user_id?:int|string,status?:string} $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,total_pages:int,per_page:int}
     */
    public function getSalesHistory(int $shopId, int $page, array $filters): array
    {
        $page = max(1, $page);
        $perPage = self::HISTORY_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $where = ['s.shop_id = :shop_id'];
        $params = ['shop_id' => $shopId];

        $folio = trim((string) ($filters['folio'] ?? ''));
        if ($folio !== '') {
            $where[] = 'CAST(s.folio AS CHAR) LIKE :folio';
            $params['folio'] = '%' . $folio . '%';
        }

        $desde = trim((string) ($filters['desde'] ?? ''));
        if ($this->isValidDate($desde)) {
            $where[] = 's.occurred_at >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }

        $hasta = trim((string) ($filters['hasta'] ?? ''));
        if ($this->isValidDate($hasta)) {
            $where[] = 's.occurred_at <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        $customerId = (int) ($filters['customer_id'] ?? 0);
        if ($customerId > 0) {
            $where[] = 's.customer_id = :customer_id';
            $params['customer_id'] = $customerId;
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 's.created_by = :user_id';
            $params['user_id'] = $userId;
        }

        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        if ($status !== '' && in_array($status, ['OPEN', 'PAID', 'CANCELLED', 'REFUNDED'], true)) {
            $where[] = 's.status = :status';
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $where);
        $countRow = Database::fetch(
            'SELECT COUNT(*) AS total
             FROM sales s
             WHERE ' . $whereSql,
            $params
        );
        $total = (int) ($countRow['total'] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = 'SELECT
                    s.id, s.folio, s.status, s.occurred_at, s.subtotal, s.discount_total, s.tax_total, s.total, s.paid_total,
                    c.name AS customer_name,
                    CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) AS seller_name,
                    cs.opened_at AS cash_opened_at,
                    (
                        SELECT GROUP_CONCAT(DISTINCT sp.payment_method ORDER BY sp.payment_method SEPARATOR ", ")
                        FROM sale_payments sp
                        WHERE sp.sale_id = s.id
                    ) AS payment_methods
                FROM sales s
                LEFT JOIN customers c ON c.id = s.customer_id
                LEFT JOIN users u ON u.id = s.created_by
                LEFT JOIN cash_sessions cs ON cs.id = s.cash_session_id
                WHERE ' . $whereSql . '
                ORDER BY s.occurred_at DESC, s.id DESC
                LIMIT :limit OFFSET :offset';
        $stmt = Database::pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => is_array($rows) ? $rows : [],
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
        ];
    }

    public function getSaleDetail(int $saleId, int $shopId): ?array
    {
        $sale = Database::fetch(
            'SELECT s.*,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    c.email AS customer_email,
                    CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) AS seller_name,
                    cs.opened_at AS cash_opened_at,
                    cs.closed_at AS cash_closed_at
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             LEFT JOIN users u ON u.id = s.created_by
             LEFT JOIN cash_sessions cs ON cs.id = s.cash_session_id
             WHERE s.id = :id AND s.shop_id = :shop_id
             LIMIT 1',
            ['id' => $saleId, 'shop_id' => $shopId]
        );
        if (!$sale) {
            return null;
        }

        $items = Database::fetchAll(
            'SELECT
                si.id, si.product_id, p.name AS product_name, p.sku, p.barcode,
                si.quantity, si.unit_price, si.tax_percent, si.line_subtotal, si.discount_amount, si.tax_amount, si.line_total,
                p.is_inventory_item,
                (
                    SELECT COALESCE(SUM(sri.quantity), 0)
                    FROM sale_return_items sri
                    INNER JOIN sale_returns sr ON sr.id = sri.sale_return_id
                    WHERE sr.sale_id = si.sale_id AND sri.sale_item_id = si.id
                ) AS returned_quantity
             FROM sale_items si
             INNER JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = :sale_id
             ORDER BY si.id ASC',
            ['sale_id' => $saleId]
        );

        $payments = Database::fetchAll(
            'SELECT id, payment_method, amount, created_at
             FROM sale_payments
             WHERE sale_id = :sale_id
             ORDER BY id ASC',
            ['sale_id' => $saleId]
        );

        return [
            'sale' => $sale,
            'items' => $items,
            'payments' => $payments,
        ];
    }

    public function lockSaleForAdjustment(int $saleId, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT id, shop_id, folio, status, total, paid_total, cash_session_id
             FROM sales
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1
             FOR UPDATE',
            ['id' => $saleId, 'shop_id' => $shopId]
        );
    }

    /**
     * Ventas abiertas con saldo del cliente (FIFO). Usar dentro de una transacción.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lockOpenDebtSalesForCustomer(int $shopId, int $customerId): array
    {
        $rows = Database::fetchAll(
            'SELECT id, folio, total, paid_total, (total - paid_total) AS saldo
             FROM sales
             WHERE shop_id = :shop_id AND customer_id = :customer_id
               AND status = "OPEN" AND total > paid_total + 0.00001
             ORDER BY occurred_at ASC, id ASC
             FOR UPDATE',
            ['shop_id' => $shopId, 'customer_id' => $customerId]
        );
        return is_array($rows) ? $rows : [];
    }

    /**
     * Abono o liquidación parcial sobre una venta abierta (debe ejecutarse en la misma transacción que el bloqueo previo).
     *
     * @return array{applied:float, new_status:string}|null Si no hay saldo aplicable
     */
    public function applyDebtPaymentToSale(
        int $saleId,
        int $shopId,
        float $amount,
        string $paymentMethod,
        ?string $reference
    ): ?array {
        $sale = Database::fetch(
            'SELECT id, total, paid_total, status
             FROM sales
             WHERE id = :id AND shop_id = :shop_id AND status = "OPEN"
             FOR UPDATE',
            ['id' => $saleId, 'shop_id' => $shopId]
        );
        if (!$sale) {
            return null;
        }
        $total = round((float) ($sale['total'] ?? 0), 2);
        $paid = round((float) ($sale['paid_total'] ?? 0), 2);
        $saldo = round($total - $paid, 2);
        if ($saldo <= 0.001) {
            return null;
        }
        $apply = round(min(max(0.0, $amount), $saldo), 2);
        if ($apply <= 0.001) {
            return null;
        }
        $newPaid = round($paid + $apply, 2);
        $newStatus = $newPaid >= $total - 0.01 ? 'PAID' : 'OPEN';
        Database::execute(
            'UPDATE sales SET paid_total = :paid_total, status = :status WHERE id = :id AND shop_id = :shop_id',
            [
                'paid_total' => $newPaid,
                'status' => $newStatus,
                'id' => $saleId,
                'shop_id' => $shopId,
            ]
        );
        $ref = $reference !== null && trim($reference) !== '' ? trim($reference) : null;
        Database::execute(
            'INSERT INTO sale_payments (sale_id, payment_method, amount, reference, created_at)
             VALUES (:sale_id, :payment_method, :amount, :reference, NOW())',
            [
                'sale_id' => $saleId,
                'payment_method' => $paymentMethod,
                'amount' => $apply,
                'reference' => $ref,
            ]
        );
        return ['applied' => $apply, 'new_status' => $newStatus];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getSaleItemsForAdjustment(int $saleId): array
    {
        return Database::fetchAll(
            'SELECT
                si.id, si.sale_id, si.product_id, si.quantity, si.unit_price, si.tax_percent,
                si.line_subtotal, si.discount_amount, si.tax_amount, si.line_total,
                p.name AS product_name, p.is_inventory_item
             FROM sale_items si
             INNER JOIN products p ON p.id = si.product_id
             WHERE si.sale_id = :sale_id
             ORDER BY si.id ASC',
            ['sale_id' => $saleId]
        );
    }

    /**
     * @param int[] $saleItemIds
     * @return array<int,float> Mapa sale_item_id => qty devuelta acumulada
     */
    public function getReturnedQtyBySaleItemIds(array $saleItemIds): array
    {
        $saleItemIds = array_values(array_unique(array_map(fn ($id) => (int) $id, $saleItemIds)));
        if ($saleItemIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($saleItemIds), '?'));
        $sql = 'SELECT sri.sale_item_id, COALESCE(SUM(sri.quantity), 0) AS qty
                FROM sale_return_items sri
                INNER JOIN sale_returns sr ON sr.id = sri.sale_return_id
                WHERE sri.sale_item_id IN (' . $placeholders . ')
                GROUP BY sri.sale_item_id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($saleItemIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) ($row['sale_item_id'] ?? 0)] = (float) ($row['qty'] ?? 0);
        }
        return $map;
    }

    public function hasCancellation(int $saleId): bool
    {
        return Database::fetch(
            'SELECT 1 FROM sale_cancellations WHERE sale_id = :sale_id LIMIT 1',
            ['sale_id' => $saleId]
        ) !== null;
    }

    public function createCancellation(
        int $shopId,
        int $saleId,
        string $reason,
        ?string $notes,
        float $refundAmount,
        ?int $cashMovementId,
        int $createdBy
    ): int {
        Database::execute(
            'INSERT INTO sale_cancellations (
                shop_id, sale_id, reason, notes, refund_amount, cash_movement_id, created_by, created_at
             ) VALUES (
                :shop_id, :sale_id, :reason, :notes, :refund_amount, :cash_movement_id, :created_by, NOW()
             )',
            [
                'shop_id' => $shopId,
                'sale_id' => $saleId,
                'reason' => trim($reason),
                'notes' => $notes !== null && $notes !== '' ? trim($notes) : null,
                'refund_amount' => round($refundAmount, 2),
                'cash_movement_id' => $cashMovementId,
                'created_by' => $createdBy,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public function updateSaleStatus(int $saleId, string $status): void
    {
        Database::execute(
            'UPDATE sales SET status = :status WHERE id = :id',
            ['status' => $status, 'id' => $saleId]
        );
    }

    public function createReturn(
        int $shopId,
        int $saleId,
        string $reason,
        ?string $notes,
        float $refundAmount,
        ?int $cashMovementId,
        int $createdBy
    ): int {
        Database::execute(
            'INSERT INTO sale_returns (
                shop_id, sale_id, reason, notes, refund_amount, cash_movement_id, created_by, created_at
             ) VALUES (
                :shop_id, :sale_id, :reason, :notes, :refund_amount, :cash_movement_id, :created_by, NOW()
             )',
            [
                'shop_id' => $shopId,
                'sale_id' => $saleId,
                'reason' => trim($reason),
                'notes' => $notes !== null && $notes !== '' ? trim($notes) : null,
                'refund_amount' => round($refundAmount, 2),
                'cash_movement_id' => $cashMovementId,
                'created_by' => $createdBy,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @param array<int,array{sale_item_id:int,product_id:int,quantity:float,unit_price:float,tax_percent:float,line_subtotal:float,tax_amount:float,line_total:float}> $items
     */
    public function insertReturnItems(int $saleReturnId, array $items): void
    {
        foreach ($items as $it) {
            Database::execute(
                'INSERT INTO sale_return_items (
                    sale_return_id, sale_item_id, product_id, quantity,
                    unit_price, tax_percent, line_subtotal, tax_amount, line_total, created_at
                 ) VALUES (
                    :sale_return_id, :sale_item_id, :product_id, :quantity,
                    :unit_price, :tax_percent, :line_subtotal, :tax_amount, :line_total, NOW()
                 )',
                [
                    'sale_return_id' => $saleReturnId,
                    'sale_item_id' => (int) $it['sale_item_id'],
                    'product_id' => (int) $it['product_id'],
                    'quantity' => round((float) $it['quantity'], 3),
                    'unit_price' => round((float) $it['unit_price'], 2),
                    'tax_percent' => round((float) $it['tax_percent'], 2),
                    'line_subtotal' => round((float) $it['line_subtotal'], 2),
                    'tax_amount' => round((float) $it['tax_amount'], 2),
                    'line_total' => round((float) $it['line_total'], 2),
                ]
            );
        }
    }

    public function getCancellationBySale(int $saleId): ?array
    {
        return Database::fetch(
            'SELECT sc.*, TRIM(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) AS created_by_name
             FROM sale_cancellations sc
             LEFT JOIN users u ON u.id = sc.created_by
             WHERE sc.sale_id = :sale_id
             LIMIT 1',
            ['sale_id' => $saleId]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getReturnsBySale(int $saleId): array
    {
        return Database::fetchAll(
            'SELECT sr.id, sr.reason, sr.notes, sr.refund_amount, sr.created_at,
                    TRIM(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) AS created_by_name
             FROM sale_returns sr
             LEFT JOIN users u ON u.id = sr.created_by
             WHERE sr.sale_id = :sale_id
             ORDER BY sr.id DESC',
            ['sale_id' => $saleId]
        );
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }
        $dt = \DateTime::createFromFormat('Y-m-d', $date);
        return $dt instanceof \DateTime && $dt->format('Y-m-d') === $date;
    }
}

