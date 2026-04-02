<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /**
     * @var array<int, array{method:string,path:string,handler:mixed,options:array,regex:string}>
     */
    private array $routes = [];

    public function add(string $method, string $path, mixed $handler, array $options = []): void
    {
        $method = strtoupper($method);
        $path = '/' . ltrim($path, '/');

        $regex = $this->compilePathToRegex($path);

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'options' => $options,
            'regex' => $regex,
        ];
    }

    public function get(string $path, mixed $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    public function post(string $path, mixed $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            $request->routeParams = $params;

            if (!empty($route['options']['auth']) && $route['options']['auth'] === true) {
                if (!Auth::check()) {
                    Redirect::to('/login');
                }
            }

            if (!empty($route['options']['roles']) && is_array($route['options']['roles'])) {
                $allowed = $route['options']['roles'];
                if (!Auth::hasAnyRole($allowed)) {
                    http_response_code(403);
                    echo 'Acceso denegado';
                    return;
                }
            }

            $handler = $route['handler'];
            $result = $this->callHandler($handler, $request);
            if (is_string($result)) {
                echo $result;
            }
            return;
        }

        http_response_code(404);
        echo 'Ruta no encontrada';
    }

    private function compilePathToRegex(string $path): string
    {
        // Soporta parámetros: /admin/products/{id}
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) {
            $name = $m[1];
            return '(?P<' . $name . '>[^/]+)';
        }, $path);

        return '#^' . $pattern . '$#';
    }

    private function callHandler(mixed $handler, Request $request): mixed
    {
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            $class = $handler[0];
            $method = $handler[1];
            $controller = new $class();
            return $controller->$method($request);
        }

        return $handler($request);
    }
}

