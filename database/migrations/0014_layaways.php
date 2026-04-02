<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS layaways (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            shop_id BIGINT UNSIGNED NOT NULL,
            folio BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            seller_id BIGINT UNSIGNED NULL,
            status ENUM("OPEN","PAID","CANCELLED","EXPIRED") NOT NULL DEFAULT "OPEN",
            starts_at DATE NOT NULL,
            due_date DATE NULL DEFAULT NULL,
            note_to_customer TEXT NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            down_payment DECIMAL(12,2) NOT NULL DEFAULT 0,
            paid_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_layaways_shop_folio (shop_id, folio),
            INDEX idx_layaways_shop_status (shop_id, status),
            INDEX idx_layaways_customer (customer_id),
            INDEX idx_layaways_due_date (due_date),
            CONSTRAINT fk_layaways_shop
                FOREIGN KEY (shop_id) REFERENCES shops(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_layaways_customer
                FOREIGN KEY (customer_id) REFERENCES customers(id)
                ON DELETE SET NULL,
            CONSTRAINT fk_layaways_seller
                FOREIGN KEY (seller_id) REFERENCES users(id)
                ON DELETE SET NULL,
            CONSTRAINT fk_layaways_created_by
                FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS layaway_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            layaway_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            product_name VARCHAR(255) NOT NULL DEFAULT "",
            quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
            unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
            tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
            line_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_layaway_items_layaway (layaway_id),
            INDEX idx_layaway_items_product (product_id),
            CONSTRAINT fk_layaway_items_layaway
                FOREIGN KEY (layaway_id) REFERENCES layaways(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_layaway_items_product
                FOREIGN KEY (product_id) REFERENCES products(id)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS layaway_payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            layaway_id BIGINT UNSIGNED NOT NULL,
            payment_method VARCHAR(60) NOT NULL DEFAULT "EFECTIVO",
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            reference VARCHAR(120) NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_layaway_payments_layaway (layaway_id),
            CONSTRAINT fk_layaway_payments_layaway
                FOREIGN KEY (layaway_id) REFERENCES layaways(id)
                ON DELETE CASCADE,
            CONSTRAINT fk_layaway_payments_created_by
                FOREIGN KEY (created_by) REFERENCES users(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};

