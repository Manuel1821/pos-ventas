<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

use App\Database\Migrations\MigrationRunner;
use App\Database\Seeders\SeederRunner;

function usage(): void
{
    echo "Uso:\n";
    echo "  php bin/console.php migrate\n";
    echo "  php bin/console.php seed\n";
    echo "  php bin/console.php all\n";
}

$command = $argv[1] ?? 'help';
$command = strtolower((string) $command);

$projectRoot = dirname(__DIR__);
$migrationsDir = $projectRoot . '/database/migrations';
$seedersDir = $projectRoot . '/database/seeders';

try {
    if ($command === 'migrate') {
        (new MigrationRunner())->run($migrationsDir);
        echo "Migraciones ejecutadas correctamente.\n";
        exit(0);
    }

    if ($command === 'seed') {
        (new SeederRunner())->run($seedersDir);
        echo "Seeders ejecutados correctamente.\n";
        exit(0);
    }

    if ($command === 'all') {
        (new MigrationRunner())->run($migrationsDir);
        (new SeederRunner())->run($seedersDir);
        echo "Migraciones + seeders ejecutados correctamente.\n";
        exit(0);
    }

    usage();
    exit(1);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

