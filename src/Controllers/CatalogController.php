<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Request;
use App\Core\View;
use App\Core\Redirect;
use App\Database\Database;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ShopRepository;
use App\Support\ProductThumbnailDelivery;

class CatalogController
{
    private ProductRepository $productRepo;
    private CategoryRepository $categoryRepo;
    private ShopRepository $shopRepo;

    public function __construct()
    {
        $this->productRepo = new ProductRepository();
        $this->categoryRepo = new CategoryRepository();
        $this->shopRepo = new ShopRepository();
    }

    /**
     * Catalogo publico: /catalogo/{shopSlug}
     */
    public function index(Request $request): void
    {
        $shopSlug = strtolower(trim((string) ($request->routeParams['shopSlug'] ?? '')));
        $shop = $shopSlug !== '' ? $this->shopRepo->findBySlug($shopSlug) : null;
        if (!$shop) {
            http_response_code(404);
            echo 'Tienda no encontrada.';
            return;
        }

        $shopId = (int) ($shop['id'] ?? 0);
        if ($shopId <= 0) {
            http_response_code(404);
            echo 'Tienda no encontrada.';
            return;
        }

        $page = max(1, (int) ($request->query['pagina'] ?? 1));
        $q = trim((string) ($request->query['q'] ?? ''));
        $categorySlug = strtolower(trim((string) ($request->query['categoria'] ?? '')));

        $category = null;
        $categoryId = null;
        if ($categorySlug !== '') {
            $category = $this->categoryRepo->findBySlugInShop($categorySlug, $shopId);
            if ($category && ($category['status'] ?? '') === 'ACTIVE') {
                $categoryId = (int) ($category['id'] ?? 0);
            } else {
                $category = null;
                $categoryId = null;
            }
        }

        $categories = $this->categoryRepo->listByShop($shopId, true);
        $history = $this->productRepo->listByShop($shopId, $page, $q, $categoryId, 'ACTIVE');

        View::render('public/catalogo/indice', [
            'pageTitle' => 'Catalogo web - ' . (string) ($shop['name'] ?? 'Tienda'),
            'shopName' => (string) ($shop['name'] ?? ''),
            'shopSlug' => $shopSlug,
            'categories' => $categories,
            'categorySlug' => $categorySlug,
            'categoryName' => $category ? (string) ($category['name'] ?? '') : null,
            'q' => $q,
            'products' => $history['items'],
            'total' => $history['total'],
            'page' => $history['page'],
            'total_pages' => $history['total_pages'],
            'flash' => null,
            'userName' => null,
        ]);
    }

    /**
     * /catalogo/{shopSlug}/producto/{productId}
     */
    public function product(Request $request): void
    {
        $shopSlug = strtolower(trim((string) ($request->routeParams['shopSlug'] ?? '')));
        $productId = (int) ($request->routeParams['productId'] ?? 0);

        $shop = $shopSlug !== '' ? $this->shopRepo->findBySlug($shopSlug) : null;
        if (!$shop || $productId <= 0) {
            http_response_code(404);
            echo 'Producto no encontrado.';
            return;
        }

        $shopId = (int) ($shop['id'] ?? 0);
        $product = $this->productRepo->findById($productId, $shopId);
        if (!$product || ($product['status'] ?? '') !== 'ACTIVE') {
            http_response_code(404);
            echo 'Producto no encontrado.';
            return;
        }

        $productImages = $this->productRepo->listImagesByProductId($productId, $shopId);

        View::render('public/catalogo/detalle', [
            'pageTitle' => 'Producto - ' . (string) ($product['name'] ?? ''),
            'shopName' => (string) ($shop['name'] ?? ''),
            'shopSlug' => $shopSlug,
            'product' => $product,
            'productImages' => $productImages,
            'categories' => [],
            'flash' => null,
            'userName' => null,
        ]);
    }

    /**
     * Imagen publica: /catalogo/{shopSlug}/producto/{productId}/imagen
     */
    public function image(Request $request): void
    {
        $shopSlug = strtolower(trim((string) ($request->routeParams['shopSlug'] ?? '')));
        $productId = (int) ($request->routeParams['productId'] ?? 0);

        $shop = $shopSlug !== '' ? $this->shopRepo->findBySlug($shopSlug) : null;
        if (!$shop || $productId <= 0) {
            http_response_code(404);
            return;
        }

        $shopId = (int) ($shop['id'] ?? 0);
        $product = $this->productRepo->findById($productId, $shopId);
        if (!$product || ($product['status'] ?? '') !== 'ACTIVE' || empty($product['image_path'])) {
            http_response_code(404);
            return;
        }

        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 3);
        $fullPath = $projectRoot . '/storage/uploads/' . $product['image_path'];
        $storageRoot = $projectRoot . '/storage';

