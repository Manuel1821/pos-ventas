-- Hito 3: Añadir columnas a la tabla customers
-- Ejecuta este archivo UNA vez en phpMyAdmin (o consola MySQL) sobre tu base de datos.
-- Si alguna sentencia dice "Duplicate column name" o "Duplicate key name", esa parte ya estaba aplicada; puedes ignorarla.

-- Columna dirección
ALTER TABLE customers ADD COLUMN address VARCHAR(500) NULL AFTER email;

-- Columna RFC
ALTER TABLE customers ADD COLUMN rfc VARCHAR(20) NULL AFTER address;

-- Columna observaciones
ALTER TABLE customers ADD COLUMN notes VARCHAR(1000) NULL AFTER rfc;

-- Índice para búsqueda por nombre
CREATE INDEX idx_customers_name ON customers (name(100));
