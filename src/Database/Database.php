<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $db = $GLOBALS['config']['db'] ?? [];
        $host = (string) ($db['host'] ?? '127.0.0.1');
        $name = (string) ($db['name'] ?? '');
        $user = (string) ($db['user'] ?? 'root');
        $pass = (string) ($db['pass'] ?? '');
        $port = (int) ($db['port'] ?? 3306);

        if ($name === '') {
            throw new \RuntimeException('Falta DB_NAME en configuración/.env');
        }

        // connect_timeout evita que una conexión fallida deje la app “colgada”.
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4;connect_timeout=3',
            $host,
            $port,
            $name
        );
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            // Falla rápido si el handshake de conexión se tarda.
            PDO::ATTR_TIMEOUT => 3,
        ]);

        $pdo->exec('SET NAMES utf8mb4');

        self::$pdo = $pdo;
        return self::$pdo;
    }

    /**
     * Ejecuta una operación transaccional (útil para ventas, caja e inventario).
     */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Helper para consultar una sola fila.
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * Helper para consultar un conjunto de filas.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    /**
     * Helper para ejecutar INSERT/UPDATE/DELETE.
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}

