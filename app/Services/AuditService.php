<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditService
{
    public function log(int $tenantId, int $userId, string $table, string $action, ?array $before, ?array $after): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_logs (tenant_id, user_id, resource, action, before_data, after_data, created_at)
             VALUES (:tenant_id, :user_id, :resource, :action, :before_data, :after_data, :created_at)'
        );

        $stmt->execute([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'resource' => $table,
            'action' => $action,
            'before_data' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
            'after_data' => $after ? json_encode($after, JSON_THROW_ON_ERROR) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
