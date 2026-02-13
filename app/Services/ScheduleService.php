<?php

declare(strict_types=1);

namespace App\Services;

final class ScheduleService
{
    public function canConfirm(array $employeeTrainingRows): bool
    {
        foreach ($employeeTrainingRows as $row) {
            if (strtotime($row['valid_until']) < strtotime('today')) {
                return false;
            }
        }

        return true;
    }
}
