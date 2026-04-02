<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Redirect;
use App\Core\Request;
use App\Core\Router;

$router = new Router();
require __DIR__ . '/../routes/web.php';

$request = Request::fromGlobals();

$router->dispatch($request);

