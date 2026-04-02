<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Support\ImageThumbnail;
use App\Support\ProductThumbnailDelivery;
use App\Validation\ProductValidator;

class ProductController
{
    private ProductRepository $productRepo;
    private CategoryRepository $categoryRepo;

    public function __construct()
    {
        $this->productRepo = new ProductRepository();
        $this->categoryRepo = new CategoryRepository();
    }

    public function index(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $flash = Flash::consume();
        $page = max(1, (int) ($request->query['pagina'] ?? 1));
        $search = trim((string) ($request->query['buscar'] ?? ''));
        $categoryId = isset($request->query['categoria']) ? (int) $request->query['categoria'] : null;
        if ($categoryId === 0) {
            $categoryId = null;
        }
        $status = isset($request->query['estado']) ? (string) $request->query['estado'] : null;
        if ($status !== null && !in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            $status = null;
        }
        $result = $this->productRepo->listByShop($shopId, $page, $search, $categoryId, $status);
        $categories = $this->categoryRepo->listByShop($shopId);

        View::render('admin/productos/indice', [
            'products' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'total_pages' => $result['total_pages'],
            'per_page' => $result['per_page'],
            'search' => $search,
            'categoryId' => $categoryId,
            'statusFilter' => $status,
            'categories' => $categories,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
        ]);
    }

    public function create(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $categories = $this->categoryRepo->listByShop($shopId, true);
        $flash = Flash::consume();
        View::render('admin/productos/crear', [
            'categories' => $categories,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'errors' => [],
            'old' => [],
        ]);
    }

