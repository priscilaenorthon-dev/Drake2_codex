<?php

declare(strict_types=1);

use App\Core\Router;

require __DIR__ . '/../config/bootstrap.php';

$router = new Router();
require __DIR__ . '/../routes/web.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$router->dispatch($_SERVER['REQUEST_METHOD'], $path);
