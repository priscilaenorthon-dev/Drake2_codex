<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class TenantContext
{
    public static function tenantId(): int
    {
        $user = Auth::user();
        $tenantId = isset($user['tenant_id']) ? (int) $user['tenant_id'] : 0;

        if ($tenantId < 1) {
            throw new RuntimeException('Tenant context is required.');
        }

        return $tenantId;
    }
}
