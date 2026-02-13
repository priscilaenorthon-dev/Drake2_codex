<h2 class="mb-3">Painel Integrado de Operações</h2>
<div class="row g-3 mb-3">
<?php foreach ($metrics as $name => $value): ?>
    <div class="col-lg-3 col-md-6">
        <div class="panel-card p-3">
            <div class="metric-title"><?= ucwords(str_replace('_', ' ', $name)) ?></div>
            <div class="metric-value"><?= (int) $value ?></div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel-card p-3">
            <h5>Pipeline Operacional do Dia</h5>
            <div class="small text-secondary mb-2">Visão consolidada para coordenação de operações complexas</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent text-light">✅ Escalas planejadas e pendências de troca de turma</li>
                <li class="list-group-item bg-transparent text-light">⚠️ Colaboradores com qualificação próxima do vencimento</li>
                <li class="list-group-item bg-transparent text-light">🛫 Solicitações logísticas em aprovação</li>
                <li class="list-group-item bg-transparent text-light">🧾 Fechamento de timesheet e eventos de RH</li>
            </ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel-card p-3 h-100">
            <h6>Acesso rápido</h6>
            <div class="d-grid gap-2">
                <a class="btn btn-outline-light" href="/crud?module=schedules">Gestão de Escalas</a>
                <a class="btn btn-outline-light" href="/workflows">Aprovações</a>
                <a class="btn btn-outline-light" href="/crud?module=logistics_requests">Logística</a>
                <a class="btn btn-success" href="/reports">Relatórios executivos</a>
            </div>
        </div>
    </div>
</div>
