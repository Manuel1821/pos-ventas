-- Hito 1: Esquema mínimo del POS SaaS (PHP + MySQL)
-- Ejecuta este script dentro de tu base de datos, por ejemplo:
--   USE totalco1_sistemapos;
--   SOURCE database/sql/hito1_schema.sql;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabla: shops
CREATE TABLE IF NOT EXISTS shops (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shops_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: roles
CREATE TABLE IF NOT EXISTS roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    display_name VARCHAR(100) NULL,
    status ENUM("ACTIVE","INACTIVE") NOT NULL DEFAULT "ACTIVE",
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: users
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    status ENUM("ACTIVE","INACTIVE") NOT NULL DEFAULT "ACTIVE",
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_shop_email (shop_id, email),
    INDEX idx_users_shop_id (shop_id),
    CONSTRAINT fk_users_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: user_roles
CREATE TABLE IF NOT EXISTS user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_roles (user_id, role_id),
    INDEX idx_user_roles_user_id (user_id),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role
        FOREIGN KEY (role_id) REFERENCES roles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: categories
CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    status ENUM("ACTIVE","INACTIVE") NOT NULL DEFAULT "ACTIVE",
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_shop_slug (shop_id, slug),
    INDEX idx_categories_shop_id (shop_id),
    CONSTRAINT fk_categories_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: products
CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    name VARCHAR(200) NOT NULL,
    sku VARCHAR(120) NULL,
    barcode VARCHAR(120) NULL,
    description TEXT NULL,
    unit VARCHAR(60) NOT NULL DEFAULT "Unidad",
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    stock DECIMAL(12,3) NOT NULL DEFAULT 0,
    is_inventory_item TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM("ACTIVE","INACTIVE") NOT NULL DEFAULT "ACTIVE",
    image_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_shop_id (shop_id),
    INDEX idx_products_category_id (category_id),
    INDEX idx_products_sku (sku),
    INDEX idx_products_barcode (barcode),
    CONSTRAINT fk_products_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: customers
CREATE TABLE IF NOT EXISTS customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM("ACTIVE","INACTIVE") NOT NULL DEFAULT "ACTIVE",
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_customers_shop_email (shop_id, email),
    INDEX idx_customers_shop_is_public (shop_id, is_public),
    INDEX idx_customers_shop_id (shop_id),
    INDEX idx_customers_phone (phone),
    INDEX idx_customers_email (email),
    CONSTRAINT fk_customers_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cash_sessions (índice propio antes de FK: evita errno 121 en MariaDB)
CREATE TABLE IF NOT EXISTS cash_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    opened_by BIGINT UNSIGNED NOT NULL,
    status ENUM("OPEN","CLOSED") NOT NULL DEFAULT "OPEN",
    opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cash_sessions_status (status),
    CONSTRAINT fk_cash_sessions_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cash_sessions_opened_by
        FOREIGN KEY (opened_by) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sales
CREATE TABLE IF NOT EXISTS sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    cash_session_id BIGINT UNSIGNED NULL,
    folio BIGINT UNSIGNED NOT NULL,
    status ENUM("OPEN","PAID","CANCELLED","REFUNDED") NOT NULL DEFAULT "PAID",
    occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sales_shop_folio (shop_id, folio),
    INDEX idx_sales_shop_id (shop_id),
    INDEX idx_sales_customer_id (customer_id),
    INDEX idx_sales_status (status),
    CONSTRAINT fk_sales_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sales_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_sales_cash_session
        FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_sales_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sale_items
CREATE TABLE IF NOT EXISTS sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sale_items_sale_id (sale_id),
    INDEX idx_sale_items_product_id (product_id),
    CONSTRAINT fk_sale_items_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_sale_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sale_payments
CREATE TABLE IF NOT EXISTS sale_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    payment_method VARCHAR(60) NOT NULL DEFAULT "EFECTIVO",
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sale_payments_sale_id (sale_id),
    CONSTRAINT fk_sale_payments_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cash_movements
CREATE TABLE IF NOT EXISTS cash_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    cash_session_id BIGINT UNSIGNED NOT NULL,
    type ENUM("IN","OUT") NOT NULL,
    payment_method VARCHAR(60) NOT NULL DEFAULT "EFECTIVO",
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cash_movements_session_id (cash_session_id),
    INDEX idx_cash_movements_type (type),
    CONSTRAINT fk_cash_movements_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cash_movements_session
        FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cash_movements_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: cash_audits
CREATE TABLE IF NOT EXISTS cash_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    cash_session_id BIGINT UNSIGNED NULL,
    actor_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    context JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cash_audits_session_id (cash_session_id),
    CONSTRAINT fk_cash_audits_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_cash_audits_session
        FOREIGN KEY (cash_session_id) REFERENCES cash_sessions(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_cash_audits_actor
        FOREIGN KEY (actor_user_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: expense_categories
CREATE TABLE IF NOT EXISTS expense_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    status ENUM("ACTIVE","INACTIVE") NOT NULL DEFAULT "ACTIVE",
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_expense_categories_shop_slug (shop_id, slug),
    INDEX idx_expense_categories_shop_id (shop_id),
    CONSTRAINT fk_expense_categories_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: expenses
CREATE TABLE IF NOT EXISTS expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    expense_category_id BIGINT UNSIGNED NOT NULL,
    concept VARCHAR(180) NOT NULL,
    amount_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    iva_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(60) NOT NULL DEFAULT "EFECTIVO",
    supplier_name VARCHAR(160) NULL,
    reference VARCHAR(120) NULL,
    occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    status ENUM("ACTIVE","CANCELLED") NOT NULL DEFAULT "ACTIVE",
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expenses_shop_id (shop_id),
    INDEX idx_expenses_category_id (expense_category_id),
    INDEX idx_expenses_shop_occurred_at (shop_id, occurred_at),
    INDEX idx_expenses_shop_status (shop_id, status),
    INDEX idx_expenses_shop_payment (shop_id, payment_method),
    INDEX idx_expenses_shop_supplier (shop_id, supplier_name(100)),
    CONSTRAINT fk_expenses_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_expenses_category
        FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_expenses_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: inventory_movements
CREATE TABLE IF NOT EXISTS inventory_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NULL,
    cash_movement_id BIGINT UNSIGNED NULL,
    type ENUM("IN","OUT") NOT NULL,
    quantity_change DECIMAL(12,3) NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_inventory_movements_shop_id (shop_id),
    INDEX idx_inventory_movements_product_id (product_id),
    CONSTRAINT fk_inventory_movements_shop
        FOREIGN KEY (shop_id) REFERENCES shops(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_inventory_movements_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_inventory_movements_sale
        FOREIGN KEY (sale_id) REFERENCES sales(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_inventory_movements_cash_movement
        FOREIGN KEY (cash_movement_id) REFERENCES cash_movements(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_inventory_movements_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: sale_cancellations (Hito 9)
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

-- Tabla: sale_returns (Hito 9)
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

-- Tabla: sale_return_items (Hito 9)
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

-- Tablas de control del sistema de migraciones/seeders (para que pueda correr el setup)
CREATE TABLE IF NOT EXISTS schema_migrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_seeders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(190) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

