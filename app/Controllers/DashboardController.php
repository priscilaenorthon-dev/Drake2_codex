<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $pdo = Database::connection();
        $tenantId = (int) $user['tenant_id'];

        $metrics = [
            'pendencias_aprovacao' => (int) $pdo->query("SELECT COUNT(*) FROM approval_requests WHERE tenant_id = {$tenantId} AND status = 'pending'")->fetchColumn(),
            'escalas_dia' => (int) $pdo->query("SELECT COUNT(*) FROM schedules WHERE tenant_id = {$tenantId} AND schedule_date = CURDATE()")->fetchColumn(),
            'treinamentos_vencendo' => (int) $pdo->query("SELECT COUNT(*) FROM employee_trainings WHERE tenant_id = {$tenantId} AND valid_until <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
            'solicitacoes_logistica' => (int) $pdo->query("SELECT COUNT(*) FROM logistics_requests WHERE tenant_id = {$tenantId} AND status = 'pending'")->fetchColumn(),
        ];

        View::render('dashboard/index', ['metrics' => $metrics, 'user' => $user]);
    }
}
