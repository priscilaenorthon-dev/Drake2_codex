<?php

declare(strict_types=1);

namespace Tests;

use App\Services\LogisticsService;
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

    public function testLogisticsEmbarkRequiresComplianceAndNoImpediment(): void
    {
        $service = new LogisticsService();
        $this->assertFalse($service->canEmbark(true, false, false));
        $this->assertFalse($service->canEmbark(true, true, true));
        $this->assertTrue($service->canEmbark(true, true, false));
        $this->assertFalse($service->canEmbark(false, false, true));
    }

    public function testInvalidLogisticsTransitionThrowsException(): void
    {
        $service = new LogisticsService();

        $this->expectException(\RuntimeException::class);
        $service->assertValidTransition('solicitado', 'embarcado');
    }
}
