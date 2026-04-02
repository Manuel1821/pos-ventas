-- Hito 6: ajustes de base de datos para modulo de ventas.
-- Ejecutar manualmente en la base activa.

ALTER TABLE sale_items
    ADD COLUMN line_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER tax_percent,
    ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER line_subtotal,
    ADD COLUMN tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER discount_amount;

CREATE INDEX idx_sales_shop_occurred_at ON sales (shop_id, occurred_at);
CREATE INDEX idx_sales_shop_status ON sales (shop_id, status);
CREATE INDEX idx_sales_shop_created_by ON sales (shop_id, created_by);