    public function store(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $body = $request->body;
        $old = [
            'name' => trim((string) ($body['name'] ?? '')),
            'sku' => trim((string) ($body['sku'] ?? '')),
            'barcode' => trim((string) ($body['barcode'] ?? '')),
            'description' => trim((string) ($body['description'] ?? '')),
            'category_id' => isset($body['category_id']) ? (int) $body['category_id'] : null,
            'unit' => trim((string) ($body['unit'] ?? 'Unidad')),
            'price' => $body['price'] ?? '',
            'cost' => $body['cost'] ?? '',
            'tax_percent' => $body['tax_percent'] ?? '',
            'stock' => $body['stock'] ?? '',
            'is_inventory_item' => !empty($body['is_inventory_item']),
        ];
        $errors = ProductValidator::validate($old);
        if (($old['sku'] ?? '') !== '' && $this->productRepo->skuExistsInShop($old['sku'], $shopId)) {
            $errors[] = 'El SKU ya está en uso por otro producto.';
        }
        if (($old['barcode'] ?? '') !== '' && $this->productRepo->barcodeExistsInShop($old['barcode'], $shopId)) {
            $errors[] = 'El código de barras ya está en uso por otro producto.';
        }
        if ($errors !== []) {
            $categories = $this->categoryRepo->listByShop($shopId, true);
            View::render('admin/productos/crear', [
                'categories' => $categories,
                'userName' => $this->getUserName(),
                'shopName' => $this->getShopName(),
                'flash' => null,
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }
        $data = [
            'name' => $old['name'],
            'sku' => $old['sku'] !== '' ? $old['sku'] : null,
            'barcode' => $old['barcode'] !== '' ? $old['barcode'] : null,
            'description' => $old['description'] !== '' ? $old['description'] : null,
            'category_id' => $old['category_id'] ?: null,
            'unit' => $old['unit'] ?: 'Unidad',
            'price' => (float) str_replace(',', '.', (string) $old['price']),
            'cost' => (float) str_replace(',', '.', (string) $old['cost']),
            'tax_percent' => (float) str_replace(',', '.', (string) ($old['tax_percent'] ?? '0')),
            'stock' => (float) str_replace(',', '.', (string) ($old['stock'] ?? '0')),
            'is_inventory_item' => $old['is_inventory_item'],
            'status' => 'ACTIVE',
            'image_path' => null,
        ];
        $newId = $this->productRepo->create($shopId, $data);
        $this->processNewProductImages($newId, $shopId, $body);
        Flash::set('success', 'Producto creado correctamente.');
        Redirect::to('/admin/productos');
    }

    public function edit(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $product = $id ? $this->productRepo->findById($id, $shopId) : null;
        if (!$product) {
            Flash::set('danger', 'Producto no encontrado.');
            Redirect::to('/admin/productos');
        }
        $categories = $this->categoryRepo->listByShop($shopId);
        $flash = Flash::consume();
        $productImages = $this->productRepo->listImagesByProductId($id, $shopId);
        View::render('admin/productos/editar', [
            'product' => $product,
            'productImages' => $productImages,
            'categories' => $categories,
            'userName' => $this->getUserName(),
            'shopName' => $this->getShopName(),
            'flash' => $flash,
            'errors' => [],
            'old' => $product,
        ]);
    }

    public function update(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $product = $id ? $this->productRepo->findById($id, $shopId) : null;
        if (!$product) {
            Flash::set('danger', 'Producto no encontrado.');
            Redirect::to('/admin/productos');
        }
        $body = $request->body;
        $old = [
            'name' => trim((string) ($body['name'] ?? '')),
            'sku' => trim((string) ($body['sku'] ?? '')),
            'barcode' => trim((string) ($body['barcode'] ?? '')),
            'description' => trim((string) ($body['description'] ?? '')),
            'category_id' => isset($body['category_id']) ? (int) $body['category_id'] : null,
            'unit' => trim((string) ($body['unit'] ?? 'Unidad')),
            'price' => $body['price'] ?? '',
            'cost' => $body['cost'] ?? '',
            'tax_percent' => $body['tax_percent'] ?? '',
            'stock' => $body['stock'] ?? '',
            'is_inventory_item' => !empty($body['is_inventory_item']),
            'status' => isset($body['status']) ? (string) $body['status'] : ($product['status'] ?? 'ACTIVE'),
        ];
        if (!in_array($old['status'], ['ACTIVE', 'INACTIVE'], true)) {
            $old['status'] = 'ACTIVE';
        }
        $errors = ProductValidator::validate($old);
        if (($old['sku'] ?? '') !== '' && $this->productRepo->skuExistsInShop($old['sku'], $shopId, $id)) {
            $errors[] = 'El SKU ya está en uso por otro producto.';
        }
        if (($old['barcode'] ?? '') !== '' && $this->productRepo->barcodeExistsInShop($old['barcode'], $shopId, $id)) {
            $errors[] = 'El código de barras ya está en uso por otro producto.';
        }
        if ($errors !== []) {
            $categories = $this->categoryRepo->listByShop($shopId);
            $productImages = $this->productRepo->listImagesByProductId($id, $shopId);
            View::render('admin/productos/editar', [
                'product' => $product,
                'productImages' => $productImages,
                'categories' => $categories,
                'userName' => $this->getUserName(),
                'shopName' => $this->getShopName(),
                'flash' => null,
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }
        $data = [
            'name' => $old['name'],
            'sku' => $old['sku'] !== '' ? $old['sku'] : null,
            'barcode' => $old['barcode'] !== '' ? $old['barcode'] : null,
            'description' => $old['description'] !== '' ? $old['description'] : null,
            'category_id' => $old['category_id'] ?: null,
            'unit' => $old['unit'] ?: 'Unidad',
            'price' => (float) str_replace(',', '.', (string) $old['price']),
            'cost' => (float) str_replace(',', '.', (string) $old['cost']),
            'tax_percent' => (float) str_replace(',', '.', (string) ($old['tax_percent'] ?? '0')),
            'stock' => (float) str_replace(',', '.', (string) ($old['stock'] ?? '0')),
            'is_inventory_item' => $old['is_inventory_item'],
            'status' => $old['status'],
            'image_path' => $product['image_path'] ?? null,
        ];
        $this->productRepo->update($id, $shopId, $data);
        $this->processEditProductImages($id, $shopId, $body);
        Flash::set('success', 'Producto actualizado correctamente.');
        Redirect::to('/admin/productos');
    }

    public function toggleStatus(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            Flash::set('danger', 'Sesión inválida.');
            Redirect::to('/login');
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $newStatus = $this->productRepo->toggleStatus($id, $shopId);
        if ($newStatus === null) {
            Flash::set('danger', 'Producto no encontrado.');
        } else {
            Flash::set('success', $newStatus === 'ACTIVE' ? 'Producto activado.' : 'Producto desactivado.');
        }
        Redirect::to('/admin/productos');
    }

    /**
     * Sirve la imagen principal del producto (products.image_path).
     */
    public function image(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            http_response_code(403);
            return;
        }
        $id = (int) ($request->routeParams['id'] ?? 0);
        $product = $id ? $this->productRepo->findById($id, $shopId) : null;
        if (!$product || empty($product['image_path'])) {
            http_response_code(404);
            return;
        }
        $this->sendStorageFile($product['image_path']);
    }

    /**
     * Sirve una imagen de la galería por id (product_images).
     */
    public function imageGallery(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            http_response_code(403);
            return;
        }
        $productId = (int) ($request->routeParams['productId'] ?? 0);
        $imageId = (int) ($request->routeParams['imageId'] ?? 0);
        $rel = $this->productRepo->findImagePathForProduct($imageId, $productId, $shopId);
        if ($rel === null || $rel === '') {
            http_response_code(404);
            return;
        }
        $this->sendStorageFile($rel);
    }

    /**
     * Miniatura de galería (admin): JPEG ~240px.
     */
    public function imageGalleryMiniatura(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            http_response_code(403);
            return;
        }
        $productId = (int) ($request->routeParams['productId'] ?? 0);
        $imageId = (int) ($request->routeParams['imageId'] ?? 0);
        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 2);
        (new ProductThumbnailDelivery($this->productRepo, $projectRoot))->sendGalleryThumbnail($shopId, $productId, $imageId);
    }

