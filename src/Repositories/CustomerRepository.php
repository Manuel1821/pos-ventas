<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;
use PDOException;

class CustomerRepository
{
    private const PER_PAGE = 15;

    /**
     * Lista clientes con paginación, búsqueda por nombre/teléfono/correo y filtro por estado.
     * Incluye al cliente genérico (is_public = 1).
     *
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public function listByShop(int $shopId, int $page = 1, string $search = '', ?string $status = null): array
    {
        $offset = ($page - 1) * self::PER_PAGE;
        $params = ['shop_id' => $shopId];
        $where = ['c.shop_id = :shop_id'];
        if ($search !== '') {
            $where[] = '(c.name LIKE :search OR c.phone LIKE :search2 OR c.email LIKE :search3)';
            $term = '%' . $search . '%';
            $params['search'] = $term;
            $params['search2'] = $term;
            $params['search3'] = $term;
        }
        if ($status !== null && in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = 'c.status = :status';
            $params['status'] = $status;
        }
        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS total FROM customers c WHERE {$whereSql}";
        $total = (int) (Database::fetch($countSql, $params)['total'] ?? 0);

        $sql = "SELECT
                    c.id, c.shop_id, c.name, c.phone, c.email, c.address, c.rfc, c.notes, c.is_public, c.status, c.created_at,
                    COALESCE(d.debt_total, 0) AS debt_total
                FROM customers c
                LEFT JOIN (
                    SELECT
                        s.customer_id,
                        SUM(s.total - s.paid_total) AS debt_total
                    FROM sales s
                    WHERE s.shop_id = :shop_id_debt
                      AND s.status = 'OPEN'
                      AND s.customer_id IS NOT NULL
                      AND s.total > s.paid_total
                    GROUP BY s.customer_id
                ) d ON d.customer_id = c.id
                WHERE {$whereSql}
                ORDER BY c.is_public DESC, c.name ASC
                LIMIT " . self::PER_PAGE . " OFFSET " . (int) $offset;
        // Algunos drivers fallan si el mismo placeholder aparece múltiples veces en una misma consulta.
        $params['shop_id_debt'] = $shopId;
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

    /**
     * Obtiene el desglose de ventas abiertas (pendientes) para un cliente.
     *
     * @return array{items: array<int, array<string, mixed>>, total_debt: float}
     */
    public function getOpenDebtDetailsByCustomer(int $shopId, int $customerId): array
    {
        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            return ['items' => [], 'total_debt' => 0.0];
        }

        $sql = 'SELECT
                    s.id,
                    s.folio,
                    s.occurred_at,
                    s.total,
                    s.paid_total,
                    (s.total - s.paid_total) AS saldo,
                    s.notes
                FROM sales s
                WHERE s.shop_id = :shop_id
                  AND s.customer_id = :customer_id
                  AND s.status = "OPEN"
                  AND s.total > s.paid_total
                ORDER BY s.occurred_at DESC, s.id DESC';

        $rows = Database::fetchAll($sql, [
            'shop_id' => $shopId,
            'customer_id' => $customerId,
        ]);

        $total = 0.0;
        foreach ($rows as $r) {
            $total += (float) ($r['saldo'] ?? 0);
        }

