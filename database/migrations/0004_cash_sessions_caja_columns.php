<?php

declare(strict_types=1);

use PDO;

return function (PDO $pdo): void {
    $alter = [
        'initial_amount'   => 'ALTER TABLE cash_sessions ADD COLUMN initial_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER status',
        'expected_amount'  => 'ALTER TABLE cash_sessions ADD COLUMN expected_amount DECIMAL(12,2) NULL AFTER closed_at',
        'counted_amount'   => 'ALTER TABLE cash_sessions ADD COLUMN counted_amount DECIMAL(12,2) NULL AFTER expected_amount',
        'difference'       => 'ALTER TABLE cash_sessions ADD COLUMN difference DECIMAL(12,2) NULL AFTER counted_amount',
        'closed_by'        => 'ALTER TABLE cash_sessions ADD COLUMN closed_by BIGINT UNSIGNED NULL AFTER difference',
        'observations'     => 'ALTER TABLE cash_sessions ADD COLUMN observations VARCHAR(500) NULL AFTER closed_by',
    ];
    foreach ($alter as $name => $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
        }
    }
    // FK para closed_by (opcional; si la tabla ya tiene datos, puede fallar por NULL)
    try {
        $pdo->exec('ALTER TABLE cash_sessions ADD CONSTRAINT fk_cash_sessions_closed_by FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL');
    } catch (Throwable $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false && strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
        }
    }
};
