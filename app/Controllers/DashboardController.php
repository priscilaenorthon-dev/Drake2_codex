<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\TenantContext;
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
        $tenantId = TenantContext::tenantId();

        $metrics = [
            'pendencias_aprovacao' => $this->countByTenant($pdo, 'SELECT COUNT(*) FROM approval_requests WHERE tenant_id = :tenant_id AND status = :status', ['tenant_id' => $tenantId, 'status' => 'pending']),
            'escalas_dia' => $this->countByTenant($pdo, 'SELECT COUNT(*) FROM schedules WHERE tenant_id = :tenant_id AND schedule_date = :today', ['tenant_id' => $tenantId, 'today' => date('Y-m-d')]),
            'treinamentos_vencendo' => $this->countByTenant($pdo, 'SELECT COUNT(*) FROM employee_trainings WHERE tenant_id = :tenant_id AND valid_until <= :limit_date', ['tenant_id' => $tenantId, 'limit_date' => date('Y-m-d', strtotime('+30 days'))]),
            'solicitacoes_logistica' => $this->countByTenant($pdo, 'SELECT COUNT(*) FROM logistics_requests WHERE tenant_id = :tenant_id AND status = :status', ['tenant_id' => $tenantId, 'status' => 'pending']),
        ];

        View::render('dashboard/index', ['metrics' => $metrics, 'user' => $user]);
    }

    private function countByTenant(\PDO $pdo, string $sql, array $params): int
    {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}
