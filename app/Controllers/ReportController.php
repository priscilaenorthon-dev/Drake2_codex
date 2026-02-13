<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\TenantContext;
use App\Core\View;

final class ReportController
{
    public function index(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $tenantId = TenantContext::tenantId();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $unitId = $_GET['unit_id'] ?? null;

        $sql = 'SELECT s.*, e.name employee_name FROM schedules s
                JOIN employees e ON e.id = s.employee_id AND e.tenant_id = s.tenant_id
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

        View::render('dashboard/reports', ['rows' => $rows, 'from' => $from, 'to' => $to]);
    }
}