        return [
            'items' => is_array($rows) ? $rows : [],
            'total_debt' => round($total, 2),
        ];
    }

    public function findById(int $id, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT id, shop_id, name, phone, email, address, rfc, notes, is_public, status, created_at
             FROM customers
             WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId]
        );
    }

    /**
     * Obtiene el cliente genérico (público en general) de la tienda.
     */
    public function findPublicByShop(int $shopId): ?array
    {
        return Database::fetch(
            'SELECT id, shop_id, name, phone, email, address, rfc, notes, is_public, status, created_at
             FROM customers
             WHERE shop_id = :shop_id AND is_public = 1
             LIMIT 1',
            ['shop_id' => $shopId]
        );
    }

    /**
     * Comprueba si el email ya existe en la tienda (email vacío no se considera duplicado).
     */
    public function emailExistsInShop(string $email, int $shopId, ?int $excludeId = null): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM customers WHERE shop_id = :shop_id AND email = :email';
        $params = ['shop_id' => $shopId, 'email' => $email];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params) !== null;
    }

    /**
     * Número de ventas asociadas al cliente (para no permitir eliminación si tiene historial).
     */
    public function countSalesByCustomer(int $customerId): int
    {
        $row = Database::fetch('SELECT COUNT(*) AS total FROM sales WHERE customer_id = :id', ['id' => $customerId]);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * Crea un cliente. is_public debe ser 0 para altas normales.
     *
     * @param array{name:string, phone?:string|null, email?:string|null, address?:string|null, rfc?:string|null, notes?:string|null, status?:string} $data
     */
    public function create(int $shopId, array $data): int
    {
        $c = $data;
        Database::execute(
            'INSERT INTO customers (shop_id, name, phone, email, address, rfc, notes, is_public, status, created_at)
             VALUES (:shop_id, :name, :phone, :email, :address, :rfc, :notes, 0, :status, NOW())',
            [
                'shop_id' => $shopId,
                'name' => $c['name'],
                'phone' => $c['phone'] ?? null,
                'email' => $c['email'] ?? null,
                'address' => $c['address'] ?? null,
                'rfc' => $c['rfc'] ?? null,
                'notes' => $c['notes'] ?? null,
                'status' => $c['status'] ?? 'ACTIVE',
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Actualiza un cliente. No se debe cambiar is_public desde aquí para el cliente genérico.
     *
     * @param array{name?:string, phone?:string|null, email?:string|null, address?:string|null, rfc?:string|null, notes?:string|null, status?:string} $data
     */
    public function update(int $id, int $shopId, array $data): bool
    {
        $c = $data;
        $n = Database::execute(
            'UPDATE customers SET
             name = :name, phone = :phone, email = :email, address = :address, rfc = :rfc, notes = :notes, status = :status
             WHERE id = :id AND shop_id = :shop_id',
            [
                'id' => $id,
                'shop_id' => $shopId,
                'name' => $c['name'],
                'phone' => $c['phone'] ?? null,
                'email' => $c['email'] ?? null,
                'address' => $c['address'] ?? null,
                'rfc' => $c['rfc'] ?? null,
                'notes' => $c['notes'] ?? null,
                'status' => $c['status'] ?? 'ACTIVE',
            ]
        );
        return $n > 0;
    }

    /**
     * Cambia estado activo/inactivo. No permite desactivar al cliente genérico.
     */
    public function toggleStatus(int $id, int $shopId): ?string
    {
        $cust = $this->findById($id, $shopId);
        if (!$cust || !empty($cust['is_public'])) {
            return null;
        }
        $newStatus = ($cust['status'] ?? '') === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        Database::execute(
            'UPDATE customers SET status = :status WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId, 'status' => $newStatus]
        );
        return $newStatus;
    }

    /**
     * Búsqueda rápida para POS: por nombre, teléfono o correo. Solo activos, límite bajo.
     */
    public function searchForPos(int $shopId, string $query, int $limit = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return $this->listByShop($shopId, 1, '', 'ACTIVE')['items'];
        }
        $term = '%' . $query . '%';
        return Database::fetchAll(
            'SELECT id, name, phone, email, is_public, status
             FROM customers
             WHERE shop_id = :shop_id AND status = "ACTIVE"
               AND (name LIKE :q OR phone LIKE :q2 OR email LIKE :q3)
             ORDER BY is_public DESC, name ASC
             LIMIT ' . max(1, min(50, $limit)),
            ['shop_id' => $shopId, 'q' => $term, 'q2' => $term, 'q3' => $term]
        );
    }

    /**
     * Registro único por operación de abono o liquidación (lo que ve el usuario en el historial).
     */
    public function insertDebtSettlement(
        int $shopId,
        int $customerId,
        string $settlementType,
        float $amount,
        string $paymentMethod,
        ?string $observaciones,
        int $createdBy
    ): int {
        if (!in_array($settlementType, ['ABONO', 'LIQUIDACION'], true)) {
            throw new \InvalidArgumentException('Tipo de movimiento inválido.');
        }
        try {
            Database::execute(
                'INSERT INTO customer_debt_settlements (
                    shop_id, customer_id, settlement_type, amount, payment_method, observaciones, created_by, created_at
                 ) VALUES (
                    :shop_id, :customer_id, :settlement_type, :amount, :payment_method, :observaciones, :created_by, NOW()
                 )',
                [
                    'shop_id' => $shopId,
                    'customer_id' => $customerId,
                    'settlement_type' => $settlementType,
                    'amount' => round($amount, 2),
                    'payment_method' => $paymentMethod,
                    'observaciones' => $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null,
                    'created_by' => $createdBy,
                ]
            );
        } catch (PDOException $e) {
            if (self::isMissingDebtSettlementsTable($e)) {
                throw new \RuntimeException(
                    'Falta la tabla customer_debt_settlements. En el servidor ejecute: php bin/console.php migrate ' .
                    'o importe database/sql/hito14_customer_debt_settlements.sql en la base de datos.',
                    0,
                    $e
                );
            }
            throw $e;
        }
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Historial de abonos/liquidaciones (una fila por operación).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listDebtSettlementsByCustomer(int $shopId, int $customerId): array
    {
        try {
            $rows = Database::fetchAll(
                'SELECT id, settlement_type, amount, payment_method, observaciones, created_at
                 FROM customer_debt_settlements
                 WHERE shop_id = :shop_id AND customer_id = :customer_id
                 ORDER BY created_at DESC, id DESC',
                ['shop_id' => $shopId, 'customer_id' => $customerId]
            );
            return is_array($rows) ? $rows : [];
        } catch (PDOException $e) {
            if (self::isMissingDebtSettlementsTable($e)) {
                return [];
            }
            throw $e;
        }
    }

    private static function isMissingDebtSettlementsTable(PDOException $e): bool
    {
        $m = $e->getMessage();
        if (!str_contains($m, 'customer_debt_settlements')) {
            return false;
        }
        return str_contains($m, "doesn't exist")
            || str_contains($m, '1146')
            || $e->getCode() === '42S02';
    }
}
