<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use PDO;

class ProductRepository
{
    private const PER_PAGE = 15;

    /**
     * Búsqueda rápida para POS: por nombre, SKU o código de barras.
     * Solo productos activos para venta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchForPos(int $shopId, string $query, int $limit = 20): array
    {
        $query = trim($query);
        $limit = max(1, min(50, $limit));
        if ($query === '') {
            return Database::fetchAll(
                'SELECT p.id, p.shop_id, p.name, p.sku, p.barcode, p.price, p.tax_percent, p.stock, p.is_inventory_item, p.image_path
                 FROM products p
                 WHERE p.shop_id = :shop_id AND p.status = "ACTIVE"
                 ORDER BY p.name ASC
                 LIMIT ' . (int) $limit,
                ['shop_id' => $shopId]
            );
        }
        $term = '%' . $query . '%';
        return Database::fetchAll(
            'SELECT p.id, p.shop_id, p.name, p.sku, p.barcode, p.price, p.tax_percent, p.stock, p.is_inventory_item, p.image_path
             FROM products p
             WHERE p.shop_id = :shop_id AND p.status = "ACTIVE"
               AND (p.name LIKE :q OR p.sku LIKE :q2 OR p.barcode LIKE :q3)
             ORDER BY p.name ASC
             LIMIT ' . (int) $limit,
            ['shop_id' => $shopId, 'q' => $term, 'q2' => $term, 'q3' => $term]
        );
    }

    /**
     * Bloquea productos para operación de venta (evita oversell).
     *
     * @param int[] $productIds
     * @return array<int, array<string, mixed>> Mapa product_id => producto
     */
    public function lockByIdsForSale(array $productIds, int $shopId): array
    {
        $productIds = array_values(array_unique(array_map(fn ($id) => (int) $id, $productIds)));
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = 'SELECT p.id, p.name, p.price, p.tax_percent, p.stock, p.is_inventory_item
                FROM products p
                WHERE p.shop_id = ?
                  AND p.status = "ACTIVE"
                  AND p.id IN (' . $placeholders . ')
                FOR UPDATE';

        $pdo = Database::pdo();
        $stmt = $pdo->prepare($sql);
        $params = array_merge([$shopId], $productIds);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) ($row['id'] ?? 0)] = $row;
        }
        return $map;
    }

    /**
     * Decrementa stock de un producto inventariable.
     */
    public function decrementStock(int $shopId, int $productId, float $qty): void
    {
        Database::execute(
            'UPDATE products
             SET stock = ROUND(stock - :qty, 3), updated_at = NOW()
             WHERE id = :id AND shop_id = :shop_id',
            [
                'qty' => round($qty, 3),
                'id' => $productId,
                'shop_id' => $shopId,
            ]
        );
    }

    /**
     * Incrementa stock de un producto inventariable.
     */
    public function incrementStock(int $shopId, int $productId, float $qty): void
    {
        Database::execute(
            'UPDATE products
             SET stock = ROUND(stock + :qty, 3), updated_at = NOW()
             WHERE id = :id AND shop_id = :shop_id',
            [
                'qty' => round($qty, 3),
                'id' => $productId,
                'shop_id' => $shopId,
            ]
        );
    }

    /**
     * Lista productos con paginación, búsqueda y filtros.
     *
     * @return array{items: array, total: int, page: int, per_page: int, total_pages: int}
     */
    public function listByShop(int $shopId, int $page = 1, string $search = '', ?int $categoryId = null, ?string $status = null): array
    {
        $offset = ($page - 1) * self::PER_PAGE;
        $params = ['shop_id' => $shopId];
        $where = ['p.shop_id = :shop_id'];
        if ($search !== '') {
            $where[] = '(p.name LIKE :search OR p.sku LIKE :search2 OR p.barcode LIKE :search3)';
            $term = '%' . $search . '%';
            $params['search'] = $term;
            $params['search2'] = $term;
            $params['search3'] = $term;
        }
        if ($categoryId !== null) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        if ($status !== null && in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $where[] = 'p.status = :status';
            $params['status'] = $status;
        }
        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS total FROM products p WHERE {$whereSql}";
        $total = (int) (Database::fetch($countSql, $params)['total'] ?? 0);

        $sql = "SELECT p.id, p.shop_id, p.category_id, p.name, p.sku, p.barcode, p.description, p.unit,
                       p.price, p.cost, p.tax_percent, p.stock, p.is_inventory_item, p.status, p.image_path, p.created_at,
                       c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE {$whereSql}
                ORDER BY p.name ASC
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

    public function findById(int $id, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT p.id, p.shop_id, p.category_id, p.name, p.sku, p.barcode, p.description, p.unit,
                    p.price, p.cost, p.tax_percent, p.stock, p.is_inventory_item, p.status, p.image_path, p.created_at, p.updated_at,
                    c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id AND p.shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId]
        );
    }

    /**
     * Productos activos para selects (lotes, reportes auxiliares).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listActiveForSelect(int $shopId, int $limit = 1000): array
    {
        $limit = max(1, min(2000, $limit));

        return Database::fetchAll(
            'SELECT id, name, sku FROM products
             WHERE shop_id = :shop_id AND status = "ACTIVE"
             ORDER BY name ASC
             LIMIT ' . (int) $limit,
            ['shop_id' => $shopId]
        );
    }

    public function skuExistsInShop(string $sku, int $shopId, ?int $excludeId = null): bool
    {
        if ($sku === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM products WHERE shop_id = :shop_id AND sku = :sku';
        $params = ['shop_id' => $shopId, 'sku' => $sku];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params) !== null;
    }

    public function barcodeExistsInShop(string $barcode, int $shopId, ?int $excludeId = null): bool
    {
        if ($barcode === '') {
            return false;
        }
        $sql = 'SELECT 1 FROM products WHERE shop_id = :shop_id AND barcode = :barcode';
        $params = ['shop_id' => $shopId, 'barcode' => $barcode];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params) !== null;
    }

    /**
     * @param array{name:string, sku?:string, barcode?:string, description?:string, category_id?:int|null, unit?:string, price?:float, cost?:float, tax_percent?:float, stock?:float, is_inventory_item?:bool, status?:string, image_path?:string|null} $data
     */
    public function create(int $shopId, array $data): int
    {
        $p = $data;
        Database::execute(
            'INSERT INTO products (shop_id, category_id, name, sku, barcode, description, unit, price, cost, tax_percent, stock, is_inventory_item, status, image_path, created_at)
             VALUES (:shop_id, :category_id, :name, :sku, :barcode, :description, :unit, :price, :cost, :tax_percent, :stock, :is_inventory_item, :status, :image_path, NOW())',
            [
                'shop_id' => $shopId,
                'category_id' => $p['category_id'] ?? null,
                'name' => $p['name'],
                'sku' => $p['sku'] ?? null,
                'barcode' => $p['barcode'] ?? null,
                'description' => $p['description'] ?? null,
                'unit' => $p['unit'] ?? 'Unidad',
                'price' => $p['price'] ?? 0,
                'cost' => $p['cost'] ?? 0,
                'tax_percent' => $p['tax_percent'] ?? 0,
                'stock' => $p['stock'] ?? 0,
                'is_inventory_item' => !empty($p['is_inventory_item']) ? 1 : 0,
                'status' => $p['status'] ?? 'ACTIVE',
                'image_path' => $p['image_path'] ?? null,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @param array{name?:string, sku?:string, barcode?:string, description?:string, category_id?:int|null, unit?:string, price?:float, cost?:float, tax_percent?:float, stock?:float, is_inventory_item?:bool, status?:string, image_path?:string|null} $data
     */
    public function update(int $id, int $shopId, array $data): bool
    {
        $p = $data;
        $n = Database::execute(
            'UPDATE products SET
             category_id = :category_id, name = :name, sku = :sku, barcode = :barcode, description = :description,
             unit = :unit, price = :price, cost = :cost, tax_percent = :tax_percent, stock = :stock,
             is_inventory_item = :is_inventory_item, status = :status, image_path = COALESCE(:image_path, image_path), updated_at = NOW()
             WHERE id = :id AND shop_id = :shop_id',
            [
                'id' => $id,
                'shop_id' => $shopId,
                'category_id' => $p['category_id'] ?? null,
                'name' => $p['name'],
                'sku' => $p['sku'] ?? null,
                'barcode' => $p['barcode'] ?? null,
                'description' => $p['description'] ?? null,
                'unit' => $p['unit'] ?? 'Unidad',
                'price' => $p['price'] ?? 0,
                'cost' => $p['cost'] ?? 0,
                'tax_percent' => $p['tax_percent'] ?? 0,
                'stock' => $p['stock'] ?? 0,
                'is_inventory_item' => !empty($p['is_inventory_item']) ? 1 : 0,
                'status' => $p['status'] ?? 'ACTIVE',
                'image_path' => $p['image_path'] ?? null,
            ]
        );
        return $n > 0;
    }

    public function toggleStatus(int $id, int $shopId): ?string
    {
        $prod = $this->findById($id, $shopId);
        if (!$prod) {
            return null;
        }
        $newStatus = ($prod['status'] ?? '') === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        Database::execute(
            'UPDATE products SET status = :status, updated_at = NOW() WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId, 'status' => $newStatus]
        );
        return $newStatus;
    }

    /**
     * Solo actualiza image_path (para no pisar el resto).
     */
    public function updateImagePath(int $id, int $shopId, ?string $imagePath): bool
    {
        $n = Database::execute(
            'UPDATE products SET image_path = :image_path, updated_at = NOW() WHERE id = :id AND shop_id = :shop_id',
            ['id' => $id, 'shop_id' => $shopId, 'image_path' => $imagePath]
        );
        return $n > 0;
    }

    /**
     * Galería del producto (verificado por tienda).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listImagesByProductId(int $productId, int $shopId): array
    {
        return Database::fetchAll(
            'SELECT pi.id, pi.product_id, pi.path, pi.thumb_path, pi.sort_order, pi.is_primary, pi.created_at
             FROM product_images pi
             INNER JOIN products p ON p.id = pi.product_id
             WHERE pi.product_id = :pid AND p.shop_id = :shop_id
             ORDER BY pi.sort_order ASC, pi.id ASC',
            ['pid' => $productId, 'shop_id' => $shopId]
        );
    }

    public function maxImageSortOrder(int $productId): int
    {
        $row = Database::fetch(
            'SELECT COALESCE(MAX(sort_order), -1) AS m FROM product_images WHERE product_id = :pid',
            ['pid' => $productId]
        );

        return (int) ($row['m'] ?? -1);
    }

    /**
     * Inserta una fila en product_images. Si $isPrimary, quita el principal anterior.
     */
    public function insertProductImage(int $productId, string $path, ?string $thumbPath, int $sortOrder, bool $isPrimary): int
    {
        if ($isPrimary) {
            Database::execute(
                'UPDATE product_images SET is_primary = 0 WHERE product_id = :pid',
                ['pid' => $productId]
            );
        }
        Database::execute(
            'INSERT INTO product_images (product_id, path, thumb_path, sort_order, is_primary, created_at)
             VALUES (:product_id, :path, :thumb_path, :sort_order, :is_primary, NOW())',
            [
                'product_id' => $productId,
                'path' => $path,
                'thumb_path' => $thumbPath !== null && $thumbPath !== '' ? $thumbPath : null,
                'sort_order' => $sortOrder,
                'is_primary' => $isPrimary ? 1 : 0,
            ]
        );

        return (int) Database::pdo()->lastInsertId();
    }

    public function updateProductImageThumbPath(int $imageId, int $productId, string $thumbPath): void
    {
        Database::execute(
            'UPDATE product_images SET thumb_path = :thumb_path WHERE id = :id AND product_id = :pid',
            ['thumb_path' => $thumbPath, 'id' => $imageId, 'pid' => $productId]
        );
    }

    /**
     * Fila de imagen con thumb_path (verificación tienda).
     */
    public function findImageRowWithThumb(int $imageId, int $productId, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT pi.id, pi.path, pi.thumb_path, pi.is_primary
             FROM product_images pi
             INNER JOIN products p ON p.id = pi.product_id
             WHERE pi.id = :iid AND pi.product_id = :pid AND p.shop_id = :sid',
            ['iid' => $imageId, 'pid' => $productId, 'sid' => $shopId]
        );
    }

    /**
     * Imagen marcada como principal del producto.
     *
     * @return array<string, mixed>|null
     */
    public function findPrimaryImageRow(int $productId, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT pi.id, pi.path, pi.thumb_path
             FROM product_images pi
             INNER JOIN products p ON p.id = pi.product_id
             WHERE pi.product_id = :pid AND p.shop_id = :sid AND pi.is_primary = 1
             LIMIT 1',
            ['pid' => $productId, 'sid' => $shopId]
        );
    }

    /**
     * Sincroniza products.image_path con la fila marcada is_primary (o null si no hay).
     */
    public function syncPrimaryImagePath(int $productId, int $shopId): void
    {
        $row = Database::fetch(
            'SELECT pi.path FROM product_images pi
             INNER JOIN products p ON p.id = pi.product_id
             WHERE pi.product_id = :pid AND p.shop_id = :sid AND pi.is_primary = 1
             LIMIT 1',
            ['pid' => $productId, 'sid' => $shopId]
        );
        $path = $row['path'] ?? null;
        Database::execute(
            'UPDATE products SET image_path = :image_path, updated_at = NOW() WHERE id = :id AND shop_id = :sid',
            ['image_path' => $path, 'id' => $productId, 'sid' => $shopId]
        );
    }

    public function findImageRow(int $imageId, int $productId, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT pi.id, pi.path, pi.thumb_path, pi.is_primary
             FROM product_images pi
             INNER JOIN products p ON p.id = pi.product_id
             WHERE pi.id = :iid AND pi.product_id = :pid AND p.shop_id = :sid',
            ['iid' => $imageId, 'pid' => $productId, 'sid' => $shopId]
        );
    }

    /**
     * Ruta relativa a storage/uploads si existe y pertenece al producto/tienda.
     */
    public function findImagePathForProduct(int $imageId, int $productId, int $shopId): ?string
    {
        $row = $this->findImageRow($imageId, $productId, $shopId);
        if (!$row) {
            return null;
        }

        return (string) ($row['path'] ?? '');
    }

    public function setPrimaryImage(int $imageId, int $productId, int $shopId): bool
    {
        if (!$this->findImageRow($imageId, $productId, $shopId)) {
            return false;
        }
        Database::execute(
            'UPDATE product_images SET is_primary = 0 WHERE product_id = :pid',
            ['pid' => $productId]
        );
        Database::execute(
            'UPDATE product_images SET is_primary = 1 WHERE id = :id AND product_id = :pid',
            ['id' => $imageId, 'pid' => $productId]
        );
        $this->syncPrimaryImagePath($productId, $shopId);

        return true;
    }

    /**
     * Elimina la imagen y devuelve path relativo para borrar archivo. Ajusta principal si hace falta.
     */
    /**
     * @return array{path: string, thumb_path: ?string}|null
     */
    public function deleteProductImage(int $imageId, int $productId, int $shopId): ?array
    {
        $row = $this->findImageRow($imageId, $productId, $shopId);
        if (!$row) {
            return null;
        }
        $path = (string) ($row['path'] ?? '');
        $thumbPath = isset($row['thumb_path']) ? (string) $row['thumb_path'] : '';
        $thumbPath = $thumbPath !== '' ? $thumbPath : null;
        $wasPrimary = (int) ($row['is_primary'] ?? 0) === 1;
        Database::execute(
            'DELETE FROM product_images WHERE id = :id AND product_id = :pid',
            ['id' => $imageId, 'pid' => $productId]
        );
        if ($wasPrimary) {
            $next = Database::fetch(
                'SELECT id FROM product_images WHERE product_id = :pid ORDER BY sort_order ASC, id ASC LIMIT 1',
                ['pid' => $productId]
            );
            if ($next) {
                Database::execute(
                    'UPDATE product_images SET is_primary = 1 WHERE id = :id',
                    ['id' => (int) ($next['id'] ?? 0)]
                );
            }
        }
        $this->syncPrimaryImagePath($productId, $shopId);

        if ($path === '') {
            return null;
        }

        return ['path' => $path, 'thumb_path' => $thumbPath];
    }

    /**
     * Si no hay ninguna principal pero hay filas, marca la primera como principal.
     */
    public function ensurePrimaryImage(int $productId, int $shopId): void
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS c FROM product_images WHERE product_id = :pid',
            ['pid' => $productId]
        );
        if ((int) ($row['c'] ?? 0) === 0) {
            $this->syncPrimaryImagePath($productId, $shopId);

            return;
        }
        $has = Database::fetch(
            'SELECT id FROM product_images WHERE product_id = :pid AND is_primary = 1 LIMIT 1',
            ['pid' => $productId]
        );
        if ($has) {
            $this->syncPrimaryImagePath($productId, $shopId);

            return;
        }
        $first = Database::fetch(
            'SELECT id FROM product_images WHERE product_id = :pid ORDER BY sort_order ASC, id ASC LIMIT 1',
            ['pid' => $productId]
        );
        if ($first) {
            Database::execute(
                'UPDATE product_images SET is_primary = 0 WHERE product_id = :pid',
                ['pid' => $productId]
            );
            Database::execute(
                'UPDATE product_images SET is_primary = 1 WHERE id = :id',
                ['id' => (int) ($first['id'] ?? 0)]
            );
        }
        $this->syncPrimaryImagePath($productId, $shopId);
    }
}
