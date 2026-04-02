<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    try {
        $pdo->exec(
            'ALTER TABLE sale_payments ADD COLUMN reference VARCHAR(120) NULL AFTER amount'
        );
    } catch (Throwable $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
    }
};
