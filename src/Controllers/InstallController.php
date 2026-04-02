<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Flash;
use App\Core\Redirect;
use App\Core\Request;
use App\Core\View;
use App\Database\Database;
use App\Database\Migrations\MigrationRunner;
use App\Database\Seeders\SeederRunner;
use Throwable;

class InstallController
{
    public function index(Request $request): void
    {
        $status = $this->getStatus();

        View::render('install/index', [
            'flash' => Flash::consume(),
            'status' => $status,
        ]);
    }

    public function run(Request $request): void
    {
        $confirm = (string) ($request->body['confirm'] ?? '');
        if (trim($confirm) !== 'SI') {
            Flash::set('warning', 'Confirmación requerida. Escribe "SI" para continuar.');
            Redirect::to('/setup');
        }

        try {
            $migrationsDir = $GLOBALS['app_base_path'] . '/database/migrations';
            $seedersDir = $GLOBALS['app_base_path'] . '/database/seeders';

            (new MigrationRunner())->run($migrationsDir);
            (new SeederRunner())->run($seedersDir);

            Flash::set('success', 'Migraciones y seeders aplicados correctamente.');
            Redirect::to('/login');
        } catch (Throwable $e) {
            Flash::set('danger', 'Error al aplicar migraciones: ' . $e->getMessage());
            Redirect::to('/setup');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getStatus(): array
    {
        $debug = (bool) ($GLOBALS['config']['app']['debug'] ?? false);

        $result = [
            'debug' => $debug,
            'db' => [
                'connected' => false,
                'error' => null,
                'db_name' => (string) (($GLOBALS['config']['db'] ?? [])['name'] ?? ''),
            ],
            'installed' => false,
        ];

        // Importante: NO bloquear la carga inicial si MySQL tarda en responder.
        // La conexión se valida cuando el usuario presiona "Aplicar cambios".

        return $result;
    }
}

