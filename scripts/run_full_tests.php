<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

use App\Services\LogisticsService;
use App\Services\PermissionService;
use App\Services\ScheduleService;
use App\Services\TrainingService;

$pass = 0;
$fail = 0;

$assert = static function (bool $condition, string $message) use (&$pass, &$fail): void {
    if ($condition) {
        $pass++;
        return;
    }

    $fail++;
    echo "[FAIL] {$message}\n";
};

$trainingService = new TrainingService();
$assert($trainingService->expiringWithin30Days(date('Y-m-d', strtotime('+5 days'))), 'Treinamento deveria vencer em 30 dias.');
$assert(!$trainingService->expiringWithin30Days(date('Y-m-d', strtotime('+90 days'))), 'Treinamento não deveria vencer em 30 dias.');

$permissionService = new PermissionService();
$assert($permissionService->hasPermission(['workflow.approve'], 'workflow.approve'), 'Permissão workflow.approve deve ser reconhecida.');
$assert(!$permissionService->hasPermission(['dashboard.view'], 'workflow.approve'), 'Permissão ausente não deveria ser concedida.');

$scheduleService = new ScheduleService();
$assert($scheduleService->canConfirm([['valid_until' => date('Y-m-d', strtotime('+1 day'))]]), 'Escala com compliance válido deveria confirmar.');
$assert(!$scheduleService->canConfirm([['valid_until' => date('Y-m-d', strtotime('-1 day'))]]), 'Escala com compliance vencido deveria bloquear.');

$logisticsService = new LogisticsService();
$assert($logisticsService->canEmbark(true, true, false), 'Embarque com compliance válido deveria ser permitido.');
$assert(!$logisticsService->canEmbark(true, false, false), 'Embarque sem compliance válido deveria ser bloqueado.');
$assert(!$logisticsService->canEmbark(true, true, true), 'Embarque com impedimento deveria ser bloqueado.');

try {
    $logisticsService->assertValidTransition('solicitado', 'em_cotacao');
    $pass++;
} catch (RuntimeException) {
    $fail++;
    echo "[FAIL] Transição solicitado -> em_cotacao deveria ser válida.\n";
}

try {
    $logisticsService->assertValidTransition('solicitado', 'embarcado');
    $fail++;
    echo "[FAIL] Transição solicitado -> embarcado deveria falhar.\n";
} catch (RuntimeException) {
    $pass++;
}

$migration = file_get_contents(__DIR__ . '/../database/migrations/002_expand_logistics_model.sql') ?: '';
foreach (['logistics_rights', 'travel_itineraries', 'travel_legs', 'logistics_documents', 'logistics_costs', 'logistics_status_history', 'employee_impediments'] as $table) {
    $assert(str_contains($migration, "CREATE TABLE {$table}"), "Migração deve conter tabela {$table}.");
}

$workflowController = file_get_contents(__DIR__ . '/../app/Controllers/WorkflowController.php') ?: '';
$assert(str_contains($workflowController, 'transitionLogisticsStatus'), 'WorkflowController deve possuir transição de status logístico.');
$assert(str_contains($workflowController, 'compliance inválido e/ou impedimento ativo'), 'WorkflowController deve bloquear embarque sem compliance válido.');

$reportController = file_get_contents(__DIR__ . '/../app/Controllers/ReportController.php') ?: '';
$assert(str_contains($reportController, 'logisticsReport'), 'ReportController deve ter relatório logístico.');
$assert(str_contains($reportController, 'delay_rate'), 'ReportController deve calcular taxa de atraso.');
$assert(str_contains($reportController, 'cancel_rate'), 'ReportController deve calcular taxa de cancelamento.');

$routes = file_get_contents(__DIR__ . '/../routes/web.php') ?: '';
$assert(str_contains($routes, '/workflows/logistics/status'), 'Rotas devem expor endpoint de status logístico.');

echo "Passed: {$pass} | Failed: {$fail}\n";
exit($fail > 0 ? 1 : 0);
