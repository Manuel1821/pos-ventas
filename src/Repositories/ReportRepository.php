<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class ReportRepository
{
    /**
     * @param array{desde:string,hasta:string,user_id:int,cash_session_id:int} $filters
     * @return array{summary:array<string,float|int>,items:array<int,array<string,mixed>>}
     */
    public function salesByPeriod(int $shopId, array $filters): array
    {
        [$whereSql, $params] = $this->buildSalesWhere($shopId, $filters);

        $summary = Database::fetch(
            'SELECT
                COUNT(*) AS transactions_count,
                COALESCE(SUM(s.total), 0) AS total_sales,
                COALESCE(AVG(s.total), 0) AS avg_ticket
             FROM sales s
             WHERE ' . $whereSql,
            $params
        ) ?: [];

        $items = Database::fetchAll(
            'SELECT
                DATE(s.occurred_at) AS sale_date,
                COUNT(*) AS transactions_count,
                COALESCE(SUM(s.total), 0) AS total_sales,
                COALESCE(AVG(s.total), 0) AS avg_ticket
             FROM sales s
             WHERE ' . $whereSql . '
             GROUP BY DATE(s.occurred_at)
             ORDER BY DATE(s.occurred_at) DESC',
            $params
        );

        return [
            'summary' => [
                'transactions_count' => (int) ($summary['transactions_count'] ?? 0),
                'total_sales' => (float) ($summary['total_sales'] ?? 0),
                'avg_ticket' => (float) ($summary['avg_ticket'] ?? 0),
            ],
            'items' => is_array($items) ? $items : [],
        ];
    }

    /**
     * @param array{desde:string,hasta:string,user_id:int,cash_session_id:int,payment_method:string} $filters
     * @return array{summary:array<string,float|int>,items:array<int,array<string,mixed>>}
     */
    public function salesByPaymentMethod(int $shopId, array $filters): array
    {
        [$whereSql, $params] = $this->buildSalesWhere($shopId, $filters);
        $method = strtoupper(trim((string) ($filters['payment_method'] ?? '')));
        if ($method !== '') {
            $whereSql .= ' AND sp.payment_method = :payment_method';
            $params['payment_method'] = $method;
        }

        $items = Database::fetchAll(
            'SELECT
                sp.payment_method,
                COUNT(DISTINCT s.id) AS transactions_count,
                COALESCE(SUM(sp.amount), 0) AS total_amount
             FROM sale_payments sp
             INNER JOIN sales s ON s.id = sp.sale_id
             WHERE ' . $whereSql . '
             GROUP BY sp.payment_method
             ORDER BY total_amount DESC',
            $params
        );

        $totalAmount = 0.0;
        $txCount = 0;
        foreach ($items as $row) {
            $totalAmount += (float) ($row['total_amount'] ?? 0);
            $txCount += (int) ($row['transactions_count'] ?? 0);
        }

        return [
            'summary' => [
                'payment_methods_count' => count($items),
                'transactions_count' => $txCount,
                'total_amount' => $totalAmount,
            ],
            'items' => is_array($items) ? $items : [],
        ];
    }

    /**
     * @param array{desde:string,hasta:string,user_id:int,cash_session_id:int} $filters
     * @return array{summary:array<string,float|int>,items:array<int,array<string,mixed>>}
     */
    public function cashSessionsReport(int $shopId, array $filters): array
    {
        $where = ['cs.shop_id = :shop_id'];
        $params = ['shop_id' => $shopId];

        $desde = trim((string) ($filters['desde'] ?? ''));
        if ($this->isValidDate($desde)) {
            $where[] = 'cs.opened_at >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }
        $hasta = trim((string) ($filters['hasta'] ?? ''));
        if ($this->isValidDate($hasta)) {
            $where[] = 'cs.opened_at <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }
        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = '(cs.opened_by = :user_id OR cs.closed_by = :user_id)';
            $params['user_id'] = $userId;
        }
        $sessionId = (int) ($filters['cash_session_id'] ?? 0);
        if ($sessionId > 0) {
            $where[] = 'cs.id = :cash_session_id';
            $params['cash_session_id'] = $sessionId;
        }

        $whereSql = implode(' AND ', $where);

        $items = Database::fetchAll(
            'SELECT
                cs.id,
                cs.status,
                cs.opened_at,
                cs.closed_at,
                cs.initial_amount,
                cs.expected_amount,
                cs.counted_amount,
                cs.difference,
                TRIM(CONCAT(COALESCE(uo.first_name, ""), " ", COALESCE(uo.last_name, ""))) AS opened_by_name,
                TRIM(CONCAT(COALESCE(uc.first_name, ""), " ", COALESCE(uc.last_name, ""))) AS closed_by_name
             FROM cash_sessions cs
             INNER JOIN users uo ON uo.id = cs.opened_by
             LEFT JOIN users uc ON uc.id = cs.closed_by
             WHERE ' . $whereSql . '
             ORDER BY cs.opened_at DESC, cs.id DESC',
            $params
        );

        $summary = [
            'sessions_count' => count($items),
            'initial_total' => 0.0,
            'expected_total' => 0.0,
            'counted_total' => 0.0,
            'difference_total' => 0.0,
        ];
        foreach ($items as $row) {
            $summary['initial_total'] += (float) ($row['initial_amount'] ?? 0);
            $summary['expected_total'] += (float) ($row['expected_amount'] ?? 0);
            $summary['counted_total'] += (float) ($row['counted_amount'] ?? 0);
            $summary['difference_total'] += (float) ($row['difference'] ?? 0);
        }

        return [
            'summary' => $summary,
            'items' => is_array($items) ? $items : [],
        ];
    }

    /**
     * @param array{desde:string,hasta:string,expense_category_id:int,supplier:string,payment_method:string,user_id:int} $filters
     * @return array{summary:array<string,float|int>,items:array<int,array<string,mixed>>}
     */
    public function expensesReport(int $shopId, array $filters): array
    {
        $where = ['e.shop_id = :shop_id', 'e.status = "ACTIVE"'];
        $params = ['shop_id' => $shopId];

        $desde = trim((string) ($filters['desde'] ?? ''));
        if ($this->isValidDate($desde)) {
            $where[] = 'e.occurred_at >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }
        $hasta = trim((string) ($filters['hasta'] ?? ''));
        if ($this->isValidDate($hasta)) {
            $where[] = 'e.occurred_at <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }
        $catId = (int) ($filters['expense_category_id'] ?? 0);
        if ($catId > 0) {
            $where[] = 'e.expense_category_id = :expense_category_id';
            $params['expense_category_id'] = $catId;
        }
        $supplier = trim((string) ($filters['supplier'] ?? ''));
        if ($supplier !== '') {
            $where[] = 'e.supplier_name LIKE :supplier';
            $params['supplier'] = '%' . $supplier . '%';
        }
        $paymentMethod = strtoupper(trim((string) ($filters['payment_method'] ?? '')));
        if ($paymentMethod !== '') {
            $where[] = 'e.payment_method = :payment_method';
            $params['payment_method'] = $paymentMethod;
        }
        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 'e.created_by = :user_id';
            $params['user_id'] = $userId;
        }

        $whereSql = implode(' AND ', $where);

        $summary = Database::fetch(
            'SELECT
                COUNT(*) AS expenses_count,
                COALESCE(SUM(e.total), 0) AS expenses_total
             FROM expenses e
             WHERE ' . $whereSql,
            $params
        ) ?: [];

        $items = Database::fetchAll(
            'SELECT
                DATE(e.occurred_at) AS expense_date,
                ec.name AS category_name,
                COUNT(*) AS expenses_count,
                COALESCE(SUM(e.total), 0) AS total_amount
             FROM expenses e
             INNER JOIN expense_categories ec ON ec.id = e.expense_category_id
             WHERE ' . $whereSql . '
             GROUP BY DATE(e.occurred_at), ec.name
             ORDER BY DATE(e.occurred_at) DESC, total_amount DESC',
            $params
        );

        return [
            'summary' => [
                'expenses_count' => (int) ($summary['expenses_count'] ?? 0),
                'expenses_total' => (float) ($summary['expenses_total'] ?? 0),
            ],
            'items' => is_array($items) ? $items : [],
        ];
    }

    /**
     * @param array{desde:string,hasta:string,user_id:int,cash_session_id:int,expense_category_id:int,supplier:string,payment_method:string} $filters
     * @return array{summary:array<string,float|int>}
     */
    public function basicProfitReport(int $shopId, array $filters): array
    {
        [$salesWhere, $salesParams] = $this->buildSalesWhere($shopId, $filters);
        $salesSummary = Database::fetch(
            'SELECT COALESCE(SUM(s.total), 0) AS sales_total
             FROM sales s
             WHERE ' . $salesWhere,
            $salesParams
        ) ?: [];

        $expenseFilters = [
            'desde' => (string) ($filters['desde'] ?? ''),
            'hasta' => (string) ($filters['hasta'] ?? ''),
            'expense_category_id' => (int) ($filters['expense_category_id'] ?? 0),
            'supplier' => (string) ($filters['supplier'] ?? ''),
            'payment_method' => (string) ($filters['payment_method'] ?? ''),
            'user_id' => (int) ($filters['user_id'] ?? 0),
        ];
        $expenses = $this->expensesReport($shopId, $expenseFilters);

        $salesTotal = (float) ($salesSummary['sales_total'] ?? 0);
        $expensesTotal = (float) ($expenses['summary']['expenses_total'] ?? 0);

        return [
            'summary' => [
                'sales_total' => $salesTotal,
                'expenses_total' => $expensesTotal,
                'operating_profit' => $salesTotal - $expensesTotal,
            ],
        ];
    }

    /**
     * @param array{category_id:int,status:string,availability:string,q:string} $filters
     * @return array{summary:array<string,float|int>,items:array<int,array<string,mixed>>}
     */
    public function inventoryCurrentReport(int $shopId, array $filters): array
    {
        $where = ['p.shop_id = :shop_id'];
        $params = ['shop_id' => $shopId];

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        if (in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = 'p.status = :status';
            $params['status'] = $status;
        }

        $availability = strtoupper(trim((string) ($filters['availability'] ?? '')));
        if ($availability === 'IN_STOCK') {
            $where[] = 'p.stock > 0';
        } elseif ($availability === 'OUT_OF_STOCK') {
            $where[] = 'p.stock <= 0';
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(p.name LIKE :q OR p.sku LIKE :q OR p.barcode LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereSql = implode(' AND ', $where);

        $items = Database::fetchAll(
            'SELECT
                p.id, p.name, p.sku, p.barcode, p.unit, p.price, p.cost, p.stock, p.status,
                p.is_inventory_item,
                c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE ' . $whereSql . '
             ORDER BY p.name ASC',
            $params
        );

        $summary = [
            'products_count' => count($items),
            'stock_units' => 0.0,
            'inventory_cost_total' => 0.0,
            'inventory_sale_total' => 0.0,
            'out_of_stock_count' => 0,
        ];
        foreach ($items as $row) {
            $stock = (float) ($row['stock'] ?? 0);
            $cost = (float) ($row['cost'] ?? 0);
            $price = (float) ($row['price'] ?? 0);
            $summary['stock_units'] += $stock;
            $summary['inventory_cost_total'] += ($stock * $cost);
            $summary['inventory_sale_total'] += ($stock * $price);
            if ($stock <= 0.0) {
                $summary['out_of_stock_count']++;
            }
        }

        return [
            'summary' => $summary,
            'items' => is_array($items) ? $items : [],
        ];
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    public function listUsers(int $shopId): array
    {
        return Database::fetchAll(
            'SELECT id, TRIM(CONCAT(COALESCE(first_name, ""), " ", COALESCE(last_name, ""))) AS name
             FROM users
             WHERE shop_id = :shop_id
             ORDER BY first_name ASC, last_name ASC',
            ['shop_id' => $shopId]
        );
    }

    /**
     * @return array<int,array{id:int,opened_at:string,status:string}>
     */
    public function listCashSessions(int $shopId): array
    {
        return Database::fetchAll(
            'SELECT id, opened_at, status
             FROM cash_sessions
             WHERE shop_id = :shop_id
             ORDER BY opened_at DESC, id DESC
             LIMIT 200',
            ['shop_id' => $shopId]
        );
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    public function listExpenseCategories(int $shopId): array
    {
        return Database::fetchAll(
            'SELECT id, name
             FROM expense_categories
             WHERE shop_id = :shop_id
             ORDER BY name ASC',
            ['shop_id' => $shopId]
        );
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    public function listProductCategories(int $shopId): array
    {
        return Database::fetchAll(
            'SELECT id, name
             FROM categories
             WHERE shop_id = :shop_id
             ORDER BY name ASC',
            ['shop_id' => $shopId]
        );
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:string,1:array<string,mixed>}
     */
    private function buildSalesWhere(int $shopId, array $filters): array
    {
        $where = ['s.shop_id = :shop_id', 's.status = "PAID"'];
        $params = ['shop_id' => $shopId];

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
        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 's.created_by = :user_id';
            $params['user_id'] = $userId;
        }
        $sessionId = (int) ($filters['cash_session_id'] ?? 0);
        if ($sessionId > 0) {
            $where[] = 's.cash_session_id = :cash_session_id';
            $params['cash_session_id'] = $sessionId;
        }

        return [implode(' AND ', $where), $params];
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

