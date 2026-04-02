<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class ExpenseCategoryRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByShop(int $shopId, bool $onlyActive = false): array
    {
        $sql = 'SELECT id, shop_id, name, slug, status, created_at
                FROM expense_categories
                WHERE shop_id = :shop_id';
        if ($onlyActive) {
            $sql .= ' AND status = "ACTIVE"';
        }
        $sql .= ' ORDER BY name ASC';
        return Database::fetchAll($sql, ['shop_id' => $shopId]);
    }

    public function findById(int $id, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT id, shop_id, name, slug, status, created_at
             FROM expense_categories
             WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId]
        );
    }

    public function existsSlugInShop(string $nameOrSlug, int $shopId, ?int $excludeId = null): bool
    {
        $slug = $this->slugFromName($nameOrSlug);
        $sql = 'SELECT 1 FROM expense_categories WHERE shop_id = :shop_id AND slug = :slug';
        $params = ['shop_id' => $shopId, 'slug' => $slug];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params) !== null;
    }

    public function create(int $shopId, string $name, string $status = 'ACTIVE'): int
    {
        $slug = $this->slugFromName($name);
        Database::execute(
            'INSERT INTO expense_categories (shop_id, name, slug, status, created_at)
             VALUES (:shop_id, :name, :slug, :status, NOW())',
            ['shop_id' => $shopId, 'name' => $name, 'slug' => $slug, 'status' => $status]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public function update(int $id, int $shopId, string $name, string $status): bool
    {
        $slug = $this->slugFromName($name);
        $n = Database::execute(
            'UPDATE expense_categories SET name = :name, slug = :slug, status = :status
             WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId, 'name' => $name, 'slug' => $slug, 'status' => $status]
        );
        return $n > 0;
    }

    public function toggleStatus(int $id, int $shopId): ?string
    {
        $cat = $this->findById($id, $shopId);
        if (!$cat) {
            return null;
        }
        $newStatus = ($cat['status'] ?? '') === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        Database::execute(
            'UPDATE expense_categories SET status = :status WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId, 'status' => $newStatus]
        );
        return $newStatus;
    }

    private function slugFromName(string $name): string
    {
        $slug = mb_strtolower(trim($name), 'UTF-8');
        $slug = preg_replace('/[^a-z0-9\s\-]/u', '', $slug);
        $slug = preg_replace('/[\s\-]+/', '-', $slug);
        return substr($slug ?: 'gasto', 0, 150);
    }
}
