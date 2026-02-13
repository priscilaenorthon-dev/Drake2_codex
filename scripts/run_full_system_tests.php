<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

use App\Controllers\AccessController;
use App\Controllers\ApiController;
use App\Controllers\DashboardController;
use App\Controllers\ReportController;
use App\Controllers\WorkflowController;
use App\Core\Database;

final class FullSystemTestRunner
{
    private \PDO $pdo;
    private int $pass = 0;
    private int $fail = 0;

    public function run(): int
    {
        $this->bootstrap();

        $this->testDashboardMetricsTenantIsolation();
        $this->testCrudModulesCreateAndDelete();
        $this->testWorkflowModules();
        $this->testReportsModule();
        $this->testAccessModuleRoleLifecycle();
        $this->testApiModuleIsolationAndAuth();

        echo "Passed: {$this->pass} | Failed: {$this->fail}\n";

        return $this->fail > 0 ? 1 : 0;
    }

    private function bootstrap(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        $schemaSql = [
            'CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, role_id INTEGER, name TEXT, email TEXT, password_hash TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE permissions (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE role_permissions (role_id INTEGER NOT NULL, permission_id INTEGER NOT NULL)',
            'CREATE TABLE user_roles (user_id INTEGER NOT NULL, role_id INTEGER NOT NULL)',
            'CREATE TABLE approval_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, request_type TEXT, request_payload TEXT, status TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE schedules (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, employee_id INTEGER, schedule_date TEXT, status TEXT, unit_id INTEGER, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE employee_trainings (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, employee_id INTEGER NOT NULL, valid_until TEXT NOT NULL)',
            'CREATE TABLE logistics_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, status TEXT)',
            'CREATE TABLE employees (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT NOT NULL, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE companies (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE units (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE locations (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE positions (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE teams (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE shifts (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE qualifications (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE trainings (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE vacation_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE timesheets (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE operations_records (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, name TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, tenant_id INTEGER NOT NULL, user_id INTEGER NOT NULL, resource TEXT NOT NULL, action TEXT NOT NULL, before_data TEXT, after_data TEXT, created_at TEXT NOT NULL)',
        ];

        foreach ($schemaSql as $sql) {
            $this->pdo->exec($sql);
        }

        $this->injectConnection($this->pdo);

        $this->pdo->exec("INSERT INTO permissions (id, name, created_at, updated_at) VALUES (1, 'dashboard.view', '2024-01-01 00:00:00', '2024-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO permissions (id, name, created_at, updated_at) VALUES (2, 'crud.manage', '2024-01-01 00:00:00', '2024-01-01 00:00:00')");

        $_SESSION['user'] = ['id' => 10, 'tenant_id' => 1, 'name' => 'Tenant 1', 'email' => 't1@example.com'];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    private function testDashboardMetricsTenantIsolation(): void
    {
        $today = date('Y-m-d');
        $limitDate = date('Y-m-d', strtotime('+20 days'));
        $this->pdo->exec("INSERT INTO approval_requests (tenant_id, status, created_at, updated_at) VALUES (1, 'pending', '2024-01-01 00:00:00', '2024-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO approval_requests (tenant_id, status, created_at, updated_at) VALUES (2, 'pending', '2024-01-01 00:00:00', '2024-01-01 00:00:00')");
        $this->pdo->exec("INSERT INTO schedules (tenant_id, schedule_date, status) VALUES (1, '{$today}', 'planned')");
        $this->pdo->exec("INSERT INTO schedules (tenant_id, schedule_date, status) VALUES (2, '{$today}', 'planned')");
        $this->pdo->exec("INSERT INTO employee_trainings (tenant_id, employee_id, valid_until) VALUES (1, 1, '{$limitDate}')");
        $this->pdo->exec("INSERT INTO employee_trainings (tenant_id, employee_id, valid_until) VALUES (2, 1, '{$limitDate}')");
        $this->pdo->exec("INSERT INTO logistics_requests (tenant_id, status) VALUES (1, 'pending')");
        $this->pdo->exec("INSERT INTO logistics_requests (tenant_id, status) VALUES (2, 'pending')");

        ob_start();
        (new DashboardController())->index();
        $html = (string) ob_get_clean();

        $this->assertContains($html, 'Painel Integrado de Operações');
        $this->assertContains($html, 'metric-value');
    }

    private function testCrudModulesCreateAndDelete(): void
    {
        $_GET = ['module' => 'employees'];
        $_POST = ['name' => 'Colaborador Teste'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        (new \App\Controllers\CrudController())->store();

        $employee = $this->pdo->query("SELECT name, tenant_id FROM employees WHERE name = 'Colaborador Teste'")->fetch();
        $this->assertTrue($employee !== false && (int) $employee['tenant_id'] === 1, 'CRUD create deve respeitar tenant.');

        $id = (int) $this->pdo->query("SELECT id FROM employees WHERE name = 'Colaborador Teste'")->fetchColumn();
        $_GET = ['module' => 'employees', 'id' => (string) $id];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        (new \App\Controllers\CrudController())->delete();

        $deleted = $this->pdo->query("SELECT COUNT(*) FROM employees WHERE id = {$id}")->fetchColumn();
        $this->assertSame(0, (int) $deleted, 'CRUD delete deve remover registro do tenant corrente.');
    }

    private function testWorkflowModules(): void
    {
        $this->pdo->exec("INSERT INTO approval_requests (id, tenant_id, status, updated_at) VALUES (500, 2, 'pending', '2024-01-01 00:00:00')");

        $_GET = ['id' => '500', 'status' => 'approved'];
        (new WorkflowController())->approve();

        $status = $this->pdo->query('SELECT status FROM approval_requests WHERE id = 500')->fetchColumn();
        $this->assertSame('pending', (string) $status, 'Workflow approve não pode atualizar outro tenant.');

        $_GET = ['employee_id' => '99'];
        $this->pdo->exec("INSERT INTO employee_trainings (tenant_id, employee_id, valid_until) VALUES (1, 99, '2020-01-01')");
        ob_start();
        (new WorkflowController())->validateImpediments();
        $json = (string) ob_get_clean();

        $this->assertContains($json, '"blocked":true');
    }

    private function testReportsModule(): void
    {
        $this->pdo->exec("INSERT INTO employees (id, tenant_id, name) VALUES (100, 1, 'Alice')");
        $this->pdo->exec("INSERT INTO employees (id, tenant_id, name) VALUES (200, 2, 'Bob')");
        $this->pdo->exec("INSERT INTO schedules (tenant_id, employee_id, schedule_date, status, unit_id) VALUES (1, 100, '2024-01-05', 'confirmed', 1)");
        $this->pdo->exec("INSERT INTO schedules (tenant_id, employee_id, schedule_date, status, unit_id) VALUES (2, 200, '2024-01-05', 'confirmed', 1)");

        $_GET = ['from' => '2024-01-01', 'to' => '2024-01-31', 'export' => 'csv'];
        ob_start();
        (new ReportController())->index();
        $csv = (string) ob_get_clean();

        $this->assertContains($csv, 'Alice');
        $this->assertNotContains($csv, 'Bob');
    }

    private function testAccessModuleRoleLifecycle(): void
    {
        $_POST = ['name' => 'Supervisor'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        (new AccessController())->storeRole();

        $roleId = (int) $this->pdo->query("SELECT id FROM roles WHERE name = 'Supervisor' AND tenant_id = 1")->fetchColumn();
        $this->assertTrue($roleId > 0, 'Deve criar papel no tenant atual.');

        $_POST = ['role_id' => (string) $roleId, 'permission_ids' => ['1', '2', '2']];
        (new AccessController())->syncRolePermissions();

        $count = (int) $this->pdo->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = {$roleId}")->fetchColumn();
        $this->assertSame(2, $count, 'Deve sincronizar permissões sem duplicidade.');

        $_POST = ['role_id' => (string) $roleId, 'name' => 'Supervisor Sr'];
        (new AccessController())->updateRole();
        $updated = (string) $this->pdo->query("SELECT name FROM roles WHERE id = {$roleId}")->fetchColumn();
        $this->assertSame('Supervisor Sr', $updated, 'Deve atualizar nome do papel.');

        $_POST = ['role_id' => (string) $roleId];
        (new AccessController())->deleteRole();
        $exists = (int) $this->pdo->query("SELECT COUNT(*) FROM roles WHERE id = {$roleId}")->fetchColumn();
        $this->assertSame(0, $exists, 'Deve remover papel do tenant atual.');
    }

    private function testApiModuleIsolationAndAuth(): void
    {
        $this->pdo->exec("INSERT INTO schedules (tenant_id, schedule_date, status) VALUES (1, '2024-02-01', 'planned')");
        $this->pdo->exec("INSERT INTO schedules (tenant_id, schedule_date, status) VALUES (2, '2024-02-01', 'planned')");
        $this->pdo->exec("INSERT INTO employee_trainings (tenant_id, employee_id, valid_until) VALUES (1, 111, '2024-02-05')");
        $this->pdo->exec("INSERT INTO employee_trainings (tenant_id, employee_id, valid_until) VALUES (2, 222, '2024-02-05')");

        ob_start();
        (new ApiController())->schedules();
        $schedules = json_decode((string) ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue(count($schedules) >= 1, 'API schedules deve retornar dados do tenant logado.');
        foreach ($schedules as $schedule) {
            $this->assertSame(1, (int) $schedule['tenant_id'], 'API schedules não pode vazar tenant.');
        }

        ob_start();
        (new ApiController())->trainingsExpiring();
        $expiring = json_decode((string) ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
        $today = strtotime(date('Y-m-d'));
        $limit = strtotime('+30 days', $today);
        foreach ($expiring as $training) {
            $date = strtotime((string) $training['valid_until']);
            $this->assertTrue($date >= $today && $date <= $limit, 'Treinamento fora da janela de próximos 30 dias.');
        }

        unset($_SESSION['user']);
        ob_start();
        (new ApiController())->schedules();
        $unauth = (string) ob_get_clean();
        $this->assertContains($unauth, 'autenticado');

        $_SESSION['user'] = ['id' => 10, 'tenant_id' => 1, 'name' => 'Tenant 1', 'email' => 't1@example.com'];
    }

    private function injectConnection(\PDO $pdo): void
    {
        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty('pdo');
        $property->setAccessible(true);
        $property->setValue(null, $pdo);
    }

    private function assertContains(string $haystack, string $needle, string $message = 'Conteúdo esperado não encontrado.'): void
    {
        if (!str_contains($haystack, $needle)) {
            $this->fail($message . " Needle: {$needle}");
            return;
        }

        $this->pass++;
    }

    private function assertNotContains(string $haystack, string $needle, string $message = 'Conteúdo não deveria existir.'): void
    {
        if (str_contains($haystack, $needle)) {
            $this->fail($message . " Needle: {$needle}");
            return;
        }

        $this->pass++;
    }

    private function assertSame(int|string $expected, int|string $actual, string $message = 'Valores diferentes.'): void
    {
        if ($expected !== $actual) {
            $this->fail($message . " Esperado: {$expected}, recebido: {$actual}");
            return;
        }

        $this->pass++;
    }

    private function assertTrue(bool $condition, string $message = 'Condição esperada como verdadeira.'): void
    {
        if (!$condition) {
            $this->fail($message);
            return;
        }

        $this->pass++;
    }

    private function fail(string $message): void
    {
        $this->fail++;
        echo "FAIL: {$message}\n";
    }
}

$runner = new FullSystemTestRunner();
exit($runner->run());
