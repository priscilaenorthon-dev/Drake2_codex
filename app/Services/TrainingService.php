<?php

declare(strict_types=1);

namespace App\Services;

final class TrainingService
{
    public function expiringWithin30Days(string $validUntil): bool
    {
        $expiration = strtotime($validUntil);
        $today = strtotime(date('Y-m-d'));
        $limit = strtotime('+30 days', $today);

        return $expiration >= $today && $expiration <= $limit;
    }
}
