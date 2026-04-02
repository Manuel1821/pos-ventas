-- Cotizaciones (listado + PDF vía impresión del navegador)
-- Ejecutar después de las migraciones previas, o usar: php bin/console.php migrate

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS quotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    folio BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    seller_id BIGINT UNSIGNED NULL,
    status ENUM('OPEN','SOLD') NOT NULL DEFAULT 'OPEN',
    valid_from DATE NOT NULL,
    valid_to DATE NULL DEFAULT NULL,
    delivery_address VARCHAR(500) NULL DEFAULT NULL,
    note_to_customer TEXT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_quotations_shop_folio (shop_id, folio),
    INDEX idx_quotations_shop_status (shop_id, status),
    INDEX idx_quotations_customer (customer_id),
    CONSTRAINT fk_quotations_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_quotations_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_quotations_seller
        FOREIGN KEY (seller_id) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_quotations_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(255) NOT NULL DEFAULT '',
    quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    line_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_quotation_items_quotation (quotation_id),
    INDEX idx_quotation_items_product (product_id),
    CONSTRAINT fk_quotation_items_quotation
        FOREIGN KEY (quotation_id) REFERENCES quotations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_quotation_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
