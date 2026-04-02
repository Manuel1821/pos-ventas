<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $columns = [
        'responsible_name' => 'ALTER TABLE shops ADD COLUMN responsible_name VARCHAR(200) NULL AFTER slug',
        'rfc' => 'ALTER TABLE shops ADD COLUMN rfc VARCHAR(20) NULL AFTER responsible_name',
        'contact_email' => 'ALTER TABLE shops ADD COLUMN contact_email VARCHAR(190) NULL AFTER rfc',
        'address' => 'ALTER TABLE shops ADD COLUMN address VARCHAR(500) NULL AFTER contact_email',
        'phone' => 'ALTER TABLE shops ADD COLUMN phone VARCHAR(30) NULL AFTER address',
        'business_type' => 'ALTER TABLE shops ADD COLUMN business_type VARCHAR(80) NULL AFTER phone',
        'updated_at' => 'ALTER TABLE shops ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    ];
    foreach ($columns as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }
    }
};
