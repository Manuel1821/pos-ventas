<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;

class UserManagementRepository
{
    /**
     * @return list<array{id:int,email:string,first_name:string,last_name:string,status:string,created_at:string,role_names:?string}>
     */
    public function listByShop(int $shopId): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.status, u.created_at,
                    GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ",") AS role_names
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id AND r.status = "ACTIVE"
             WHERE u.shop_id = :shop_id
             GROUP BY u.id, u.email, u.first_name, u.last_name, u.status, u.created_at
             ORDER BY u.created_at ASC',
            ['shop_id' => $shopId]
        );
    }

    public function findByIdForShop(int $userId, int $shopId): ?array
    {
        return Database::fetch(
            'SELECT id, shop_id, email, first_name, last_name, status, created_at
             FROM users
             WHERE id = :id AND shop_id = :shop_id
             LIMIT 1',
            ['id' => $userId, 'shop_id' => $shopId]
        );
    }

    /**
     * Primer rol del usuario (name), o null.
     */
    public function getPrimaryRoleName(int $userId): ?string
    {
        $row = Database::fetch(
            'SELECT r.name
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id
             ORDER BY r.name ASC
             LIMIT 1',
            ['user_id' => $userId]
        );
        $n = (string) ($row['name'] ?? '');
        return $n !== '' ? $n : null;
    }

    public function getRoleIdByName(string $name): ?int
    {
        $row = Database::fetch(
            'SELECT id FROM roles WHERE name = :name AND status = "ACTIVE" LIMIT 1',
            ['name' => $name]
        );
        if (!$row) {
            return null;
        }
        return (int) $row['id'];
    }

    public function emailExistsForShop(string $email, int $shopId, ?int $excludeUserId = null): bool
    {
        $email = mb_strtolower(trim($email));
        if ($excludeUserId !== null && $excludeUserId > 0) {
            $row = Database::fetch(
                'SELECT id FROM users WHERE shop_id = :shop_id AND email = :email AND id != :exclude LIMIT 1',
                ['shop_id' => $shopId, 'email' => $email, 'exclude' => $excludeUserId]
            );
        } else {
            $row = Database::fetch(
                'SELECT id FROM users WHERE shop_id = :shop_id AND email = :email LIMIT 1',
                ['shop_id' => $shopId, 'email' => $email]
            );
        }
        return $row !== null;
    }

    /**
     * Usuarios activos con rol admin en la tienda.
     */
    public function countActiveAdminsInShop(int $shopId, ?int $excludeUserId = null): int
    {
        if ($excludeUserId !== null && $excludeUserId > 0) {
            $row = Database::fetch(
                'SELECT COUNT(DISTINCT u.id) AS c
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id AND r.name = "admin"
                 WHERE u.shop_id = :shop_id AND u.status = "ACTIVE" AND u.id != :exclude',
                ['shop_id' => $shopId, 'exclude' => $excludeUserId]
            );
        } else {
            $row = Database::fetch(
                'SELECT COUNT(DISTINCT u.id) AS c
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id AND r.name = "admin"
                 WHERE u.shop_id = :shop_id AND u.status = "ACTIVE"',
                ['shop_id' => $shopId]
            );
        }
        return (int) ($row['c'] ?? 0);
    }

    public function create(int $shopId, string $email, string $passwordHash, string $firstName, string $lastName): int
    {
        Database::execute(
            'INSERT INTO users (shop_id, email, password_hash, first_name, last_name, status, created_at)
             VALUES (:shop_id, :email, :password_hash, :first_name, :last_name, "ACTIVE", NOW())',
            [
                'shop_id' => $shopId,
                'email' => mb_strtolower(trim($email)),
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]
        );
        return (int) Database::pdo()->lastInsertId();
    }

    public function update(
        int $userId,
        int $shopId,
        string $email,
        string $firstName,
        string $lastName,
        ?string $passwordHash,
        string $status
    ): bool {
        if ($passwordHash !== null) {
            $n = Database::execute(
                'UPDATE users SET
                    email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    password_hash = :password_hash,
                    status = :status,
                    updated_at = NOW()
                 WHERE id = :id AND shop_id = :shop_id',
                [
                    'id' => $userId,
                    'shop_id' => $shopId,
                    'email' => mb_strtolower(trim($email)),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password_hash' => $passwordHash,
                    'status' => $status,
                ]
            );
        } else {
            $n = Database::execute(
                'UPDATE users SET
                    email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    status = :status,
                    updated_at = NOW()
                 WHERE id = :id AND shop_id = :shop_id',
                [
                    'id' => $userId,
                    'shop_id' => $shopId,
                    'email' => mb_strtolower(trim($email)),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'status' => $status,
                ]
            );
        }
        return $n > 0;
    }

    public function setStatus(int $userId, int $shopId, string $status): bool
    {
        if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            return false;
        }
        $n = Database::execute(
            'UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id AND shop_id = :shop_id',
            ['id' => $userId, 'shop_id' => $shopId, 'status' => $status]
        );
        return $n > 0;
    }

    /**
     * Deja un solo rol al usuario (reemplaza user_roles).
     */
    public function setUserRole(int $userId, string $roleName): void
    {
        $roleId = $this->getRoleIdByName($roleName);
        if ($roleId === null) {
            throw new \RuntimeException('Rol no encontrado: ' . $roleName);
        }
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM user_roles WHERE user_id = :user_id')->execute(['user_id' => $userId]);
        Database::execute(
            'INSERT INTO user_roles (user_id, role_id, created_at) VALUES (:user_id, :role_id, NOW())',
            ['user_id' => $userId, 'role_id' => $roleId]
        );
    }
}
