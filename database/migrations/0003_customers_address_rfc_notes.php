<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    // Añadir columnas sugeridas por el Hito 3: dirección, RFC, observaciones
    $columns = [
        'address' => 'ALTER TABLE customers ADD COLUMN address VARCHAR(500) NULL AFTER email',
        'rfc' => 'ALTER TABLE customers ADD COLUMN rfc VARCHAR(20) NULL AFTER address',
        'notes' => 'ALTER TABLE customers ADD COLUMN notes VARCHAR(1000) NULL AFTER rfc',
    ];
    foreach ($columns as $name => $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }
    }
    // Índice para búsqueda por nombre
    try {
        $pdo->exec('CREATE INDEX idx_customers_name ON customers (name(100))');
    } catch (Throwable $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }
};
