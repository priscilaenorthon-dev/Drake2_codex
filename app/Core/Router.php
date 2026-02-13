<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[strtoupper($method)][$path] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo 'Rota não encontrada';
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            (new $class())->{$action}();
            return;
        }

        $handler();
    }
}
