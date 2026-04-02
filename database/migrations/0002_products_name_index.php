<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    try {
        $pdo->exec('CREATE INDEX idx_products_name ON products (name(100))');
    } catch (Throwable $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }
};
