-- Configuración de impresión de tickets (ancho papel, fuente, tamaño)
-- Ejecutar manualmente si no usas el runner de migraciones.

ALTER TABLE shops ADD COLUMN ticket_paper_width_mm TINYINT UNSIGNED NOT NULL DEFAULT 80;
ALTER TABLE shops ADD COLUMN ticket_font_preset VARCHAR(32) NOT NULL DEFAULT 'sans_bold';
ALTER TABLE shops ADD COLUMN ticket_font_size_pt DECIMAL(4,1) NOT NULL DEFAULT 13.0;
