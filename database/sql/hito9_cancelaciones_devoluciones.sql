-- Hito 9: modulo de cancelaciones y devoluciones
-- Ejecutar manualmente en la base activa.

CREATE TABLE IF NOT EXISTS sale_cancellations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(180) NOT NULL,
    notes VARCHAR(255) NULL,
    refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    cash_movement_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sale_cancellations_sale_id (sale_id),
    INDEX idx_sale_cancellations_shop_id (shop_id),
    INDEX idx_sale_cancellations_created_at (created_at),
    CONSTRAINT fk_sale_cancellations_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sale_cancellations_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sale_cancellations_cash_movement
        FOREIGN KEY (cash_movement_id) REFERENCES cash_movements(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_sale_cancellations_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sale_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(180) NOT NULL,
    notes VARCHAR(255) NULL,
    refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    cash_movement_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sale_returns_shop_id (shop_id),
    INDEX idx_sale_returns_sale_id (sale_id),
    INDEX idx_sale_returns_created_at (created_at),
    CONSTRAINT fk_sale_returns_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sale_returns_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sale_returns_cash_movement
        FOREIGN KEY (cash_movement_id) REFERENCES cash_movements(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_sale_returns_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sale_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_return_id BIGINT UNSIGNED NOT NULL,
    sale_item_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    line_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sale_return_items_return_id (sale_return_id),
    INDEX idx_sale_return_items_sale_item_id (sale_item_id),
    INDEX idx_sale_return_items_product_id (product_id),
    CONSTRAINT fk_sale_return_items_return
        FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sale_return_items_sale_item
        FOREIGN KEY (sale_item_id) REFERENCES sale_items(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_sale_return_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE inventory_movements
    ADD INDEX idx_inventory_movements_sale_id (sale_id);

