<?php

declare(strict_types=1);

return [
    'app' => [
        'debug' => false,
        'timezone' => (string) ($_ENV['APP_TIMEZONE'] ?? 'UTC'),
        'url' => (string) ($_ENV['APP_URL'] ?? ''),
    ],
];

