<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

final class Router
{
    private array $routes = [];
    private ?Closure $authorizer;
    private ?Closure $forbiddenHandler;

    public function __construct(?callable $authorizer = null, ?callable $forbiddenHandler = null)
    {
        $this->authorizer = $authorizer ? Closure::fromCallable($authorizer) : null;
        $this->forbiddenHandler = $forbiddenHandler ? Closure::fromCallable($forbiddenHandler) : null;
    }

    public function add(string $method, string $path, callable|array $handler, ?string $permission = null): void
    {
        $this->routes[strtoupper($method)][$path] = [
            'handler' => $handler,
            'permission' => $permission,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            http_response_code(404);
            echo 'Rota não encontrada';
            return;
        }

        if ($this->isForbidden($route['permission'])) {
            $this->denyAccess();
            return;
        }

        $handler = $route['handler'];

        if (is_array($handler)) {
            [$class, $action] = $handler;
            (new $class())->{$action}();
            return;
        }

        $handler();
    }

    private function isForbidden(?string $permission): bool
    {
        if ($permission === null) {
            return false;
        }

        $authorizer = $this->authorizer ?? static fn(string $requiredPermission): bool => Auth::checkPermission($requiredPermission);

        return !$authorizer($permission);
    }

    private function denyAccess(): void
    {
        if ($this->forbiddenHandler) {
            ($this->forbiddenHandler)();
            return;
        }

        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => 'forbidden',
            'message' => 'Acesso negado',
        ], JSON_UNESCAPED_UNICODE);
    }
}
