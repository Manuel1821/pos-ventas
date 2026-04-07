<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Genera JPEG reducido para listados y miniaturas (requiere extensión GD).
 */
final class ImageThumbnail
{
    public const DEFAULT_MAX_EDGE = 240;

    public static function suggestedThumbRelativePath(string $mainRelativePath): string
    {
        $dir = dirname($mainRelativePath);
        $base = pathinfo($mainRelativePath, PATHINFO_FILENAME);

        return $dir . '/thumbs/' . $base . '_thumb.jpg';
    }

    public static function isGdAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }

    /**
     * Crea un JPEG con el lado mayor como máximo $maxEdge px.
     */
    public static function createJpegThumbnail(string $sourceAbsolute, string $destinationAbsolute, int $maxEdge = self::DEFAULT_MAX_EDGE): bool
    {
        return self::createJpegResized($sourceAbsolute, $destinationAbsolute, $maxEdge, 82);
    }

    /**
     * Crea un JPEG redimensionado y comprimido.
     *
     * @param int $quality 1..100 (recomendado 70..85)
     */
    public static function createJpegResized(string $sourceAbsolute, string $destinationAbsolute, int $maxEdge, int $quality): bool
    {
        if (!is_file($sourceAbsolute) || !self::isGdAvailable()) {
            return false;
        }

        $quality = max(1, min(100, $quality));

        $info = @getimagesize($sourceAbsolute);
        if ($info === false) {
            return false;
        }

        $w = (int) ($info[0] ?? 0);
        $h = (int) ($info[1] ?? 0);
        if ($w < 1 || $h < 1) {
            return false;
        }

        $mime = (string) ($info['mime'] ?? '');
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourceAbsolute),
            'image/png' => @imagecreatefrompng($sourceAbsolute),
            'image/gif' => @imagecreatefromgif($sourceAbsolute),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourceAbsolute) : false,
            default => false,
        };

        if ($src === false) {
            return false;
        }

        $ratio = min($maxEdge / $w, $maxEdge / $h, 1.0);
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = imagecreatetruecolor($nw, $nh);
        if ($dst === false) {
            imagedestroy($src);
            return false;
        }

        // Fondo blanco para evitar transparencia negra al convertir PNG/GIF/WebP a JPEG.
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        $dir = dirname($destinationAbsolute);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                imagedestroy($dst);
                return false;
            }
        }

        $ok = imagejpeg($dst, $destinationAbsolute, $quality);
        imagedestroy($dst);

        return $ok;
    }
}
