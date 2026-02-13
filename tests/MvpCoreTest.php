<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Router;
use App\Services\PermissionService;
use App\Services\ScheduleService;
use App\Services\TrainingService;
use PHPUnit\Framework\TestCase;

final class MvpCoreTest extends TestCase
{
    public function testAuthPasswordHashIsValid(): void
    {
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        $this->assertTrue(password_verify('123456', $hash));
    }

    public function testPermissionCheck(): void
    {
        $service = new PermissionService();
        $this->assertTrue($service->hasPermission(['dashboard.view', 'crud.manage'], 'crud.manage'));
        $this->assertFalse($service->hasPermission(['dashboard.view'], 'workflow.approve'));
    }

    public function testScheduleValidationWithExpiredTraining(): void
    {
        $service = new ScheduleService();
        $this->assertFalse($service->canConfirm([
            ['valid_until' => date('Y-m-d', strtotime('-1 day'))],
        ]));
    }

    public function testTrainingExpirationRule(): void
    {
        $service = new TrainingService();
        $this->assertTrue($service->expiringWithin30Days(date('Y-m-d', strtotime('+10 day'))));
        $this->assertFalse($service->expiringWithin30Days(date('Y-m-d', strtotime('+45 day'))));
    }

    public function testUserWithoutPermissionCannotApproveWorkflow(): void
    {
        $status = $this->dispatchProtectedRoute('/workflows/approve', 'workflow.approve');
        $this->assertSame(403, $status);
    }

    public function testUserWithoutPermissionCannotExportReport(): void
    {
        $status = $this->dispatchProtectedRoute('/reports', 'reports.view');
        $this->assertSame(403, $status);
    }

    public function testUserWithoutPermissionCannotChangeCrudRecords(): void
    {
        $status = $this->dispatchProtectedRoute('/crud/store', 'crud.manage', 'POST');
        $this->assertSame(403, $status);
    }

    private function dispatchProtectedRoute(string $path, string $requiredPermission, string $method = 'GET'): int
    {
        $statusCode = 0;
        $executed = false;

        $router = new Router(
            static fn(string $permission): bool => $permission !== $requiredPermission,
            static function () use (&$statusCode): void {
                $statusCode = 403;
            }
        );

        $router->add($method, $path, static function () use (&$executed): void {
            $executed = true;
        }, $requiredPermission);

        $router->dispatch($method, $path);

        $this->assertFalse($executed, 'Action não deve executar sem permissão.');

        return $statusCode;
    }
}
