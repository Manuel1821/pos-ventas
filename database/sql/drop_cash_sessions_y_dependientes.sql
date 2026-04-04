-- =============================================================================
-- Borra tablas que impiden DROP de cash_sessions (error #1451).
-- Ejecuta TODO el bloque de una vez en phpMyAdmin (pestaña SQL) y pulsa Continuar.
-- Si alguna tabla no existe en tu BD, comenta esa línea o ignora el aviso.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Hijos de sales / movimientos (orden seguro para esquema hito1 + módulos típicos)
DROP TABLE IF EXISTS sale_return_items;
DROP TABLE IF EXISTS sale_returns;
DROP TABLE IF EXISTS sale_cancellations;

DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sale_payments;

DROP TABLE IF EXISTS inventory_movements;

DROP TABLE IF EXISTS sales;

DROP TABLE IF EXISTS cash_movements;
DROP TABLE IF EXISTS cash_audits;

DROP TABLE IF EXISTS cash_sessions;

SET FOREIGN_KEY_CHECKS = 1;

-- Después: sube el 0001_init_schema.php corregido y ejecuta /setup con SI,
-- o crea solo cash_sessions con el CREATE de hito1_schema.sql / migración.