    /**
     * Miniatura de la foto principal del producto (listados, POS): /admin/productos/{id}/imagen-miniatura
     */
    public function imagePrimaryMiniatura(Request $request): void
    {
        $shopId = Auth::shopId();
        if ($shopId === null) {
            http_response_code(403);
            return;
        }
        $productId = (int) ($request->routeParams['id'] ?? 0);
        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 2);
        (new ProductThumbnailDelivery($this->productRepo, $projectRoot))->sendPrimaryThumbnail($shopId, $productId);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function processNewProductImages(int $productId, int $shopId, array $body): void
    {
        $paths = $this->saveUploadedImagesBatch($shopId, 'images');
        if ($paths === []) {
            return;
        }
        $choice = $this->parsePrimaryChoice((string) ($body['primary_choice'] ?? ''));
        $primaryIdx = 0;
        if ($choice['type'] === 'new') {
            $primaryIdx = max(0, min(count($paths) - 1, (int) ($choice['index'] ?? 0)));
        }
        foreach ($paths as $i => $item) {
            $p = is_array($item) ? (string) ($item['path'] ?? '') : (string) $item;
            $t = is_array($item) ? ($item['thumb_path'] ?? null) : null;
            $this->productRepo->insertProductImage($productId, $p, $t !== '' ? $t : null, $i, $i === $primaryIdx);
        }
        $this->productRepo->ensurePrimaryImage($productId, $shopId);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function processEditProductImages(int $productId, int $shopId, array $body): void
    {
        $deleteIds = $body['delete_image_ids'] ?? [];
        if (!is_array($deleteIds)) {
            $deleteIds = [];
        }
        foreach ($deleteIds as $did) {
            $did = (int) $did;
            if ($did <= 0) {
                continue;
            }
            $deleted = $this->productRepo->deleteProductImage($did, $productId, $shopId);
            if ($deleted !== null) {
                $this->unlinkStorageRelative($deleted['path']);
                if (!empty($deleted['thumb_path'])) {
                    $this->unlinkStorageRelative($deleted['thumb_path']);
                }
            }
        }

        $sortBase = $this->productRepo->maxImageSortOrder($productId) + 1;
        $newPaths = $this->saveUploadedImagesBatch($shopId, 'images');
        $newIds = [];
        foreach ($newPaths as $k => $item) {
            $p = is_array($item) ? (string) ($item['path'] ?? '') : (string) $item;
            $t = is_array($item) ? ($item['thumb_path'] ?? null) : null;
            $newIds[] = $this->productRepo->insertProductImage($productId, $p, $t !== '' ? $t : null, $sortBase + $k, false);
        }

        $choice = $this->parsePrimaryChoice((string) ($body['primary_choice'] ?? ''));
        $ok = false;
        if ($choice['type'] === 'existing' && isset($choice['id'])) {
            $ok = $this->productRepo->setPrimaryImage((int) $choice['id'], $productId, $shopId);
        } elseif ($choice['type'] === 'new' && isset($choice['index']) && $newIds !== []) {
            $ix = max(0, min(count($newIds) - 1, (int) $choice['index']));
            $ok = $this->productRepo->setPrimaryImage((int) $newIds[$ix], $productId, $shopId);
        }
        if (!$ok) {
            $this->productRepo->ensurePrimaryImage($productId, $shopId);
        }
    }

    /**
     * @return array{type: string, id?: int, index?: int}
     */
    private function parsePrimaryChoice(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['type' => 'none'];
        }
        if (preg_match('/^e_(\d+)$/', $raw, $m)) {
            return ['type' => 'existing', 'id' => (int) $m[1]];
        }
        if (preg_match('/^n_(\d+)$/', $raw, $m)) {
            return ['type' => 'new', 'index' => (int) $m[1]];
        }

        return ['type' => 'none'];
    }

