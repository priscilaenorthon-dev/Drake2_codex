<?php

declare(strict_types=1);

namespace App\Services;

final class PermissionService
{
    public function hasPermission(array $permissions, string $required): bool
    {
        return in_array($required, $permissions, true);
    }
}
