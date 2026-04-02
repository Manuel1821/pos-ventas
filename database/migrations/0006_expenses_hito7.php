<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($dbName === '') {
        return;
    }

    $columns = [
        'reference' => 'ALTER TABLE expenses ADD COLUMN reference VARCHAR(120) NULL AFTER supplier_name',
        'status' => 'ALTER TABLE expenses ADD COLUMN status ENUM("ACTIVE","CANCELLED") NOT NULL DEFAULT "ACTIVE" AFTER notes',
    ];
    foreach ($columns as $columnName => $sql) {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :db_name
               AND TABLE_NAME = "expenses"
               AND COLUMN_NAME = :column_name
             LIMIT 1'
        );
        $stmt->execute(['db_name' => $dbName, 'column_name' => $columnName]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec($sql);
        }
    }

    $indexesExpenseCategories = [
        'idx_expense_categories_shop_id' => 'CREATE INDEX idx_expense_categories_shop_id ON expense_categories (shop_id)',
    ];
    foreach ($indexesExpenseCategories as $indexName => $sql) {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = :db_name
               AND TABLE_NAME = "expense_categories"
               AND INDEX_NAME = :index_name
             LIMIT 1'
        );
        $stmt->execute(['db_name' => $dbName, 'index_name' => $indexName]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec($sql);
        }
    }

    $indexesExpenses = [
        'idx_expenses_shop_occurred_at' => 'CREATE INDEX idx_expenses_shop_occurred_at ON expenses (shop_id, occurred_at)',
        'idx_expenses_shop_status' => 'CREATE INDEX idx_expenses_shop_status ON expenses (shop_id, status)',
        'idx_expenses_shop_payment' => 'CREATE INDEX idx_expenses_shop_payment ON expenses (shop_id, payment_method)',
        'idx_expenses_shop_supplier' => 'CREATE INDEX idx_expenses_shop_supplier ON expenses (shop_id, supplier_name(100))',
    ];
    foreach ($indexesExpenses as $indexName => $sql) {
        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = :db_name
               AND TABLE_NAME = "expenses"
               AND INDEX_NAME = :index_name
             LIMIT 1'
        );
        $stmt->execute(['db_name' => $dbName, 'index_name' => $indexName]);
        if (!$stmt->fetchColumn()) {
            $pdo->exec($sql);
        }
    }
};