        $realStorageRoot = realpath($storageRoot);
        $realFilePath = realpath($fullPath);
        if ($realStorageRoot === false || $realFilePath === false || !str_starts_with($realFilePath, $realStorageRoot)) {
            http_response_code(404);
            return;
        }

        if (!is_file($realFilePath)) {
            http_response_code(404);
            return;
        }

        $mime = mime_content_type($realFilePath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realFilePath));
        readfile($realFilePath);
        exit;
    }

    /**
     * Imagen de galería: /catalogo/{shopSlug}/producto/{productId}/imagen/{imageId}
     */
    public function galleryImage(Request $request): void
    {
        $shopSlug = strtolower(trim((string) ($request->routeParams['shopSlug'] ?? '')));
        $productId = (int) ($request->routeParams['productId'] ?? 0);
        $imageId = (int) ($request->routeParams['imageId'] ?? 0);

        $shop = $shopSlug !== '' ? $this->shopRepo->findBySlug($shopSlug) : null;
        if (!$shop || $productId <= 0 || $imageId <= 0) {
            http_response_code(404);
            return;
        }

        $shopId = (int) ($shop['id'] ?? 0);
        $product = $this->productRepo->findById($productId, $shopId);
        if (!$product || ($product['status'] ?? '') !== 'ACTIVE') {
            http_response_code(404);
            return;
        }

        $rel = $this->productRepo->findImagePathForProduct($imageId, $productId, $shopId);
        if ($rel === null || $rel === '') {
            http_response_code(404);
            return;
        }

        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 2);
        $fullPath = $projectRoot . '/storage/uploads/' . $rel;
        $storageRoot = $projectRoot . '/storage';

        $realStorageRoot = realpath($storageRoot);
        $realFilePath = realpath($fullPath);
        if ($realStorageRoot === false || $realFilePath === false || !str_starts_with($realFilePath, $realStorageRoot)) {
            http_response_code(404);
            return;
        }

        if (!is_file($realFilePath)) {
            http_response_code(404);
            return;
        }

        $mime = mime_content_type($realFilePath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($realFilePath));
        readfile($realFilePath);
        exit;
    }

    /**
     * Miniatura de la foto principal (listado catálogo web).
     */
    public function productImageMiniatura(Request $request): void
    {
        $shopSlug = strtolower(trim((string) ($request->routeParams['shopSlug'] ?? '')));
        $productId = (int) ($request->routeParams['productId'] ?? 0);

        $shop = $shopSlug !== '' ? $this->shopRepo->findBySlug($shopSlug) : null;
        if (!$shop || $productId <= 0) {
            http_response_code(404);
            return;
        }

        $shopId = (int) ($shop['id'] ?? 0);
        $product = $this->productRepo->findById($productId, $shopId);
        if (!$product || ($product['status'] ?? '') !== 'ACTIVE') {
            http_response_code(404);
            return;
        }

        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 2);
        (new ProductThumbnailDelivery($this->productRepo, $projectRoot))->sendPrimaryThumbnail($shopId, $productId);
    }

    /**
     * Miniatura de una imagen de galería (vista detalle).
     */
    public function galleryImageMiniatura(Request $request): void
    {
        $shopSlug = strtolower(trim((string) ($request->routeParams['shopSlug'] ?? '')));
        $productId = (int) ($request->routeParams['productId'] ?? 0);
        $imageId = (int) ($request->routeParams['imageId'] ?? 0);

        $shop = $shopSlug !== '' ? $this->shopRepo->findBySlug($shopSlug) : null;
        if (!$shop || $productId <= 0 || $imageId <= 0) {
            http_response_code(404);
            return;
        }

        $shopId = (int) ($shop['id'] ?? 0);
        $product = $this->productRepo->findById($productId, $shopId);
        if (!$product || ($product['status'] ?? '') !== 'ACTIVE') {
            http_response_code(404);
            return;
        }

        $projectRoot = $GLOBALS['app_base_path'] ?? dirname(__DIR__, 2);
        (new ProductThumbnailDelivery($this->productRepo, $projectRoot))->sendGalleryThumbnail($shopId, $productId, $imageId);
    }
}

