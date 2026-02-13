<?php

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\CrudController;
use App\Controllers\DashboardController;
use App\Controllers\ReportController;
use App\Controllers\WorkflowController;

$router->add('GET', '/', [DashboardController::class, 'index']);
$router->add('GET', '/login', [AuthController::class, 'loginForm']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('GET', '/logout', [AuthController::class, 'logout']);
$router->add('GET', '/dashboard', [DashboardController::class, 'index']);

$router->add('GET', '/crud', [CrudController::class, 'index']);
$router->add('GET', '/crud/create', [CrudController::class, 'create']);
$router->add('POST', '/crud/store', [CrudController::class, 'store']);
$router->add('GET', '/crud/delete', [CrudController::class, 'delete']);

$router->add('GET', '/workflows', [WorkflowController::class, 'index']);
$router->add('GET', '/workflows/config', [WorkflowController::class, 'config']);
$router->add('POST', '/workflows/config', [WorkflowController::class, 'config']);
$router->add('GET', '/workflows/monitor', [WorkflowController::class, 'monitor']);
$router->add('GET', '/workflows/approve', [WorkflowController::class, 'approve']);
$router->add('GET', '/workflows/team-swap', [WorkflowController::class, 'teamSwap']);
$router->add('POST', '/workflows/team-swap', [WorkflowController::class, 'teamSwap']);
$router->add('GET', '/workflows/validate-impediments', [WorkflowController::class, 'validateImpediments']);

$router->add('GET', '/reports', [ReportController::class, 'index']);

$router->add('GET', '/api/schedules', [ApiController::class, 'schedules']);
$router->add('GET', '/api/trainings-expiring', [ApiController::class, 'trainingsExpiring']);
