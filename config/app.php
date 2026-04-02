<?php

declare(strict_types=1);

return [
    'app' => [
        'url' => (string) ($_ENV['APP_URL'] ?? ''),
        'timezone' => (string) ($_ENV['APP_TIMEZONE'] ?? 'UTC'),
        'debug' => false,
    ],
    'session' => [
        'name' => 'POSSESSION',
        'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 0),
        'path' => '/',
        'domain' => (string) ($_ENV['SESSION_DOMAIN'] ?? ''),
        'secure' => (bool) ($_ENV['SESSION_SECURE'] ?? false),
        'samesite' => (string) ($_ENV['SESSION_SAMESITE'] ?? 'Lax'),
    ],
    'db' => [
        'host' => (string) ($_ENV['DB_HOST'] ?? '127.0.0.1'),
        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        'name' => (string) ($_ENV['DB_NAME'] ?? ''),
        'user' => (string) ($_ENV['DB_USER'] ?? 'root'),
        'pass' => (string) ($_ENV['DB_PASS'] ?? ''),
    ],
];

