<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $columns = [
        'quotation_print_paper' => 'ALTER TABLE shops ADD COLUMN quotation_print_paper VARCHAR(16) NOT NULL DEFAULT "letter"',
        'quotation_print_margin_mm' => 'ALTER TABLE shops ADD COLUMN quotation_print_margin_mm TINYINT UNSIGNED NOT NULL DEFAULT 10',
        'quotation_print_scale_pct' => 'ALTER TABLE shops ADD COLUMN quotation_print_scale_pct SMALLINT UNSIGNED NOT NULL DEFAULT 100',
        'quotation_print_show_sku' => 'ALTER TABLE shops ADD COLUMN quotation_print_show_sku TINYINT(1) NOT NULL DEFAULT 1',
        'quotation_print_show_tax_col' => 'ALTER TABLE shops ADD COLUMN quotation_print_show_tax_col TINYINT(1) NOT NULL DEFAULT 1',
        'quotation_print_show_signatures' => 'ALTER TABLE shops ADD COLUMN quotation_print_show_signatures TINYINT(1) NOT NULL DEFAULT 1',
        'quotation_print_footer_note' => 'ALTER TABLE shops ADD COLUMN quotation_print_footer_note VARCHAR(600) NULL DEFAULT NULL',
    ];
    foreach ($columns as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }
    }
};
