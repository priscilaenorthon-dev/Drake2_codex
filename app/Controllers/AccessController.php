<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\AccessRepository;

final class AccessController
{
    public function index(): void
    {
        $user = Auth::user();
        if (!$user) {
            http_response_code(403);
            echo 'Acesso negado';
            return;
        }

        $repo = new AccessRepository();
        $tenantId = (int) $user['tenant_id'];

        $roles = $repo->allRolesByTenant($tenantId);
        $permissions = $repo->allPermissions();

        View::render('access/index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function storeRole(): void
    {
        $user = Auth::user();
        $name = trim((string) ($_POST['name'] ?? ''));

        if (!$user || $name === '') {
            http_response_code(422);
            return;
        }

        (new AccessRepository())->createRole((int) $user['tenant_id'], $name);
        header('Location: /access');
    }

    public function updateRole(): void
    {
        $user = Auth::user();
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if (!$user || $roleId < 1 || $name === '') {
            http_response_code(422);
            return;
        }

        (new AccessRepository())->updateRoleName((int) $user['tenant_id'], $roleId, $name);
        header('Location: /access');
    }

    public function deleteRole(): void
    {
        $user = Auth::user();
        $roleId = (int) ($_POST['role_id'] ?? 0);

        if (!$user || $roleId < 1) {
            http_response_code(422);
            return;
        }

        (new AccessRepository())->deleteRole((int) $user['tenant_id'], $roleId);
        header('Location: /access');
    }

    public function syncRolePermissions(): void
    {
        $user = Auth::user();
        $roleId = (int) ($_POST['role_id'] ?? 0);

        if (!$user || $roleId < 1) {
            http_response_code(422);
            return;
        }

        $permissionIds = array_map('intval', (array) ($_POST['permission_ids'] ?? []));
        (new AccessRepository())->syncRolePermissions((int) $user['tenant_id'], $roleId, array_values(array_unique($permissionIds)));

        header('Location: /access');
    }
}
