-- Hito 16: Lotes y caducidades (trazabilidad por producto)
-- Ejecuta en tu base de datos, por ejemplo:
--   USE tu_base_datos;
--   SOURCE database/sql/hito16_lotes_caducidades.sql;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    lot_code VARCHAR(64) NOT NULL,
    expiry_date DATE NULL DEFAULT NULL,
    quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_batches_shop_product_lot (shop_id, product_id, lot_code),
    INDEX idx_inventory_batches_shop (shop_id),
    INDEX idx_inventory_batches_product (product_id),
    INDEX idx_inventory_batches_expiry (expiry_date),
    CONSTRAINT fk_inventory_batches_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_inventory_batches_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
