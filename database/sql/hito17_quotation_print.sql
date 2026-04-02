-- Impresión avanzada de cotizaciones (PDF vía navegador)
-- Ejecutar manualmente si no usas: php bin/console.php migrate

SET NAMES utf8mb4;

ALTER TABLE shops ADD COLUMN quotation_print_paper VARCHAR(16) NOT NULL DEFAULT 'letter';
ALTER TABLE shops ADD COLUMN quotation_print_margin_mm TINYINT UNSIGNED NOT NULL DEFAULT 10;
ALTER TABLE shops ADD COLUMN quotation_print_scale_pct SMALLINT UNSIGNED NOT NULL DEFAULT 100;
ALTER TABLE shops ADD COLUMN quotation_print_show_sku TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE shops ADD COLUMN quotation_print_show_tax_col TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE shops ADD COLUMN quotation_print_show_signatures TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE shops ADD COLUMN quotation_print_footer_note VARCHAR(600) NULL DEFAULT NULL;
