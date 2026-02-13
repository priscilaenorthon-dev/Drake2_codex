<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditService
{
    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function logEvent(
        int $tenantId,
        int $actorId,
        string $resource,
        string $action,
        ?array $before = null,
        ?array $after = null,
        ?string $correlationId = null
    ): void {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $resolvedCorrelationId = $correlationId
            ?? $_SERVER['HTTP_X_CORRELATION_ID']
            ?? $_SERVER['HTTP_X_REQUEST_ID']
            ?? null;

        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_logs (
                tenant_id,
                actor_id,
                resource,
                action,
                before_data,
                after_data,
                ip_address,
                user_agent,
                correlation_id,
                created_at
            )
            VALUES (
                :tenant_id,
                :actor_id,
                :resource,
                :action,
                :before_data,
                :after_data,
                :ip_address,
                :user_agent,
                :correlation_id,
                NOW()
            )'
        );

        $stmt->execute([
            'tenant_id' => $tenantId,
            'actor_id' => $actorId,
            'resource' => $resource,
            'action' => $action,
            'before_data' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
            'after_data' => $after ? json_encode($after, JSON_THROW_ON_ERROR) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'correlation_id' => $resolvedCorrelationId,
        ]);
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function log(int $tenantId, int $userId, string $table, string $action, ?array $before, ?array $after): void
    {
        $this->logEvent($tenantId, $userId, $table, $action, $before, $after);
    }
}
