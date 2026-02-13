<?php

declare(strict_types=1);

namespace App\Repositories;

final class AccessRepository extends BaseRepository
{
    public function allRolesByTenant(int $tenantId): array
    {
        $sql = 'SELECT r.id, r.name,
                       GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ", ") AS permissions
                FROM roles r
                LEFT JOIN role_permissions rp ON rp.role_id = r.id
                LEFT JOIN permissions p ON p.id = rp.permission_id
                WHERE r.tenant_id = :tenant_id
                GROUP BY r.id, r.name
                ORDER BY r.name';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);

        return $stmt->fetchAll();
    }

    public function allPermissions(): array
    {
        return $this->pdo->query('SELECT id, name FROM permissions ORDER BY name')->fetchAll();
    }

    public function createRole(int $tenantId, string $name): int
    {
        $sql = 'INSERT INTO roles (tenant_id, name, created_at, updated_at)
                VALUES (:tenant_id, :name, :created_at, :updated_at)';

        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'tenant_id' => $tenantId,
            'name' => $name,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateRoleName(int $tenantId, int $roleId, string $name): void
    {
        $sql = 'UPDATE roles SET name = :name, updated_at = :updated_at
                WHERE id = :id AND tenant_id = :tenant_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s'),
            'id' => $roleId,
            'tenant_id' => $tenantId,
        ]);
    }

    public function deleteRole(int $tenantId, int $roleId): void
    {
        $this->pdo->prepare('DELETE FROM user_roles WHERE role_id = :role_id')->execute(['role_id' => $roleId]);
        $this->pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $roleId]);

        $sql = 'DELETE FROM roles WHERE id = :id AND tenant_id = :tenant_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $roleId, 'tenant_id' => $tenantId]);
    }

    public function syncRolePermissions(int $tenantId, int $roleId, array $permissionIds): void
    {
        $roleCheck = $this->pdo->prepare('SELECT id FROM roles WHERE id = :id AND tenant_id = :tenant_id LIMIT 1');
        $roleCheck->execute(['id' => $roleId, 'tenant_id' => $tenantId]);

        if (!$roleCheck->fetch()) {
            return;
        }

        $this->pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $roleId]);

        if ($permissionIds === []) {
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
        foreach ($permissionIds as $permissionId) {
            $stmt->execute(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function rolePermissionIds(int $tenantId, int $roleId): array
    {
        $sql = 'SELECT rp.permission_id
                FROM role_permissions rp
                JOIN roles r ON r.id = rp.role_id
                WHERE rp.role_id = :role_id AND r.tenant_id = :tenant_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['role_id' => $roleId, 'tenant_id' => $tenantId]);

        return array_map(static fn(array $row): int => (int) $row['permission_id'], $stmt->fetchAll());
    }
}
