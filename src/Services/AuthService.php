<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Database\Database;
use PDO;

class AuthService
{
    public function login(string $email, string $password): bool
    {
        $email = trim(mb_strtolower($email));
        if ($email === '' || $password === '') {
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            'SELECT id, shop_id, password_hash, status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || ($user['status'] ?? '') !== 'ACTIVE') {
            return false;
        }

        $hash = (string) ($user['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return false;
        }

        $roles = $this->fetchRoles((int) $user['id']);
        if ($roles === []) {
            return false;
        }

        Auth::login([
            'user_id' => (int) $user['id'],
            'shop_id' => (int) $user['shop_id'],
            'roles' => $roles,
        ]);

        return true;
    }

    /**
     * @return string[]
     */
    private function fetchRoles(int $userId): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare(
            'SELECT r.name
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);

        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $name = (string) ($row['name'] ?? '');
            if ($name !== '') {
                $roles[] = $name;
            }
        }

        return $roles;
    }
}

