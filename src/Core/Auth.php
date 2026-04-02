<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['auth']['user_id']) && is_numeric($_SESSION['auth']['user_id']);
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['auth']['user_id']) ? (int) $_SESSION['auth']['user_id'] : null;
    }

    public static function shopId(): ?int
    {
        return isset($_SESSION['auth']['shop_id']) ? (int) $_SESSION['auth']['shop_id'] : null;
    }

    /**
     * @return string[]
     */
    public static function roles(): array
    {
        $roles = $_SESSION['auth']['roles'] ?? [];
        if (!is_array($roles)) {
            return [];
        }
        return array_values(array_filter($roles, fn($r) => is_string($r)));
    }

    /**
     * @param string[] $allowed
     */
    public static function hasAnyRole(array $allowed): bool
    {
        $allowed = array_values(array_filter($allowed, fn($r) => is_string($r) && $r !== ''));
        if ($allowed === []) {
            return true;
        }
        $userRoles = self::roles();
        foreach ($userRoles as $role) {
            if (in_array($role, $allowed, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array{user_id:int, shop_id:int, roles:string[]} $authData
     */
    public static function login(array $authData): void
    {
        $_SESSION['auth'] = $authData;
    }

    public static function logout(): void
    {
        unset($_SESSION['auth']);
    }
}

