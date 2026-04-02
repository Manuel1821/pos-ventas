<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Database\Database;
use PDO;

class SeederRunner
{
    public function run(string $seedersDir): void
    {
        $pdo = Database::pdo();
        $this->ensureSeedersTable($pdo);

        $applied = $this->getAppliedSeeders($pdo);
        $files = glob($seedersDir . '/*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            if (isset($applied[$name])) {
                continue;
            }

            $seeder = require $file;
            if (!is_callable($seeder)) {
                throw new \RuntimeException("El seeder no es ejecutable: {$name}");
            }

            $seeder($pdo);

            $stmt = $pdo->prepare('INSERT INTO schema_seeders (name, applied_at) VALUES (:name, NOW())');
            $stmt->execute(['name' => $name]);
        }
    }

    /**
     * @return array<string, true>
     */
    private function getAppliedSeeders(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT name FROM schema_seeders');
        $applied = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $name = (string) ($row['name'] ?? '');
            if ($name !== '') {
                $applied[$name] = true;
            }
        }
        return $applied;
    }

    private function ensureSeedersTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_seeders (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(190) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}

