<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

$pass = 0;
$fail = 0;

$hash = password_hash('123456', PASSWORD_DEFAULT);
if (password_verify('123456', $hash)) { $pass++; } else { $fail++; echo "Auth test failed\n"; }

$permissionService = new App\Services\PermissionService();
if ($permissionService->hasPermission(['crud.manage'], 'crud.manage') && !$permissionService->hasPermission([], 'crud.manage')) { $pass++; } else { $fail++; echo "Permission test failed\n"; }

$scheduleService = new App\Services\ScheduleService();
if (!$scheduleService->canConfirm([['valid_until' => date('Y-m-d', strtotime('-1 day'))]])) { $pass++; } else { $fail++; echo "Schedule impediment test failed\n"; }

$trainingService = new App\Services\TrainingService();
if ($trainingService->expiringWithin30Days(date('Y-m-d', strtotime('+15 day')))
    && !$trainingService->expiringWithin30Days(date('Y-m-d', strtotime('+45 day')))
    && !$trainingService->expiringWithin30Days(date('Y-m-d', strtotime('-1 day')))) { $pass++; } else { $fail++; echo "Training expiration test failed\n"; }

echo "Passed: {$pass} | Failed: {$fail}\n";
exit($fail > 0 ? 1 : 0);
