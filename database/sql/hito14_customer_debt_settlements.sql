-- =============================================================================
-- Cambios manuales de base de datos — Historial de abonos / liquidación de deuda
-- Migración equivalente: database/migrations/0010_customer_debt_settlements.php
-- =============================================================================
--
-- Qué hace:
--   Crea la tabla customer_debt_settlements: una fila por cada operación de abono
--   o liquidación registrada desde "Deuda del cliente" (importe, forma de pago,
--   observación, fecha).
--
-- Requisitos:
--   Deben existir las tablas: shops, customers, users (InnoDB, mismas convenciones).
--
-- Cómo aplicarlo:
--   1) phpMyAdmin: selecciona tu base de datos → pestaña SQL → pegar todo → Ejecutar.
--   2) Consola MySQL: mysql -u USUARIO -p NOMBRE_BD < hito14_customer_debt_settlements.sql
--   3) O desde el proyecto: php bin/console.php migrate
--      (crea la misma tabla y registra la migración en schema_migrations).
--
-- Si la tabla ya existe, el script no falla (CREATE TABLE IF NOT EXISTS).
-- =============================================================================

CREATE TABLE IF NOT EXISTS customer_debt_settlements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    settlement_type ENUM('ABONO','LIQUIDACION') NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
