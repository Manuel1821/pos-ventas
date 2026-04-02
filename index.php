<?php

declare(strict_types=1);

// Front-controller para cuando el proyecto se monta directamente en una subcarpeta.
// En cPanel (Apache/LiteSpeed) normalmente se usa con `.htaccess`.

require_once __DIR__ . '/bootstrap/app.php';

use App\Core\Request;
use App\Core\Router;

$router = new Router();
require __DIR__ . '/routes/web.php';

$request = Request::fromGlobals();
$router->dispatch($request);

