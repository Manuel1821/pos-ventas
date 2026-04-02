<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class CategoryRepository
{
    /**
     * Lista categorías de una tienda con opción de incluir inactivas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listByShop(int $shopId, bool $onlyActive = false): array
    {
        $sql = 'SELECT id, shop_id, name, slug, status, created_at
                FROM categories
                WHERE shop_id = :shop_id';
        if ($onlyActive) {
            $sql .= ' AND status = "ACTIVE"';
        }
        $sql .= ' ORDER BY name ASC';
        return Database::fetchAll($sql, ['shop_id' => $shopId]);
    }

    public function findById(int $id, int $shopId): ?array
    {
        $row = Database::fetch(
            'SELECT id, shop_id, name, slug, status, created_at
             FROM categories
             WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId]
        );
        return $row;
    }

    public function findBySlugInShop(string $slug, int $shopId): ?array
    {
        $slug = $this->slugFromName($slug);
        return Database::fetch(
            'SELECT id, shop_id, name, slug, status, created_at
             FROM categories
             WHERE shop_id = :shop_id AND slug = :slug
             LIMIT 1',
            ['shop_id' => $shopId, 'slug' => $slug]
        );
    }

    public function existsSlugInShop(string $slug, int $shopId, ?int $excludeId = null): bool
    {
        $slug = $this->slugFromName($slug);
        $sql = 'SELECT 1 FROM categories WHERE shop_id = :shop_id AND slug = :slug';
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
            'INSERT INTO categories (shop_id, name, slug, status, created_at)
             VALUES (:shop_id, :name, :slug, :status, NOW())',
            ['shop_id' => $shopId, 'name' => $name, 'slug' => $slug, 'status' => $status]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public function update(int $id, int $shopId, string $name, string $status): bool
    {
        $slug = $this->slugFromName($name);
        $n = Database::execute(
            'UPDATE categories SET name = :name, slug = :slug, status = :status
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
            'UPDATE categories SET status = :status WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId, 'status' => $newStatus]
        );
        return $newStatus;
    }

    private function slugFromName(string $name): string
    {
        $slug = mb_strtolower(trim($name), 'UTF-8');
        $slug = preg_replace('/[^a-z0-9\s\-]/u', '', $slug);
        $slug = preg_replace('/[\s\-]+/', '-', $slug);
        return substr($slug ?: 'categoria', 0, 150);
    }
}
