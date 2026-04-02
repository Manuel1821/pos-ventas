-- Referencia por línea de pago (folio tarjeta, transferencia, etc.)
-- Ejecutar manualmente si no usas el runner de migraciones.

ALTER TABLE sale_payments ADD COLUMN reference VARCHAR(120) NULL AFTER amount;
