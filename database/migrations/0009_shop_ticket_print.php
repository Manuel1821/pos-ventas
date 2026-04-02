<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $columns = [
        'ticket_paper_width_mm' => 'ALTER TABLE shops ADD COLUMN ticket_paper_width_mm TINYINT UNSIGNED NOT NULL DEFAULT 80',
        'ticket_font_preset' => 'ALTER TABLE shops ADD COLUMN ticket_font_preset VARCHAR(32) NOT NULL DEFAULT "sans_bold"',
        'ticket_font_size_pt' => 'ALTER TABLE shops ADD COLUMN ticket_font_size_pt DECIMAL(4,1) NOT NULL DEFAULT 13.0',
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
