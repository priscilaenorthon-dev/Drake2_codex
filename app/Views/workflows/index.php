<div class="panel-card p-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0">Fluxo de Aprovações</h3>
            <small class="text-secondary">Workflows por tipo, valor, unidade, equipe e urgência</small>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light" href="/workflows/config">Configurar Fluxos</a>
            <a class="btn btn-outline-info" href="/workflows/monitor">Monitor SLA</a>
            <a class="btn btn-secondary" href="/workflows/team-swap">Nova Solicitação</a>
        </div>
    </div>
    <?php if (($_GET['error'] ?? '') === 'workflow_not_found'): ?>
        <div class="alert alert-warning mt-3">Nenhum workflow ativo foi encontrado para esta combinação de regras.</div>
    <?php endif; ?>
    <table class="table table-dark-custom mt-3">
        <thead><tr><th>ID</th><th>Tipo</th><th>Workflow</th><th>Etapa Atual</th><th>Prazo (SLA)</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $item): ?>
            <tr>
                <td><?= (int) $item['id'] ?></td>
                <td><?= htmlspecialchars($item['request_type']) ?></td>
                <td><?= htmlspecialchars($item['workflow_name'] ?? 'Sem configuração') ?></td>
                <td><?= htmlspecialchars($item['current_step_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars((string) ($item['sla_deadline_at'] ?? '-')) ?></td>
                <td><span class="badge text-bg-warning badge-status"><?= htmlspecialchars($item['status']) ?></span></td>
                <td>
                    <a class="btn btn-sm btn-success" href="/workflows/approve?id=<?= (int) $item['id'] ?>&status=approved">Aprovar Etapa</a>
                    <a class="btn btn-sm btn-danger" href="/workflows/approve?id=<?= (int) $item['id'] ?>&status=rejected">Rejeitar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
