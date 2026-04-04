<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $routeParams = [];

    public function __construct(string $method, string $path, array $query, array $body)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = preg_replace('#/+#', '/', (string) $path) ?: '/';
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        // Permite que el router funcione si el proyecto está montado en subcarpetas.
        // Ej: si el front-controller está en /manuel/index.php, para /manuel/login
        // el path se normaliza a /login.
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName !== '') {
            $baseDir = rtrim(str_replace('\\', '/', (string) dirname($scriptName)), '/');
            if ($baseDir !== '' && $baseDir !== '.') {
                $baseDir = $baseDir === '/' ? '' : $baseDir;
                if ($baseDir !== '') {
                    if (str_starts_with($path, $baseDir . '/')) {
                        $path = substr($path, strlen($baseDir));
                        $path = $path === '' ? '/' : $path;
                    } elseif ($path === $baseDir) {
                        $path = '/';
                    }
                }
            }
        }

        // Subcarpeta: URL tipo /proyecto/setup con front controller en /proyecto/public/index.php
        // (Apache suele tener SCRIPT_NAME .../public/index.php pero REQUEST_URI .../proyecto/setup).
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptName !== '' && str_ends_with($scriptName, '/public/index.php')) {
            $publicDir = rtrim((string) dirname($scriptName), '/');
            $projectRoot = dirname($publicDir);
            if ($publicDir !== '' && $publicDir !== '/' && str_ends_with($publicDir, '/public')
                && $projectRoot !== '' && $projectRoot !== '/' && $projectRoot !== '.') {
                if ($path === $projectRoot) {
                    $path = '/';
                } elseif (str_starts_with($path, $projectRoot . '/') && !str_starts_with($path, $publicDir . '/')) {
                    $path = substr($path, strlen($projectRoot));
                    $path = $path === '' ? '/' : $path;
                    if ($path[0] !== '/') {
                        $path = '/' . $path;
                    }
                }
            }
        }

        $query = $_GET ?? [];
        $body = $_POST ?? [];

        return new self((string) $method, (string) $path, (array) $query, (array) $body);
    }
}

