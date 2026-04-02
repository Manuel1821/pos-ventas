<?php

declare(strict_types=1);

/**
 * Autocargador simple para clases bajo el namespace `App\`.
 * Evita requerir Composer para el MVP del Hito 1.
 */
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

