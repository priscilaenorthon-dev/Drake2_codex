<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class ReportController
{
    public function index(): void
    {
        $user = Auth::user();
        $tenantId = (int) $user['tenant_id'];

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $unitId = $_GET['unit_id'] ?? null;
        $mode = $_GET['mode'] ?? 'schedules';

        if ($mode === 'logistics') {
            $this->logisticsReport($tenantId, $from, $to, $unitId);
            return;
        }

        $sql = 'SELECT s.*, e.name employee_name FROM schedules s
                JOIN employees e ON e.id = s.employee_id
                WHERE s.tenant_id = :tenant AND s.schedule_date BETWEEN :from AND :to';
        $params = ['tenant' => $tenantId, 'from' => $from, 'to' => $to];

        if ($unitId) {
            $sql .= ' AND s.unit_id = :unit_id';
            $params['unit_id'] = $unitId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        if (($_GET['export'] ?? '') === 'pdf') {
            header('Content-Type: text/html; charset=utf-8');
            echo '<html><head><title>Relatório de Escalas</title><style>body{font-family:Arial}table{width:100%;border-collapse:collapse}th,td{border:1px solid #bbb;padding:6px}h2{margin-bottom:16px}</style></head><body>';
            echo '<h2>Relatório de Escalas</h2>';
            echo '<p>Período: ' . htmlspecialchars($from) . ' até ' . htmlspecialchars($to) . '</p>';
            echo '<table><tr><th>ID</th><th>Colaborador</th><th>Data</th><th>Status</th></tr>';
            foreach ($rows as $row) {
                echo '<tr><td>' . (int) $row['id'] . '</td><td>' . htmlspecialchars($row['employee_name']) . '</td><td>' . htmlspecialchars($row['schedule_date']) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
            }
            echo '</table><script>window.print()</script></body></html>';
            return;
        }

        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="relatorio_escalas.csv"');
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['ID', 'Colaborador', 'Data', 'Status']);
            foreach ($rows as $row) {
                fputcsv($out, [$row['id'], $row['employee_name'], $row['schedule_date'], $row['status']]);
            }
            fclose($out);
            return;
        }

        View::render('dashboard/reports', ['rows' => $rows, 'from' => $from, 'to' => $to, 'mode' => 'schedules']);
    }

    private function logisticsReport(int $tenantId, string $from, string $to, ?string $unitId): void
    {
        $pdo = Database::connection();
        $costSql = 'SELECT
                COALESCE(lc.unit_id, 0) unit_id,
                COALESCE(u.name, "Sem unidade") unit_name,
                COALESCE(lc.team_id, 0) team_id,
                COALESCE(t.name, "Sem equipe") team_name,
                SUM(lc.amount) total_cost,
                COUNT(DISTINCT lc.logistics_request_id) requests
            FROM logistics_costs lc
            LEFT JOIN units u ON u.id = lc.unit_id
            LEFT JOIN teams t ON t.id = lc.team_id
            WHERE lc.tenant_id = :tenant AND lc.cost_date BETWEEN :from AND :to';

        $params = ['tenant' => $tenantId, 'from' => $from, 'to' => $to];
        if ($unitId) {
            $costSql .= ' AND lc.unit_id = :unit_id';
            $params['unit_id'] = $unitId;
        }

        $costSql .= ' GROUP BY lc.unit_id, u.name, lc.team_id, t.name ORDER BY total_cost DESC';
        $costStmt = $pdo->prepare($costSql);
        $costStmt->execute($params);
        $costRows = $costStmt->fetchAll();

        $kpiSql = 'SELECT
                COUNT(*) total_itineraries,
                SUM(CASE WHEN status = "cancelado" THEN 1 ELSE 0 END) cancelled_count,
                SUM(CASE WHEN delay_minutes > 0 THEN 1 ELSE 0 END) delayed_count
            FROM travel_itineraries
            WHERE tenant_id = :tenant AND departure_at BETWEEN :from_dt AND :to_dt';
        $kpiStmt = $pdo->prepare($kpiSql);
        $kpiStmt->execute([
            'tenant' => $tenantId,
            'from_dt' => $from . ' 00:00:00',
            'to_dt' => $to . ' 23:59:59',
        ]);
        $kpi = $kpiStmt->fetch() ?: ['total_itineraries' => 0, 'cancelled_count' => 0, 'delayed_count' => 0];

        $total = max((int) $kpi['total_itineraries'], 1);
        $kpi['delay_rate'] = round(((int) $kpi['delayed_count'] * 100) / $total, 2);
        $kpi['cancel_rate'] = round(((int) $kpi['cancelled_count'] * 100) / $total, 2);

        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="relatorio_logistico.csv"');
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['Unidade', 'Equipe', 'Solicitacoes', 'Custo total (BRL)']);
            foreach ($costRows as $row) {
                fputcsv($out, [$row['unit_name'], $row['team_name'], $row['requests'], $row['total_cost']]);
            }
            fclose($out);
            return;
        }

        View::render('dashboard/reports', [
            'rows' => $costRows,
            'from' => $from,
            'to' => $to,
            'mode' => 'logistics',
            'kpi' => $kpi,
        ]);
    }
}
