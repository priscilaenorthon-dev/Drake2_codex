<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Services\AuditService;
use App\Repositories\BaseRepository;

final class ApiController
{
    public function schedules(): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Não autenticado']);
            return;
        }

        $tenantId = (int) $user['tenant_id'];
        $payload = (new BaseRepository())->allByTenant('schedules', $tenantId);

        (new AuditService())->logEvent($tenantId, (int) $user['id'], 'api.schedules', 'read', null, ['count' => count($payload)]);

        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    public function trainingsExpiring(): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Não autenticado']);
            return;
        }

        $repo = new BaseRepository();
        $trainings = $repo->allByTenant('employee_trainings', (int) $user['tenant_id']);
        $filtered = array_values(array_filter($trainings, static fn(array $item): bool => strtotime($item['valid_until']) <= strtotime('+30 days')));

        (new AuditService())->logEvent((int) $user['tenant_id'], (int) $user['id'], 'api.trainings_expiring', 'read', null, ['count' => count($filtered)]);

        header('Content-Type: application/json');
        echo json_encode($filtered);
    }

    public function assignRole(): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Não autenticado']);
            return;
        }

        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $roleId = (int) ($_POST['role_id'] ?? 0);

        if ($targetUserId < 1 || $roleId < 1) {
            http_response_code(422);
            echo json_encode(['message' => 'Payload inválido']);
            return;
        }

        $stmt = Database::connection()->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
        $stmt->execute(['user_id' => $targetUserId, 'role_id' => $roleId]);

        (new AuditService())->logEvent(
            (int) $user['tenant_id'],
            (int) $user['id'],
            'user_roles',
            'grant_role',
            null,
            ['user_id' => $targetUserId, 'role_id' => $roleId]
        );

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function assignPermission(): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Não autenticado']);
            return;
        }

        $roleId = (int) ($_POST['role_id'] ?? 0);
        $permissionId = (int) ($_POST['permission_id'] ?? 0);

        if ($roleId < 1 || $permissionId < 1) {
            http_response_code(422);
            echo json_encode(['message' => 'Payload inválido']);
            return;
        }

        $stmt = Database::connection()->prepare('INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
        $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);

        (new AuditService())->logEvent(
            (int) $user['tenant_id'],
            (int) $user['id'],
            'role_permissions',
            'grant_permission',
            null,
            ['role_id' => $roleId, 'permission_id' => $permissionId]
        );

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }
}
