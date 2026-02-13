<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
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

        header('Content-Type: application/json');
        echo json_encode((new BaseRepository())->allByTenant('schedules', (int) $user['tenant_id']));
    }

    public function trainingsExpiring(): void
    {
        $user = Auth::user();
        $repo = new BaseRepository();
        $trainings = $repo->allByTenant('employee_trainings', (int) $user['tenant_id']);
        $filtered = array_values(array_filter($trainings, static fn(array $item): bool => strtotime($item['valid_until']) <= strtotime('+30 days')));

        header('Content-Type: application/json');
        echo json_encode($filtered);
    }
}
