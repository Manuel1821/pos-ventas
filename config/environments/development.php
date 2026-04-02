<?php

declare(strict_types=1);

return [
    'app' => [
        'debug' => true,
        'timezone' => (string) ($_ENV['APP_TIMEZONE'] ?? 'America/Mexico_City'),
        'url' => (string) ($_ENV['APP_URL'] ?? ''),
    ],
];

