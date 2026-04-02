<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($dbName === '') {
        return;
    }

    // Columnas para preservar desglose historico por partida.
    $columns = [
        'line_subtotal' => 'ALTER TABLE sale_items ADD COLUMN line_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER tax_percent',
        'discount_amount' => 'ALTER TABLE sale_items ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER line_subtotal',
        'tax_amount' => 'ALTER TABLE sale_items ADD COLUMN tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER discount_amount',
    ];
    foreach ($columns as $columnName => $sql) {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :db_name
               AND TABLE_NAME = "sale_items"
               AND COLUMN_NAME = :column_name
             LIMIT 1'
        );
        $stmt->execute(['db_name' => $dbName, 'column_name' => $columnName]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec($sql);
        }
    }

    // Indices para consultas administrativas del modulo de ventas.
    $indexes = [
        'idx_sales_shop_occurred_at' => 'CREATE INDEX idx_sales_shop_occurred_at ON sales (shop_id, occurred_at)',
        'idx_sales_shop_status' => 'CREATE INDEX idx_sales_shop_status ON sales (shop_id, status)',
        'idx_sales_shop_created_by' => 'CREATE INDEX idx_sales_shop_created_by ON sales (shop_id, created_by)',
    ];
    foreach ($indexes as $indexName => $sql) {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = :db_name
               AND TABLE_NAME = "sales"
               AND INDEX_NAME = :index_name
             LIMIT 1'
        );
        $stmt->execute(['db_name' => $dbName, 'index_name' => $indexName]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec($sql);
        }
    }
};

