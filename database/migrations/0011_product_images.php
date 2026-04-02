<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS product_images (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            path VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_primary TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product_images_product (product_id),
            CONSTRAINT fk_product_images_product
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'INSERT INTO product_images (product_id, path, sort_order, is_primary, created_at)
         SELECT id, image_path, 0, 1, created_at
         FROM products
         WHERE image_path IS NOT NULL AND TRIM(image_path) != ""'
    );
};
