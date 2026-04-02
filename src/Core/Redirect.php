<?php

declare(strict_types=1);

namespace App\Core;

class Redirect
{
    public static function to(string $path, int $status = 302): never
    {
        // Para subcarpetas: infiere el prefijo real usando SCRIPT_NAME (front-controller).
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('\\', '/', (string) dirname($scriptName)), '/');
        if ($basePath === '.' || $basePath === '\\' || $basePath === '/' || $basePath === '') {
            $basePath = '';
        }

        $baseUrl = $host !== '' ? ($scheme . '://' . $host . $basePath) : $basePath;

        $target = $baseUrl . $path;

        header('Location: ' . $target, true, $status);
        exit;
    }
}

