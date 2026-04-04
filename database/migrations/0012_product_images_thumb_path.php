<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($dbName === '') {
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT 1
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db_name
           AND TABLE_NAME = "product_images"
           AND COLUMN_NAME = "thumb_path"
         LIMIT 1'
    );
    $stmt->execute(['db_name' => $dbName]);
    if ($stmt->fetchColumn()) {
        return;
    }

    $pdo->exec(
        'ALTER TABLE product_images ADD COLUMN thumb_path VARCHAR(255) NULL DEFAULT NULL AFTER path'
    );
};
