<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use PDO;

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
        $sql = "SELECT ar.*, wd.name AS workflow_name, wi.current_step_order,
                       wis.name AS current_step_name, active_step.sla_deadline_at
                FROM approval_requests ar
                LEFT JOIN workflow_instances wi ON wi.id = ar.workflow_instance_id
                LEFT JOIN workflow_definitions wd ON wd.id = wi.workflow_definition_id
                LEFT JOIN workflow_steps wis ON wis.workflow_definition_id = wd.id AND wis.step_order = wi.current_step_order
                LEFT JOIN workflow_instance_steps active_step ON active_step.workflow_instance_id = wi.id AND active_step.step_order = wi.current_step_order
                WHERE ar.tenant_id = :tenant_id
                ORDER BY ar.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);

        View::render('workflows/index', ['requests' => $stmt->fetchAll()]);
    }

    public function approve(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $id = (int) ($_GET['id'] ?? 0);
        $decision = $_GET['status'] ?? 'approved';
        $pdo = Database::connection();

        $requestStmt = $pdo->prepare('SELECT * FROM approval_requests WHERE id = :id AND tenant_id = :tenant_id');
        $requestStmt->execute(['id' => $id, 'tenant_id' => (int) $user['tenant_id']]);
        $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

        if (!$request || !$request['workflow_instance_id']) {
            header('Location: /workflows');
            return;
        }

        $instanceStmt = $pdo->prepare('SELECT * FROM workflow_instances WHERE id = :id');
        $instanceStmt->execute(['id' => (int) $request['workflow_instance_id']]);
        $instance = $instanceStmt->fetch(PDO::FETCH_ASSOC);

        if (!$instance) {
            header('Location: /workflows');
            return;
        }

        $stepStmt = $pdo->prepare('SELECT * FROM workflow_instance_steps WHERE workflow_instance_id = :workflow_instance_id AND step_order = :step_order');
        $stepStmt->execute([
            'workflow_instance_id' => (int) $instance['id'],
            'step_order' => (int) $instance['current_step_order'],
        ]);
        $step = $stepStmt->fetch(PDO::FETCH_ASSOC);

        if (!$step) {
            header('Location: /workflows');
            return;
        }

        $elapsedSql = 'TIMESTAMPDIFF(MINUTE, COALESCE(started_at, created_at), NOW())';
        $updateCurrentStep = $pdo->prepare("UPDATE workflow_instance_steps
            SET status = :status,
                acted_at = NOW(),
                acted_by_user_id = :acted_by_user_id,
                elapsed_minutes = {$elapsedSql},
                updated_at = NOW()
            WHERE id = :id");
        $updateCurrentStep->execute([
            'status' => $decision,
            'acted_by_user_id' => (int) $user['id'],
            'id' => (int) $step['id'],
        ]);

        if ($decision === 'rejected') {
            $pdo->prepare('UPDATE workflow_instances SET status = :status, finished_at = NOW(), updated_at = NOW() WHERE id = :id')
                ->execute(['status' => 'rejected', 'id' => (int) $instance['id']]);
            $pdo->prepare('UPDATE approval_requests SET status = :status, updated_at = NOW() WHERE id = :id')
                ->execute(['status' => 'rejected', 'id' => $id]);

            header('Location: /workflows');
            return;
        }

        $nextOrder = (int) $instance['current_step_order'] + 1;
        $nextStepStmt = $pdo->prepare('SELECT wis.*, wstep.id AS instance_step_id
            FROM workflow_steps wis
            LEFT JOIN workflow_instance_steps wstep
                ON wstep.workflow_instance_id = :workflow_instance_id
                AND wstep.step_order = wis.step_order
            WHERE wis.workflow_definition_id = :workflow_definition_id AND wis.step_order = :step_order');
        $nextStepStmt->execute([
            'workflow_instance_id' => (int) $instance['id'],
            'workflow_definition_id' => (int) $instance['workflow_definition_id'],
            'step_order' => $nextOrder,
        ]);
        $nextStep = $nextStepStmt->fetch(PDO::FETCH_ASSOC);

        if ($nextStep && $nextStep['instance_step_id']) {
            $pdo->prepare('UPDATE workflow_instance_steps
                SET status = :status,
                    started_at = NOW(),
                    updated_at = NOW()
                WHERE id = :id')
                ->execute(['status' => 'in_progress', 'id' => (int) $nextStep['instance_step_id']]);

            $pdo->prepare('UPDATE workflow_instances
                SET current_step_order = :current_step_order,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :id')
                ->execute([
                    'current_step_order' => $nextOrder,
                    'status' => 'in_progress',
                    'id' => (int) $instance['id'],
                ]);

            $pdo->prepare('UPDATE approval_requests SET status = :status, updated_at = NOW() WHERE id = :id')
                ->execute(['status' => 'in_progress', 'id' => $id]);
        } else {
            $pdo->prepare('UPDATE workflow_instances SET status = :status, finished_at = NOW(), updated_at = NOW() WHERE id = :id')
                ->execute(['status' => 'approved', 'id' => (int) $instance['id']]);
            $pdo->prepare('UPDATE approval_requests SET status = :status, updated_at = NOW() WHERE id = :id')
                ->execute(['status' => 'approved', 'id' => $id]);
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

        $tenantId = (int) $user['tenant_id'];
        $requestType = $_POST['request_type'] ?? 'team_swap';
        $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : null;
        $unitId = isset($_POST['unit_id']) ? (int) $_POST['unit_id'] : null;
        $teamId = isset($_POST['team_id']) ? (int) $_POST['team_id'] : null;
        $urgency = $_POST['urgency'] ?? 'normal';

        $payload = $_POST;
        $payload['requester'] = $user['name'];
        $payload['requested_at'] = date('Y-m-d H:i:s');

        $pdo = Database::connection();
        $definitionId = $this->resolveWorkflowDefinition($tenantId, $requestType, $amount, $unitId, $teamId, $urgency);

        if ($definitionId === null) {
            header('Location: /workflows?error=workflow_not_found');
            return;
        }

        $insertRequest = $pdo->prepare('INSERT INTO approval_requests (tenant_id, request_type, request_payload, status, created_at, updated_at)
            VALUES (:tenant_id, :request_type, :request_payload, :status, NOW(), NOW())');
        $insertRequest->execute([
            'tenant_id' => $tenantId,
            'request_type' => $requestType,
            'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => 'in_progress',
        ]);
        $requestId = (int) $pdo->lastInsertId();

        $stepsStmt = $pdo->prepare('SELECT * FROM workflow_steps WHERE workflow_definition_id = :workflow_definition_id ORDER BY step_order ASC');
        $stepsStmt->execute(['workflow_definition_id' => $definitionId]);
        $steps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($steps === []) {
            $pdo->prepare('UPDATE approval_requests SET status = :status, updated_at = NOW() WHERE id = :id')
                ->execute(['status' => 'approved', 'id' => $requestId]);
            header('Location: /workflows');
            return;
        }

        $instanceStmt = $pdo->prepare('INSERT INTO workflow_instances
            (tenant_id, workflow_definition_id, request_type, request_payload, status, current_step_order, started_at, created_at, updated_at)
            VALUES (:tenant_id, :workflow_definition_id, :request_type, :request_payload, :status, :current_step_order, NOW(), NOW(), NOW())');
        $instanceStmt->execute([
            'tenant_id' => $tenantId,
            'workflow_definition_id' => $definitionId,
            'request_type' => $requestType,
            'request_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => 'in_progress',
            'current_step_order' => (int) $steps[0]['step_order'],
        ]);
        $instanceId = (int) $pdo->lastInsertId();

        foreach ($steps as $index => $step) {
            $isFirst = $index === 0;
            $insertStep = $pdo->prepare('INSERT INTO workflow_instance_steps
                (workflow_instance_id, workflow_step_id, step_order, status, sla_deadline_at, started_at, created_at, updated_at)
                VALUES (:workflow_instance_id, :workflow_step_id, :step_order, :status, DATE_ADD(NOW(), INTERVAL :sla_hours HOUR), :started_at, NOW(), NOW())');
            $insertStep->execute([
                'workflow_instance_id' => $instanceId,
                'workflow_step_id' => (int) $step['id'],
                'step_order' => (int) $step['step_order'],
                'status' => $isFirst ? 'in_progress' : 'pending',
                'sla_hours' => (int) $step['sla_hours'],
                'started_at' => $isFirst ? date('Y-m-d H:i:s') : null,
            ]);
        }

        $pdo->prepare('UPDATE approval_requests SET workflow_instance_id = :workflow_instance_id WHERE id = :id')
            ->execute(['workflow_instance_id' => $instanceId, 'id' => $requestId]);

        header('Location: /workflows');
    }

    public function config(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $pdo = Database::connection();
        $tenantId = (int) $user['tenant_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $definitionStmt = $pdo->prepare('INSERT INTO workflow_definitions (tenant_id, name, request_type, is_active, created_at, updated_at)
                VALUES (:tenant_id, :name, :request_type, 1, NOW(), NOW())');
            $definitionStmt->execute([
                'tenant_id' => $tenantId,
                'name' => $_POST['name'] ?? 'Fluxo sem nome',
                'request_type' => $_POST['request_type'] ?? 'team_swap',
            ]);
            $definitionId = (int) $pdo->lastInsertId();

            $conditionStmt = $pdo->prepare('INSERT INTO workflow_conditions
                (workflow_definition_id, min_value, max_value, unit_id, team_id, urgency, created_at, updated_at)
                VALUES (:workflow_definition_id, :min_value, :max_value, :unit_id, :team_id, :urgency, NOW(), NOW())');
            $conditionStmt->execute([
                'workflow_definition_id' => $definitionId,
                'min_value' => $_POST['min_value'] !== '' ? (float) $_POST['min_value'] : null,
                'max_value' => $_POST['max_value'] !== '' ? (float) $_POST['max_value'] : null,
                'unit_id' => $_POST['unit_id'] !== '' ? (int) $_POST['unit_id'] : null,
                'team_id' => $_POST['team_id'] !== '' ? (int) $_POST['team_id'] : null,
                'urgency' => $_POST['urgency'] !== '' ? $_POST['urgency'] : null,
            ]);

            $stepsRaw = array_filter(array_map('trim', explode("\n", $_POST['steps'] ?? '')));
            foreach ($stepsRaw as $index => $rawStep) {
                [$stepName, $slaHours, $approvers] = array_pad(array_map('trim', explode('|', $rawStep)), 3, '');
                $stepStmt = $pdo->prepare('INSERT INTO workflow_steps (workflow_definition_id, step_order, name, sla_hours, created_at, updated_at)
                    VALUES (:workflow_definition_id, :step_order, :name, :sla_hours, NOW(), NOW())');
                $stepStmt->execute([
                    'workflow_definition_id' => $definitionId,
                    'step_order' => $index + 1,
                    'name' => $stepName !== '' ? $stepName : 'Etapa ' . ($index + 1),
                    'sla_hours' => $slaHours !== '' ? (int) $slaHours : 24,
                ]);
                $workflowStepId = (int) $pdo->lastInsertId();

                foreach (array_filter(array_map('trim', explode(',', $approvers))) as $approver) {
                    $pdo->prepare('INSERT INTO workflow_step_approvers
                        (workflow_step_id, approver_type, approver_reference, created_at, updated_at)
                        VALUES (:workflow_step_id, :approver_type, :approver_reference, NOW(), NOW())')
                        ->execute([
                            'workflow_step_id' => $workflowStepId,
                            'approver_type' => 'role',
                            'approver_reference' => $approver,
                        ]);
                }
            }

            header('Location: /workflows/config');
            return;
        }

        $definitions = $pdo->prepare('SELECT wd.*, wc.min_value, wc.max_value, wc.unit_id, wc.team_id, wc.urgency
            FROM workflow_definitions wd
            LEFT JOIN workflow_conditions wc ON wc.workflow_definition_id = wd.id
            WHERE wd.tenant_id = :tenant_id
            ORDER BY wd.id DESC');
        $definitions->execute(['tenant_id' => $tenantId]);

        $steps = $pdo->prepare('SELECT ws.*, GROUP_CONCAT(wsa.approver_reference SEPARATOR ", ") AS approvers
            FROM workflow_steps ws
            LEFT JOIN workflow_step_approvers wsa ON wsa.workflow_step_id = ws.id
            WHERE ws.workflow_definition_id IN (SELECT id FROM workflow_definitions WHERE tenant_id = :tenant_id)
            GROUP BY ws.id
            ORDER BY ws.workflow_definition_id, ws.step_order');
        $steps->execute(['tenant_id' => $tenantId]);

        $units = $pdo->prepare('SELECT id, name FROM units WHERE tenant_id = :tenant_id ORDER BY name');
        $units->execute(['tenant_id' => $tenantId]);

        $teams = $pdo->prepare('SELECT id, name FROM teams WHERE tenant_id = :tenant_id ORDER BY name');
        $teams->execute(['tenant_id' => $tenantId]);

        View::render('workflows/config', [
            'definitions' => $definitions->fetchAll(PDO::FETCH_ASSOC),
            'steps' => $steps->fetchAll(PDO::FETCH_ASSOC),
            'units' => $units->fetchAll(PDO::FETCH_ASSOC),
            'teams' => $teams->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    public function monitor(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $pdo = Database::connection();
        $tenantId = (int) $user['tenant_id'];

        $instancesStmt = $pdo->prepare("SELECT wi.id, wi.request_type, wi.status, wi.current_step_order, wi.started_at, wi.finished_at,
                wd.name AS workflow_name,
                active_step.status AS current_step_status,
                active_step.sla_deadline_at,
                TIMESTAMPDIFF(MINUTE, wi.started_at, COALESCE(wi.finished_at, NOW())) AS total_elapsed_minutes
            FROM workflow_instances wi
            INNER JOIN workflow_definitions wd ON wd.id = wi.workflow_definition_id
            LEFT JOIN workflow_instance_steps active_step
                ON active_step.workflow_instance_id = wi.id AND active_step.step_order = wi.current_step_order
            WHERE wi.tenant_id = :tenant_id
            ORDER BY wi.id DESC");
        $instancesStmt->execute(['tenant_id' => $tenantId]);

        $stepsStmt = $pdo->prepare("SELECT wis.workflow_instance_id, wis.step_order, ws.name,
                wis.status, wis.sla_deadline_at,
                COALESCE(wis.elapsed_minutes, TIMESTAMPDIFF(MINUTE, COALESCE(wis.started_at, wis.created_at), NOW())) AS elapsed_minutes
            FROM workflow_instance_steps wis
            INNER JOIN workflow_steps ws ON ws.id = wis.workflow_step_id
            WHERE wis.workflow_instance_id IN (SELECT id FROM workflow_instances WHERE tenant_id = :tenant_id)
            ORDER BY wis.workflow_instance_id DESC, wis.step_order ASC");
        $stepsStmt->execute(['tenant_id' => $tenantId]);

        View::render('workflows/monitor', [
            'instances' => $instancesStmt->fetchAll(PDO::FETCH_ASSOC),
            'steps' => $stepsStmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    private function resolveWorkflowDefinition(
        int $tenantId,
        string $requestType,
        ?float $amount,
        ?int $unitId,
        ?int $teamId,
        string $urgency
    ): ?int {
        $sql = 'SELECT wd.id
            FROM workflow_definitions wd
            LEFT JOIN workflow_conditions wc ON wc.workflow_definition_id = wd.id
            WHERE wd.tenant_id = :tenant_id
              AND wd.request_type = :request_type
              AND wd.is_active = 1
              AND (wc.min_value IS NULL OR :amount >= wc.min_value)
              AND (wc.max_value IS NULL OR :amount <= wc.max_value)
              AND (wc.unit_id IS NULL OR wc.unit_id = :unit_id)
              AND (wc.team_id IS NULL OR wc.team_id = :team_id)
              AND (wc.urgency IS NULL OR wc.urgency = :urgency)
            ORDER BY wd.id ASC
            LIMIT 1';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => $tenantId,
            'request_type' => $requestType,
            'amount' => $amount ?? 0,
            'unit_id' => $unitId ?? 0,
            'team_id' => $teamId ?? 0,
            'urgency' => $urgency,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return (int) $row['id'];
    }
}
