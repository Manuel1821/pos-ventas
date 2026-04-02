<?php

declare(strict_types=1);

namespace App\Core;

class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * @return array{type:string, message:string}|null
     */
    public static function consume(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return is_array($flash) ? $flash : null;
    }
}

