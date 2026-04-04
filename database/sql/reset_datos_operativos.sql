-- =============================================================================
-- POS: vaciar datos de prueba y dejar la base lista para uso desde cero
-- =============================================================================
-- Usa DELETE en lugar de TRUNCATE: MySQL/MariaDB no permite TRUNCATE en tablas
-- que son padre en una FK (#1701), aunque FOREIGN_KEY_CHECKS = 0.
--
-- 1) Respaldo antes (mysqldump / exportar).
-- 2) No borra schema_migrations (el esquema se mantiene).
-- 3) Vacía schema_seeders para que /setup ejecute de nuevo los seeders.
-- 4) Navegador: /setup → SI → confirmar.
-- 5) Login: ADMIN_EMAIL / ADMIN_PASSWORD en .env
--
-- Si una tabla no existe, comenta las líneas DELETE de ese bloque.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --- Solo si existe hito9 (cancelaciones / devoluciones) ---
-- DELETE FROM sale_return_items;
-- DELETE FROM sale_returns;
-- DELETE FROM sale_cancellations;

-- Hijos antes que padres (apartados)
DELETE FROM layaway_payments;
DELETE FROM layaway_items;
DELETE FROM layaways;

-- Cotizaciones
DELETE FROM quotation_items;
DELETE FROM quotations;

DELETE FROM customer_debt_settlements;

DELETE FROM product_images;
DELETE FROM inventory_batches;

DELETE FROM inventory_movements;
DELETE FROM expenses;
DELETE FROM expense_categories;
DELETE FROM cash_audits;
DELETE FROM cash_movements;
DELETE FROM sale_payments;
DELETE FROM sale_items;
DELETE FROM sales;
DELETE FROM cash_sessions;

DELETE FROM customers;
DELETE FROM products;
DELETE FROM categories;

DELETE FROM user_roles;
DELETE FROM users;
DELETE FROM shops;

DELETE FROM roles;

DELETE FROM schema_seeders;

SET FOREIGN_KEY_CHECKS = 1;

-- Opcional: reiniciar contadores AUTO_INCREMENT (mejor aspecto en IDs nuevos)
ALTER TABLE layaway_payments AUTO_INCREMENT = 1;
ALTER TABLE layaway_items AUTO_INCREMENT = 1;
ALTER TABLE layaways AUTO_INCREMENT = 1;
ALTER TABLE quotation_items AUTO_INCREMENT = 1;
ALTER TABLE quotations AUTO_INCREMENT = 1;
ALTER TABLE customer_debt_settlements AUTO_INCREMENT = 1;
ALTER TABLE product_images AUTO_INCREMENT = 1;
ALTER TABLE inventory_batches AUTO_INCREMENT = 1;
ALTER TABLE inventory_movements AUTO_INCREMENT = 1;
ALTER TABLE expenses AUTO_INCREMENT = 1;
ALTER TABLE expense_categories AUTO_INCREMENT = 1;
ALTER TABLE cash_audits AUTO_INCREMENT = 1;
ALTER TABLE cash_movements AUTO_INCREMENT = 1;
ALTER TABLE sale_payments AUTO_INCREMENT = 1;
ALTER TABLE sale_items AUTO_INCREMENT = 1;
ALTER TABLE sales AUTO_INCREMENT = 1;
ALTER TABLE cash_sessions AUTO_INCREMENT = 1;
ALTER TABLE customers AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE user_roles AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE shops AUTO_INCREMENT = 1;
ALTER TABLE roles AUTO_INCREMENT = 1;
ALTER TABLE schema_seeders AUTO_INCREMENT = 1;

-- Fin: /setup con SI, luego login.
