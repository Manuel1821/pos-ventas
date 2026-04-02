<?php

declare(strict_types=1);

namespace App\Validation;

class ShopUserValidator
{
    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    public static function validateCreate(array $data): array
    {
        return self::validateInternal($data, true);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    public static function validateUpdate(array $data): array
    {
        return self::validateInternal($data, false);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private static function validateInternal(array $data, bool $requirePassword): array
    {
        $errors = [];

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            $errors[] = 'El correo es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo no es válido.';
        } elseif (mb_strlen($email) > 190) {
            $errors[] = 'El correo no puede superar 190 caracteres.';
        }

        $fn = trim((string) ($data['first_name'] ?? ''));
        if ($fn === '') {
            $errors[] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($fn) > 100) {
            $errors[] = 'El nombre no puede superar 100 caracteres.';
        }

        $ln = trim((string) ($data['last_name'] ?? ''));
        if ($ln === '') {
            $errors[] = 'El apellido es obligatorio.';
        } elseif (mb_strlen($ln) > 100) {
            $errors[] = 'El apellido no puede superar 100 caracteres.';
        }

        $pwd = (string) ($data['password'] ?? '');
        if ($requirePassword) {
            if (mb_strlen($pwd) < 8) {
                $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
            }
        } elseif ($pwd !== '' && mb_strlen($pwd) < 8) {
            $errors[] = 'La nueva contraseña debe tener al menos 8 caracteres.';
        }

        $role = trim((string) ($data['role'] ?? ''));
        if (!in_array($role, ['admin', 'cajero'], true)) {
            $errors[] = 'Selecciona un rol válido (Administrador o Cajero).';
        }

        return $errors;
    }
}
