<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\AuditService;

final class ReportController
{
    public function index(): void
    {
        $user = Auth::user();
        $tenantId = (int) $user['tenant_id'];

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $unitId = $_GET['unit_id'] ?? null;

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

        (new AuditService())->logEvent(
            $tenantId,
            (int) $user['id'],
            'reports.schedules',
            'view',
            null,
            ['from' => $from, 'to' => $to, 'unit_id' => $unitId, 'export' => $_GET['export'] ?? null]
        );

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

    public function audit(): void
    {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            return;
        }

        $tenantId = (int) $user['tenant_id'];
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-t');
        $actorId = $_GET['actor_id'] ?? '';
        $resource = trim((string) ($_GET['resource'] ?? ''));
        $action = trim((string) ($_GET['action'] ?? ''));

        $sql = 'SELECT a.*, u.name AS actor_name
                FROM audit_logs a
                JOIN users u ON u.id = a.actor_id
                WHERE a.tenant_id = :tenant_id
                AND a.created_at BETWEEN :from_date AND :to_date';
        $params = [
            'tenant_id' => $tenantId,
            'from_date' => $from . ' 00:00:00',
            'to_date' => $to . ' 23:59:59',
        ];

        if ($actorId !== '') {
            $sql .= ' AND a.actor_id = :actor_id';
            $params['actor_id'] = (int) $actorId;
        }
        if ($resource !== '') {
            $sql .= ' AND a.resource = :resource';
            $params['resource'] = $resource;
        }
        if ($action !== '') {
            $sql .= ' AND a.action = :action';
            $params['action'] = $action;
        }

        $sql .= ' ORDER BY a.id DESC LIMIT 300';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $usersStmt = Database::connection()->prepare('SELECT id, name FROM users WHERE tenant_id = :tenant_id ORDER BY name ASC');
        $usersStmt->execute(['tenant_id' => $tenantId]);
        $actors = $usersStmt->fetchAll();

        (new AuditService())->logEvent(
            $tenantId,
            (int) $user['id'],
            'reports.audit',
            'view',
            null,
            ['from' => $from, 'to' => $to, 'actor_id' => $actorId, 'resource' => $resource, 'action' => $action]
        );

        View::render('dashboard/audit', [
            'rows' => $rows,
            'actors' => $actors,
            'filters' => [
                'from' => $from,
                'to' => $to,
                'actor_id' => $actorId,
                'resource' => $resource,
                'action' => $action,
            ],
        ]);
    }
}
