<?php

use App\Controllers\AccessController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\CrudController;
use App\Controllers\DashboardController;
use App\Controllers\ReportController;
use App\Controllers\WorkflowController;

$router->add('GET', '/', [DashboardController::class, 'index'], 'dashboard.view');
$router->add('GET', '/login', [AuthController::class, 'loginForm']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('GET', '/logout', [AuthController::class, 'logout']);
$router->add('GET', '/dashboard', [DashboardController::class, 'index'], 'dashboard.view');

$router->add('GET', '/crud', [CrudController::class, 'index'], 'crud.manage');
$router->add('GET', '/crud/create', [CrudController::class, 'create'], 'crud.manage');
$router->add('POST', '/crud/store', [CrudController::class, 'store'], 'crud.manage');
$router->add('GET', '/crud/delete', [CrudController::class, 'delete'], 'crud.manage');

$router->add('GET', '/workflows', [WorkflowController::class, 'index'], 'workflow.approve');
$router->add('GET', '/workflows/approve', [WorkflowController::class, 'approve'], 'workflow.approve');
$router->add('GET', '/workflows/team-swap', [WorkflowController::class, 'teamSwap'], 'workflow.approve');
$router->add('POST', '/workflows/team-swap', [WorkflowController::class, 'teamSwap'], 'workflow.approve');
$router->add('GET', '/workflows/validate-impediments', [WorkflowController::class, 'validateImpediments'], 'workflow.approve');

$router->add('GET', '/reports', [ReportController::class, 'index'], 'reports.view');

$router->add('GET', '/access', [AccessController::class, 'index'], 'access.manage');
$router->add('POST', '/access/roles/store', [AccessController::class, 'storeRole'], 'access.manage');
$router->add('POST', '/access/roles/update', [AccessController::class, 'updateRole'], 'access.manage');
$router->add('POST', '/access/roles/delete', [AccessController::class, 'deleteRole'], 'access.manage');
$router->add('POST', '/access/roles/permissions', [AccessController::class, 'syncRolePermissions'], 'access.manage');

$router->add('GET', '/api/schedules', [ApiController::class, 'schedules'], 'dashboard.view');
$router->add('GET', '/api/trainings-expiring', [ApiController::class, 'trainingsExpiring'], 'dashboard.view');
