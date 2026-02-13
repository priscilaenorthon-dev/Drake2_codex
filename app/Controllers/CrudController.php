<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Repositories\BaseRepository;
use App\Services\AuditService;

final class CrudController
{
    private const MODULES = [
        'companies' => ['table' => 'companies', 'title' => 'Empresas', 'description' => 'Gestão de empresas/tenants operacionais.'],
        'units' => ['table' => 'units', 'title' => 'Unidades', 'description' => 'Bases e unidades operacionais.'],
        'locations' => ['table' => 'locations', 'title' => 'Locais', 'description' => 'Locais físicos (plataformas, bases e frentes).'],
        'positions' => ['table' => 'positions', 'title' => 'Cargos', 'description' => 'Funções e hierarquias operacionais.'],
        'teams' => ['table' => 'teams', 'title' => 'Equipes', 'description' => 'Times e turmas por unidade.'],
        'shifts' => ['table' => 'shifts', 'title' => 'Turnos', 'description' => 'Definição de jornadas e turnos.'],
        'employees' => ['table' => 'employees', 'title' => 'Colaboradores', 'description' => 'Cadastro e status dos colaboradores.'],
        'schedules' => ['table' => 'schedules', 'title' => 'Escalas', 'description' => 'Planejamento, alocação e acompanhamento de escalas.'],
        'qualifications' => ['table' => 'qualifications', 'title' => 'Qualificações', 'description' => 'Matriz de qualificação por função/equipe.'],
        'trainings' => ['table' => 'trainings', 'title' => 'Treinamentos', 'description' => 'Treinamentos online/presenciais e validade.'],
        'logistics_requests' => ['table' => 'logistics_requests', 'title' => 'Solicitações Logísticas', 'description' => 'Viagens, embarques, compras e serviços.'],
        'vacation_requests' => ['table' => 'vacation_requests', 'title' => 'Férias', 'description' => 'Solicitações de férias e aprovação.'],
        'timesheets' => ['table' => 'timesheets', 'title' => 'Timesheets', 'description' => 'Apontamento e fechamento de horas.'],
        'operations_records' => ['table' => 'operations_records', 'title' => 'Operações', 'description' => 'Registro de atividades, absenteísmo e custos.'],
    ];

    public function index(): void
    {
        $user = Auth::user();
        $module = $_GET['module'] ?? '';
        if (!$user || !isset(self::MODULES[$module])) {
            http_response_code(403);
            echo 'Acesso negado';
            return;
        }

        $repository = new BaseRepository();
        $records = $repository->allByTenant(self::MODULES[$module]['table'], (int) $user['tenant_id']);
        View::render('crud/index', ['records' => $records, 'module' => $module, 'meta' => self::MODULES[$module]]);
    }

    public function create(): void
    {
        $module = $_GET['module'] ?? '';
        View::render('crud/form', ['module' => $module, 'record' => null]);
    }

    public function store(): void
    {
        $user = Auth::user();
        $module = $_GET['module'] ?? '';
        $table = self::MODULES[$module]['table'] ?? null;
        if (!$user || !$table) {
            http_response_code(422);
            return;
        }

        $payload = array_filter($_POST, static fn($k) => !in_array($k, ['id'], true), ARRAY_FILTER_USE_KEY);
        $payload['tenant_id'] = (int) $user['tenant_id'];
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');

        $repo = new BaseRepository();
        $id = $repo->create($table, $payload);

        (new AuditService())->log((int) $user['tenant_id'], (int) $user['id'], $table, 'create', null, ['id' => $id] + $payload);

        header('Location: /crud?module=' . $module);
    }

    public function delete(): void
    {
        $user = Auth::user();
        $module = $_GET['module'] ?? '';
        $id = (int) ($_GET['id'] ?? 0);
        $table = self::MODULES[$module]['table'] ?? null;

        if (!$user || !$table || $id < 1) {
            http_response_code(422);
            return;
        }

        $repo = new BaseRepository();
        $before = $repo->find($table, $id, (int) $user['tenant_id']);
        $repo->delete($table, $id, (int) $user['tenant_id']);
        (new AuditService())->log((int) $user['tenant_id'], (int) $user['id'], $table, 'delete', $before, null);

        header('Location: /crud?module=' . $module);
    }
}
