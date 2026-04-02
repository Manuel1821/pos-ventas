<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Database\Database;
use PDO;

class MigrationRunner
{
    /**
     * @param string $migrationsDir
     */
    public function run(string $migrationsDir): void
    {
        $pdo = Database::pdo();
        $this->ensureMigrationsTable($pdo);

        $applied = $this->getAppliedMigrations($pdo);

        $files = glob($migrationsDir . '/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            if (isset($applied[$name])) {
                continue;
            }

            $migration = require $file;
            if (!is_callable($migration)) {
                throw new \RuntimeException("La migración no es ejecutable: {$name}");
            }

            $migration($pdo);

            $stmt = $pdo->prepare('INSERT INTO schema_migrations (name, applied_at) VALUES (:name, NOW())');
            $stmt->execute(['name' => $name]);
        }
    }

    /**
     * @return array<string, true>
     */
    private function getAppliedMigrations(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT name FROM schema_migrations');
        $applied = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $name = (string) ($row['name'] ?? '');
            if ($name !== '') {
                $applied[$name] = true;
            }
        }
        return $applied;
    }

    private function ensureMigrationsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(190) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}