    /**
     * @return list<array{path: string, thumb_path: ?string}>
     */
    private function saveUploadedImagesBatch(int $shopId, string $fieldName): array
    {
        $max = 20;
        $list = $this->collectUploadedFiles($fieldName);
        $out = [];
        foreach ($list as $file) {
            if (count($out) >= $max) {
                break;
            }
            $saved = $this->moveUploadedImageToStorage($shopId, $file['tmp_name']);
            if ($saved !== null) {
                $out[] = $saved;
            }
        }

        return $out;
    }

    /**
     * @return list<array{tmp_name: string}>
     */
    private function collectUploadedFiles(string $fieldName): array
    {
        $files = $_FILES[$fieldName] ?? null;
        if (!$files || !isset($files['name'])) {
            return [];
        }
        $out = [];
        if (is_array($files['name'])) {
            $n = count($files['name']);
            for ($i = 0; $i < $n; $i++) {
                if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $tmp = (string) ($files['tmp_name'][$i] ?? '');
                if ($tmp === '' || !is_uploaded_file($tmp)) {
                    continue;
                }
                $out[] = ['tmp_name' => $tmp];
            }
        } elseif (($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmp = (string) ($files['tmp_name'] ?? '');
            if ($tmp !== '' && is_uploaded_file($tmp)) {
                $out[] = ['tmp_name' => $tmp];
            }
        }

        return $out;
    }

    /**
     * @return array{path: string, thumb_path: ?string}|null
     */
    private function moveUploadedImageToStorage(int $shopId, string $tmpPath): ?array
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);
        if (!in_array($mime, $allowed, true)) {
            return null;
        }
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 2);
        $dir = $projectRoot . '/storage/uploads/productos/' . $shopId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $relativePath = 'productos/' . $shopId . '/' . $filename;
        $fullPath = $projectRoot . '/storage/uploads/' . $relativePath;
        if (!move_uploaded_file($tmpPath, $fullPath)) {
            return null;
        }

        $thumbRel = ImageThumbnail::suggestedThumbRelativePath($relativePath);
        $thumbFull = $projectRoot . '/storage/uploads/' . $thumbRel;
        $thumbOk = ImageThumbnail::createJpegThumbnail($fullPath, $thumbFull, ImageThumbnail::DEFAULT_MAX_EDGE);

        return [
            'path' => $relativePath,
            'thumb_path' => $thumbOk ? $thumbRel : null,
        ];
    }

    private function unlinkStorageRelative(string $relativePath): void
    {
        if ($relativePath === '') {
            return;
        }
        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 2);
        $full = $projectRoot . '/storage/uploads/' . $relativePath;
        $storageReal = realpath($projectRoot . '/storage');
        if ($storageReal === false) {
            return;
        }
        $fileReal = realpath($full);
        if ($fileReal !== false && str_starts_with($fileReal, $storageReal) && is_file($fileReal)) {
            @unlink($fileReal);
        }
    }

    private function sendStorageFile(string $relativePath): void
    {
        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 2);
        $fullPath = $projectRoot . '/storage/uploads/' . $relativePath;
        $storageRoot = realpath($projectRoot . '/storage');
        $realFile = realpath($fullPath);
        if ($storageRoot === false || $realFile === false || !str_starts_with($realFile, $storageRoot) || !is_file($realFile)) {
            http_response_code(404);
            return;
        }
        $mime = mime_content_type($realFile) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realFile));
        readfile($realFile);
        exit;
    }

    private function getUserName(): string
    {
        $pdo = \App\Database\Database::pdo();
        $stmt = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::userId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        return $name !== '' ? $name : 'Usuario';
    }

    private function getShopName(): string
    {
        $pdo = \App\Database\Database::pdo();
        $stmt = $pdo->prepare('SELECT name FROM shops WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => Auth::shopId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (string) ($row['name'] ?? '');
    }
}
