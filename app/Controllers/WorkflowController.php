<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\LogisticsService;

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
        $id = (int) ($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? 'approved';
        $stmt = Database::connection()->prepare('UPDATE approval_requests SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

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


    public function transitionLogisticsStatus(): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            return;
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $nextStatus = (string) ($_POST['to_status'] ?? '');
        $note = trim((string) ($_POST['note'] ?? ''));

        if ($requestId < 1 || $nextStatus === '') {
            http_response_code(422);
            echo 'Parâmetros obrigatórios ausentes.';
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM logistics_requests WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['id' => $requestId, 'tenant_id' => (int) $user['tenant_id']]);
        $request = $stmt->fetch();

        if (!$request) {
            http_response_code(404);
            echo 'Solicitação logística não encontrada.';
            return;
        }

        $service = new LogisticsService();

        try {
            $service->assertValidTransition((string) $request['operational_status'], $nextStatus);
        } catch (\RuntimeException $exception) {
            http_response_code(422);
            echo $exception->getMessage();
            return;
        }

        if ($nextStatus === 'embarcado') {
            $trainingStmt = $pdo->prepare('SELECT COUNT(*) FROM employee_trainings WHERE tenant_id = :tenant_id AND employee_id = :employee_id AND valid_until >= CURDATE()');
            $trainingStmt->execute([
                'tenant_id' => (int) $user['tenant_id'],
                'employee_id' => (int) ($request['employee_id'] ?? 0),
            ]);
            $hasValidCompliance = (int) $trainingStmt->fetchColumn() > 0;

            $impedimentStmt = $pdo->prepare('SELECT COUNT(*) FROM employee_impediments WHERE tenant_id = :tenant_id AND employee_id = :employee_id AND status = :status AND (ends_at IS NULL OR ends_at >= NOW())');
            $impedimentStmt->execute([
                'tenant_id' => (int) $user['tenant_id'],
                'employee_id' => (int) ($request['employee_id'] ?? 0),
                'status' => 'active',
            ]);
            $hasOpenImpediments = (int) $impedimentStmt->fetchColumn() > 0;

            $canEmbark = $service->canEmbark((bool) $request['requires_compliance'], $hasValidCompliance, $hasOpenImpediments);
            if (!$canEmbark) {
                http_response_code(422);
                echo 'Embarque bloqueado: compliance inválido e/ou impedimento ativo.';
                return;
            }
        }

        $updateStmt = $pdo->prepare('UPDATE logistics_requests
            SET operational_status = :status,
                status = :status,
                embarked_at = CASE WHEN :status_embarked = 1 THEN NOW() ELSE embarked_at END,
                updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id');
        $updateStmt->execute([
            'status' => $nextStatus,
            'status_embarked' => $nextStatus === 'embarcado' ? 1 : 0,
            'id' => $requestId,
            'tenant_id' => (int) $user['tenant_id'],
        ]);

        $historyStmt = $pdo->prepare('INSERT INTO logistics_status_history
            (tenant_id, logistics_request_id, changed_by_user_id, from_status, to_status, note, created_at)
            VALUES (:tenant_id, :request_id, :user_id, :from_status, :to_status, :note, NOW())');
        $historyStmt->execute([
            'tenant_id' => (int) $user['tenant_id'],
            'request_id' => $requestId,
            'user_id' => (int) $user['id'],
            'from_status' => (string) $request['operational_status'],
            'to_status' => $nextStatus,
            'note' => $note !== '' ? $note : null,
        ]);

        header('Content-Type: application/json');
        echo json_encode(['request_id' => $requestId, 'from_status' => $request['operational_status'], 'to_status' => $nextStatus]);
    }

    public function teamSwap(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            View::render('workflows/swap');
            return;
        }

        $payload = [
            'tenant_id' => (int) Auth::user()['tenant_id'],
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
