<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\TenantContext;
use App\Core\View;

final class WorkflowController
{
    public function index(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $stmt = Database::connection()->prepare('SELECT * FROM approval_requests WHERE tenant_id = :tenant_id ORDER BY id DESC');
        $stmt->execute(['tenant_id' => TenantContext::tenantId()]);
        $requests = $stmt->fetchAll();

        View::render('workflows/index', ['requests' => $requests]);
    }

    public function approve(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? 'approved';
        $stmt = Database::connection()->prepare(
            'UPDATE approval_requests
             SET status = :status, updated_at = :updated_at
             WHERE tenant_id = :tenant_id AND id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
            'tenant_id' => TenantContext::tenantId(),
            'id' => $id,
        ]);

        header('Location: /workflows');
    }

    public function validateImpediments(): void
    {
        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM employee_trainings
             WHERE tenant_id = :tenant_id AND employee_id = :employee_id AND valid_until < :today'
        );
        $stmt->execute([
            'tenant_id' => TenantContext::tenantId(),
            'employee_id' => $employeeId,
            'today' => date('Y-m-d'),
        ]);
        $hasIssues = (int) $stmt->fetchColumn() > 0;

        header('Content-Type: application/json');
        echo json_encode(['employee_id' => $employeeId, 'blocked' => $hasIssues, 'reason' => $hasIssues ? 'Treinamento vencido' : null]);
    }

    public function teamSwap(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            View::render('workflows/swap');
            return;
        }

        $payload = [
            'tenant_id' => TenantContext::tenantId(),
            'request_type' => 'team_swap',
            'request_payload' => json_encode($_POST),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $sql = 'INSERT INTO approval_requests (tenant_id, request_type, request_payload, status, created_at, updated_at)
                VALUES (:tenant_id, :request_type, :request_payload, :status, :created_at, :updated_at)';
        Database::connection()->prepare($sql)->execute($payload);

        header('Location: /workflows');
    }
}
