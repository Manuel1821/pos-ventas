<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class CashSessionRepository
{
    /**
     * Obtiene la sesión de caja abierta de la tienda, si existe.
     */
    public function getOpenByShop(int $shopId): ?array
    {
        return Database::fetch(
            'SELECT id, shop_id, opened_by, status, opened_at, closed_at, created_at,
                    COALESCE(initial_amount, 0) AS initial_amount,
                    expected_amount, counted_amount, difference, closed_by, observations
             FROM cash_sessions
             WHERE shop_id = :shop_id AND status = "OPEN"
             ORDER BY opened_at DESC
             LIMIT 1',
            ['shop_id' => $shopId]
        );
    }

    /**
     * Busca una sesión por id y tienda.
     */
    public function findById(int $id, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT id, shop_id, opened_by, status, opened_at, closed_at, created_at,
                    COALESCE(initial_amount, 0) AS initial_amount,
                    expected_amount, counted_amount, difference, closed_by, observations
             FROM cash_sessions
             WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId]
        );
    }

    /**
     * Crea una nueva sesión de caja (apertura).
     */
    public function create(int $shopId, int $openedBy, float $initialAmount): int
    {
        Database::execute(
            'INSERT INTO cash_sessions (shop_id, opened_by, status, initial_amount, opened_at, created_at)
             VALUES (:shop_id, :opened_by, "OPEN", :initial_amount, NOW(), NOW())',
            [
                'shop_id' => $shopId,
                'opened_by' => $openedBy,
                'initial_amount' => round($initialAmount, 2),
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Cierra la sesión: status, closed_at, expected_amount, counted_amount, difference, closed_by, observations.
     */
    public function close(
        int $sessionId,
        int $shopId,
        float $expectedAmount,
        ?float $countedAmount,
        int $closedBy,
        ?string $observations
    ): bool {
        $difference = $countedAmount !== null ? round($countedAmount - $expectedAmount, 2) : null;
        $obsTrimmed = $observations === null ? '' : trim($observations);
        $n = Database::execute(
            'UPDATE cash_sessions SET
             status = "CLOSED", closed_at = NOW(),
             expected_amount = :expected_amount, counted_amount = :counted_amount,
             difference = :difference, closed_by = :closed_by, observations = :observations
             WHERE id = :id AND shop_id = :shop_id AND status = "OPEN"',
            [
                'id' => $sessionId,
                'shop_id' => $shopId,
                'expected_amount' => round($expectedAmount, 2),
                'counted_amount' => $countedAmount !== null ? round($countedAmount, 2) : null,
                'difference' => $difference,
                'closed_by' => $closedBy,
                'observations' => $obsTrimmed !== '' ? $obsTrimmed : null,
            ]
        );
        return $n > 0;
    }

    private const PER_PAGE = 15;

    /**
     * Historial de sesiones con paginación y filtros.
     *
     * @param array{desde?:string, hasta?:string, estado?:string} $filters
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public function listHistory(int $shopId, int $page = 1, array $filters = []): array
    {
        $params = ['shop_id' => $shopId];
        $where = ['cs.shop_id = :shop_id'];
        if (!empty($filters['desde'])) {
            $where[] = 'DATE(cs.opened_at) >= :desde';
            $params['desde'] = $filters['desde'];
        }
        if (!empty($filters['hasta'])) {
            $where[] = 'DATE(cs.opened_at) <= :hasta';
            $params['hasta'] = $filters['hasta'];
        }
        if (!empty($filters['estado']) && in_array($filters['estado'], ['OPEN', 'CLOSED'], true)) {
            $where[] = 'cs.status = :estado';
            $params['estado'] = $filters['estado'];
        }
        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * self::PER_PAGE;

        $countSql = "SELECT COUNT(*) AS total FROM cash_sessions cs WHERE {$whereSql}";
        $total = (int) (Database::fetch($countSql, $params)['total'] ?? 0);

        $sql = "SELECT cs.id, cs.shop_id, cs.opened_by, cs.status, cs.opened_at, cs.closed_at, cs.created_at,
                       COALESCE(cs.initial_amount, 0) AS initial_amount,
                       cs.expected_amount, cs.counted_amount, cs.difference, cs.closed_by, cs.observations,
                       u_open.first_name AS opened_by_name
                FROM cash_sessions cs
                LEFT JOIN users u_open ON u_open.id = cs.opened_by
                WHERE {$whereSql}
                ORDER BY cs.opened_at DESC
                LIMIT " . self::PER_PAGE . " OFFSET " . (int) $offset;
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
}
