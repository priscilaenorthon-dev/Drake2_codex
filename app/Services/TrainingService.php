<?php

declare(strict_types=1);

namespace App\Services;

final class TrainingService
{
    public function expiringWithin30Days(string $validUntil): bool
    {
        return strtotime($validUntil) <= strtotime('+30 days');
    }
}
