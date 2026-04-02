-- Hito 7: ajustes para el módulo de gastos (bases ya creadas en Hito 1)
-- Ejecuta en tu base de datos, por ejemplo: USE totalco1_sistemapos;
--
-- Si un paso falla porque el objeto ya existe, omite solo ese paso y continúa.

SET NAMES utf8mb4;

-- 1) Índice por tienda en categorías de gasto
ALTER TABLE expense_categories
    ADD INDEX idx_expense_categories_shop_id (shop_id);

-- 2) Columnas nuevas en gastos
ALTER TABLE expenses
    ADD COLUMN reference VARCHAR(120) NULL AFTER supplier_name;

ALTER TABLE expenses
    ADD COLUMN status ENUM('ACTIVE','CANCELLED') NOT NULL DEFAULT 'ACTIVE' AFTER notes;

-- 3) Índices para listados y filtros (cada uno por separado)
ALTER TABLE expenses ADD INDEX idx_expenses_shop_occurred_at (shop_id, occurred_at);
ALTER TABLE expenses ADD INDEX idx_expenses_shop_status (shop_id, status);
ALTER TABLE expenses ADD INDEX idx_expenses_shop_payment (shop_id, payment_method);
ALTER TABLE expenses ADD INDEX idx_expenses_shop_supplier (shop_id, supplier_name(100));
