-- Hito 11: datos fiscales y de contacto en `shops` (si ya tienes la BD sin migración 0007).
-- Ejecutar manualmente o preferir /setup para aplicar migraciones.

SET NAMES utf8mb4;

ALTER TABLE shops ADD COLUMN responsible_name VARCHAR(200) NULL AFTER slug;
ALTER TABLE shops ADD COLUMN rfc VARCHAR(20) NULL AFTER responsible_name;
ALTER TABLE shops ADD COLUMN contact_email VARCHAR(190) NULL AFTER rfc;
ALTER TABLE shops ADD COLUMN address VARCHAR(500) NULL AFTER contact_email;
ALTER TABLE shops ADD COLUMN phone VARCHAR(30) NULL AFTER address;
ALTER TABLE shops ADD COLUMN business_type VARCHAR(80) NULL AFTER phone;
ALTER TABLE shops ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
