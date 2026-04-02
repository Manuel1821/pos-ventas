<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS customer_debt_settlements (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NOT NULL,
            settlement_type ENUM("ABONO","LIQUIDACION") NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            payment_method VARCHAR(60) NOT NULL,
            observaciones VARCHAR(500) NULL,
            created_by BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cds_shop_customer (shop_id, customer_id),
            INDEX idx_cds_created (created_at),
            CONSTRAINT fk_cds_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
            CONSTRAINT fk_cds_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
            CONSTRAINT fk_cds_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
