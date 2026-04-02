<?php

declare(strict_types=1);

/**
 * Router para el servidor embebido de PHP.
 *
 * Sirve archivos estáticos si existen, y para el resto envía la petición a `public/index.php`
 * (front controller) para que el sistema use `Router` + rutas definidas en `routes/web.php`.
 */

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$path = '/' . ltrim($path, '/');

$filePath = __DIR__ . $path;

if (is_file($filePath)) {
    return false; // deja que el servidor sirva el archivo
}

require __DIR__ . '/index.php';

