<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\AuditService;

final class WorkflowController
{
    public function index(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $pdo = Database::connection();
        $tenantId = (int) $user['tenant_id'];
        $requests = $pdo->query("SELECT * FROM approval_requests WHERE tenant_id = {$tenantId} ORDER BY id DESC")->fetchAll();
        View::render('workflows/index', ['requests' => $requests]);
    }

    public function approve(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $tenantId = (int) $user['tenant_id'];
        $id = (int) ($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? 'approved';

        $select = Database::connection()->prepare('SELECT * FROM approval_requests WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $select->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $before = $select->fetch() ?: null;

        $stmt = Database::connection()->prepare('UPDATE approval_requests SET status = :status, updated_at = NOW() WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['status' => $status, 'id' => $id, 'tenant_id' => $tenantId]);

        if ($before) {
            $after = $before;
            $after['status'] = $status;
            $after['updated_at'] = date('Y-m-d H:i:s');
            (new AuditService())->logEvent(
                $tenantId,
                (int) $user['id'],
                'approval_requests',
                'state_transition',
                ['id' => $id, 'status' => $before['status']],
                ['id' => $id, 'status' => $status]
            );
        }

        header('Location: /workflows');
    }

    public function validateImpediments(): void
    {
        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM employee_trainings WHERE employee_id = :employee_id AND valid_until < CURDATE()');
        $stmt->execute(['employee_id' => $employeeId]);
        $hasIssues = (int) $stmt->fetchColumn() > 0;

        header('Content-Type: application/json');
        echo json_encode(['employee_id' => $employeeId, 'blocked' => $hasIssues, 'reason' => $hasIssues ? 'Treinamento vencido' : null]);
    }

    public function teamSwap(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            View::render('workflows/swap');
            return;
        }

        $payload = [
            'tenant_id' => (int) $user['tenant_id'],
            'request_type' => 'team_swap',
            'request_payload' => json_encode($_POST),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $sql = 'INSERT INTO approval_requests (tenant_id, request_type, request_payload, status, created_at, updated_at)
                VALUES (:tenant_id, :request_type, :request_payload, :status, :created_at, :updated_at)';
        Database::connection()->prepare($sql)->execute($payload);

        (new AuditService())->logEvent(
            (int) $user['tenant_id'],
            (int) $user['id'],
            'approval_requests',
            'create',
            null,
            ['request_type' => 'team_swap', 'status' => 'pending', 'payload' => $_POST]
        );

        header('Location: /workflows');
    }
}
