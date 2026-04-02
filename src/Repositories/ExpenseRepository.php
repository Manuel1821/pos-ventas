<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class ExpenseRepository
{
    private const PER_PAGE = 20;

    /**
     * @param array{
     *   q?:string,
     *   desde?:string,
     *   hasta?:string,
     *   expense_category_id?:int,
     *   supplier?:string,
     *   payment_method?:string,
     *   user_id?:int,
     *   estado?:string
     * } $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,total_pages:int,per_page:int}
     */
    public function listForShop(int $shopId, int $page, array $filters): array
    {
        $page = max(1, $page);
        $perPage = self::PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $where = ['e.shop_id = :shop_id'];
        $params = ['shop_id' => $shopId];

        $estado = strtoupper(trim((string) ($filters['estado'] ?? '')));
        if ($estado === 'ALL' || $estado === 'TODOS') {
            // sin filtro de estado
        } elseif ($estado === 'CANCELLED' || $estado === 'ANULADO' || $estado === 'ANULADOS') {
            $where[] = 'e.status = "CANCELLED"';
        } else {
            $where[] = 'e.status = "ACTIVE"';
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(e.concept LIKE :q OR e.supplier_name LIKE :q OR e.notes LIKE :q OR e.reference LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

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

        $pm = trim((string) ($filters['payment_method'] ?? ''));
        if ($pm !== '') {
            $where[] = 'e.payment_method = :payment_method';
            $params['payment_method'] = $pm;
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 'e.created_by = :user_id';
            $params['user_id'] = $userId;
        }

        $whereSql = implode(' AND ', $where);
        $countRow = Database::fetch(
            'SELECT COUNT(*) AS total
             FROM expenses e
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
                    e.id, e.shop_id, e.expense_category_id, e.concept, e.amount_subtotal, e.iva_amount, e.total,
                    e.payment_method, e.supplier_name, e.reference, e.occurred_at, e.notes, e.status,
                    e.created_by, e.created_at,
                    ec.name AS category_name,
                    TRIM(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) AS creator_name
                FROM expenses e
                INNER JOIN expense_categories ec ON ec.id = e.expense_category_id
                LEFT JOIN users u ON u.id = e.created_by
                WHERE ' . $whereSql . '
                ORDER BY e.occurred_at DESC, e.id DESC
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

    public function findById(int $id, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT
                e.id, e.shop_id, e.expense_category_id, e.concept, e.amount_subtotal, e.iva_amount, e.total,
                e.payment_method, e.supplier_name, e.reference, e.occurred_at, e.notes, e.status,
                e.created_by, e.created_at,
                ec.name AS category_name,
                TRIM(CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, ""))) AS creator_name
             FROM expenses e
             INNER JOIN expense_categories ec ON ec.id = e.expense_category_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.id = :id AND e.shop_id = :shop_id
             LIMIT 1',
            ['id' => $id, 'shop_id' => $shopId]
        );
    }

    /**
     * Categoría activa y de la misma tienda.
     */
    public function findActiveCategory(int $categoryId, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT id, name, status
             FROM expense_categories
             WHERE id = :id AND shop_id = :shop_id AND status = "ACTIVE"',
            ['id' => $categoryId, 'shop_id' => $shopId]
        );
    }

    public function create(
        int $shopId,
        int $expenseCategoryId,
        string $concept,
        float $amountSubtotal,
        float $ivaAmount,
        float $total,
        string $paymentMethod,
        ?string $supplierName,
        ?string $reference,
        string $occurredAt,
        ?string $notes,
        int $createdBy
    ): int {
        Database::execute(
            'INSERT INTO expenses (
                shop_id, expense_category_id, concept, amount_subtotal, iva_amount, total,
                payment_method, supplier_name, reference, occurred_at, notes, status, created_by, created_at
            ) VALUES (
                :shop_id, :expense_category_id, :concept, :amount_subtotal, :iva_amount, :total,
                :payment_method, :supplier_name, :reference, :occurred_at, :notes, "ACTIVE", :created_by, NOW()
            )',
            [
                'shop_id' => $shopId,
                'expense_category_id' => $expenseCategoryId,
                'concept' => $concept,
                'amount_subtotal' => round($amountSubtotal, 2),
                'iva_amount' => round($ivaAmount, 2),
                'total' => round($total, 2),
                'payment_method' => $paymentMethod,
                'supplier_name' => $supplierName !== null && $supplierName !== '' ? $supplierName : null,
                'reference' => $reference !== null && $reference !== '' ? $reference : null,
                'occurred_at' => $occurredAt,
                'notes' => $notes !== null && $notes !== '' ? $notes : null,
                'created_by' => $createdBy,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public function update(
        int $id,
        int $shopId,
        int $expenseCategoryId,
        string $concept,
        float $amountSubtotal,
        float $ivaAmount,
        float $total,
        string $paymentMethod,
        ?string $supplierName,
        ?string $reference,
        string $occurredAt,
        ?string $notes
    ): bool {
        $n = Database::execute(
            'UPDATE expenses SET
                expense_category_id = :expense_category_id,
                concept = :concept,
                amount_subtotal = :amount_subtotal,
                iva_amount = :iva_amount,
                total = :total,
                payment_method = :payment_method,
                supplier_name = :supplier_name,
                reference = :reference,
                occurred_at = :occurred_at,
                notes = :notes
             WHERE id = :id AND shop_id = :shop_id AND status = "ACTIVE"',
            [
                'id' => $id,
                'shop_id' => $shopId,
                'expense_category_id' => $expenseCategoryId,
                'concept' => $concept,
                'amount_subtotal' => round($amountSubtotal, 2),
                'iva_amount' => round($ivaAmount, 2),
                'total' => round($total, 2),
                'payment_method' => $paymentMethod,
                'supplier_name' => $supplierName !== null && $supplierName !== '' ? $supplierName : null,
                'reference' => $reference !== null && $reference !== '' ? $reference : null,
                'occurred_at' => $occurredAt,
                'notes' => $notes !== null && $notes !== '' ? $notes : null,
            ]
        );
        return $n > 0;
    }

    public function cancel(int $id, int $shopId): bool
    {
        $n = Database::execute(
            'UPDATE expenses SET status = "CANCELLED" WHERE id = :id AND shop_id = :shop_id AND status = "ACTIVE"',
            ['id' => $id, 'shop_id' => $shopId]
        );
        return $n > 0;
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
