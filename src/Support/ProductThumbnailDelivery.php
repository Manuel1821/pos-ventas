<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\ProductRepository;

/**
 * Sirve miniaturas JPEG (generación diferida si no existe).
 */
final class ProductThumbnailDelivery
{
    public function __construct(
        private ProductRepository $productRepo,
        private string $projectRoot,
    ) {
    }

    public function sendGalleryThumbnail(int $shopId, int $productId, int $imageId): void
    {
        $row = $this->productRepo->findImageRowWithThumb($imageId, $productId, $shopId);
        if (!$row) {
            http_response_code(404);
            return;
        }
        $fullRel = (string) ($row['path'] ?? '');
        if ($fullRel === '') {
            http_response_code(404);
            return;
        }
        $thumbRelDb = isset($row['thumb_path']) ? (string) $row['thumb_path'] : '';
        $thumbRel = $this->ensureThumbnailFile((int) ($row['id'] ?? 0), $productId, $fullRel, $thumbRelDb);
        if ($thumbRel === null) {
            $this->outputOriginalFile($fullRel);
            return;
        }
        $this->outputJpegFile($thumbRel);
    }

    /**
     * Miniatura de la imagen principal del producto (POS, listados, catálogo).
     */
    public function sendPrimaryThumbnail(int $shopId, int $productId): void
    {
        $primary = $this->productRepo->findPrimaryImageRow($productId, $shopId);
        if ($primary) {
            $imageId = (int) ($primary['id'] ?? 0);
            $fullRel = (string) ($primary['path'] ?? '');
            $thumbRelDb = isset($primary['thumb_path']) ? (string) $primary['thumb_path'] : '';
            if ($fullRel !== '' && $imageId > 0) {
                $thumbRel = $this->ensureThumbnailFile($imageId, $productId, $fullRel, $thumbRelDb);
                if ($thumbRel !== null) {
                    $this->outputJpegFile($thumbRel);
                    return;
                }
                $this->outputOriginalFile($fullRel);
                return;
            }
        }

        $product = $this->productRepo->findById($productId, $shopId);
        if (!$product) {
            http_response_code(404);
            return;
        }
        $fullRel = (string) ($product['image_path'] ?? '');
        if ($fullRel === '') {
            http_response_code(404);
            return;
        }

        $cacheRel = 'productos/' . $shopId . '/thumbs/_primary_' . $productId . '.jpg';
        $thumbRel = $this->ensureThumbnailFromFullOnly($fullRel, $cacheRel);
        if ($thumbRel !== null) {
            $this->outputJpegFile($thumbRel);
            return;
        }
        $this->outputOriginalFile($fullRel);
    }

    private function ensureThumbnailFile(int $imageId, int $productId, string $fullRel, string $thumbRelDb): ?string
    {
        $uploads = $this->projectRoot . '/storage/uploads/';
        $fullAbs = $uploads . $fullRel;
        if (!is_file($fullAbs)) {
            return null;
        }

        $suggested = ImageThumbnail::suggestedThumbRelativePath($fullRel);
        $candidates = $thumbRelDb !== '' ? [$thumbRelDb, $suggested] : [$suggested];
        $seen = [];
        foreach ($candidates as $tryRel) {
            if ($tryRel === '' || isset($seen[$tryRel])) {
                continue;
            }
            $seen[$tryRel] = true;
            $tryAbs = $uploads . $tryRel;
            if (is_file($tryAbs)) {
                if ($imageId > 0 && $tryRel !== $thumbRelDb) {
                    $this->productRepo->updateProductImageThumbPath($imageId, $productId, $tryRel);
                }

                return $tryRel;
            }
        }

        if (!ImageThumbnail::isGdAvailable()) {
            return null;
        }

        if (!ImageThumbnail::createJpegThumbnail($fullAbs, $uploads . $suggested, ImageThumbnail::DEFAULT_MAX_EDGE)) {
            return null;
        }
        if ($imageId > 0) {
            $this->productRepo->updateProductImageThumbPath($imageId, $productId, $suggested);
        }

        return $suggested;
    }

    /**
     * Sin fila en product_images: solo archivo en caché por producto.
     */
    private function ensureThumbnailFromFullOnly(string $fullRel, string $cacheRel): ?string
    {
        $uploads = $this->projectRoot . '/storage/uploads/';
        $fullAbs = $uploads . $fullRel;
        $cacheAbs = $uploads . $cacheRel;
        if (is_file($cacheAbs)) {
            return $cacheRel;
        }
        if (!is_file($fullAbs) || !ImageThumbnail::isGdAvailable()) {
            return null;
        }
        if (!ImageThumbnail::createJpegThumbnail($fullAbs, $cacheAbs, ImageThumbnail::DEFAULT_MAX_EDGE)) {
            return null;
        }

        return $cacheRel;
    }

    private function outputJpegFile(string $relativePath): void
    {
        $fullPath = $this->projectRoot . '/storage/uploads/' . $relativePath;
        $storageRoot = realpath($this->projectRoot . '/storage');
        $realFile = realpath($fullPath);
        if ($storageRoot === false || $realFile === false || !str_starts_with($realFile, $storageRoot) || !is_file($realFile)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . (string) filesize($realFile));
        readfile($realFile);
        exit;
    }

    private function outputOriginalFile(string $relativePath): void
    {
        $fullPath = $this->projectRoot . '/storage/uploads/' . $relativePath;
        $storageRoot = realpath($this->projectRoot . '/storage');
        $realFile = realpath($fullPath);
        if ($storageRoot === false || $realFile === false || !str_starts_with($realFile, $storageRoot) || !is_file($realFile)) {
            http_response_code(404);
            return;
        }
        $mime = mime_content_type($realFile) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . (string) filesize($realFile));
        readfile($realFile);
        exit;
    }
}
