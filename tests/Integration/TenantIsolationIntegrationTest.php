<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\ReportController;
use App\Controllers\WorkflowController;
use App\Core\Database;
use App\Repositories\BaseRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class TenantIsolationIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->bootstrapSchema();
        $this->injectConnection($this->pdo);

        $_SESSION['user'] = ['id' => 10, 'tenant_id' => 1, 'name' => 'Tenant 1', 'email' => 't1@example.com'];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testWorkflowApproveCannotUpdateOtherTenantRecord(): void
    {
        $this->pdo->exec("INSERT INTO approval_requests (id, tenant_id, status, updated_at) VALUES (200, 2, 'pending', '2024-01-01 00:00:00')");

        $_GET = ['id' => '200', 'status' => 'approved'];
        (new WorkflowController())->approve();

        $status = $this->pdo->query('SELECT status FROM approval_requests WHERE id = 200')->fetchColumn();
        $this->assertSame('pending', $status);
    }

    public function testValidateImpedimentsIgnoresOtherTenantTraining(): void
    {
        $this->pdo->exec("INSERT INTO employee_trainings (tenant_id, employee_id, valid_until) VALUES (2, 55, '2020-01-01')");

        $_GET = ['employee_id' => '55'];
        ob_start();
        (new WorkflowController())->validateImpediments();
        $payload = (string) ob_get_clean();

        $this->assertStringContainsString('"blocked":false', $payload);
    }

    public function testSchedulesAndLogisticsQueriesOnlyReturnCurrentTenantRecords(): void
    {
        $this->pdo->exec("INSERT INTO schedules (id, tenant_id, schedule_date, status) VALUES (1, 1, '2024-01-01', 'ok')");
        $this->pdo->exec("INSERT INTO schedules (id, tenant_id, schedule_date, status) VALUES (2, 2, '2024-01-01', 'ok')");
        $this->pdo->exec("INSERT INTO logistics_requests (id, tenant_id, status) VALUES (1, 1, 'pending')");
        $this->pdo->exec("INSERT INTO logistics_requests (id, tenant_id, status) VALUES (2, 2, 'pending')");

        $repo = new BaseRepository($this->pdo);
        $schedules = $repo->allByTenant('schedules', 1);
        $logistics = $repo->allByTenant('logistics_requests', 1);

        $this->assertCount(1, $schedules);
        $this->assertSame(1, (int) $schedules[0]['tenant_id']);
        $this->assertCount(1, $logistics);
        $this->assertSame(1, (int) $logistics[0]['tenant_id']);
    }

    public function testReportsDoNotLeakRowsFromOtherTenant(): void
    {
        $this->pdo->exec("INSERT INTO employees (id, tenant_id, name) VALUES (10, 1, 'Alice')");
        $this->pdo->exec("INSERT INTO employees (id, tenant_id, name) VALUES (20, 2, 'Bob')");
        $this->pdo->exec("INSERT INTO schedules (id, tenant_id, employee_id, schedule_date, status, unit_id) VALUES (1, 1, 10, '2024-01-02', 'confirmed', 1)");
        $this->pdo->exec("INSERT INTO schedules (id, tenant_id, employee_id, schedule_date, status, unit_id) VALUES (2, 2, 20, '2024-01-02', 'confirmed', 1)");

        $_GET = ['from' => '2024-01-01', 'to' => '2024-01-31', 'export' => 'csv'];
        ob_start();
        (new ReportController())->index();
        $csv = (string) ob_get_clean();

        $this->assertStringContainsString('Alice', $csv);
        $this->assertStringNotContainsString('Bob', $csv);
    }

    public function testWorkflowApproveFailsWithoutTenantContext(): void
    {
        unset($_SESSION['user']);
        $_GET = ['id' => '1', 'status' => 'approved'];

        $this->expectException(RuntimeException::class);
        (new WorkflowController())->approve();
    }

    private function bootstrapSchema(): void
    {
        $this->pdo->exec('CREATE TABLE approval_requests (id INTEGER PRIMARY KEY, tenant_id INTEGER NOT NULL, status TEXT, updated_at TEXT)');
        $this->pdo->exec('CREATE TABLE employee_trainings (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, employee_id INTEGER NOT NULL, valid_until TEXT NOT NULL)');
        $this->pdo->exec('CREATE TABLE schedules (id INTEGER PRIMARY KEY, tenant_id INTEGER NOT NULL, employee_id INTEGER NULL, schedule_date TEXT, status TEXT, unit_id INTEGER NULL)');
        $this->pdo->exec('CREATE TABLE logistics_requests (id INTEGER PRIMARY KEY, tenant_id INTEGER NOT NULL, status TEXT)');
        $this->pdo->exec('CREATE TABLE employees (id INTEGER PRIMARY KEY, tenant_id INTEGER NOT NULL, name TEXT NOT NULL)');
    }

    private function injectConnection(PDO $pdo): void
    {
        $reflection = new ReflectionClass(Database::class);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue(null, $pdo);
    }
}
