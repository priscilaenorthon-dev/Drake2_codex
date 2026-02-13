<div class="panel-card p-3">
    <h3>Configuração de Workflow por Tenant</h3>
    <p class="text-secondary">Defina regras por tipo, valor, unidade, equipe e urgência.</p>

    <form method="post" action="/workflows/config" class="mb-4">
        <div class="row g-3">
            <div class="col-md-4"><input class="form-control" name="name" placeholder="Nome do fluxo" required></div>
            <div class="col-md-2">
                <select class="form-select" name="request_type" required>
                    <option value="travel">Viagem</option>
                    <option value="purchase">Compra</option>
                    <option value="vacation">Férias</option>
                    <option value="team_swap">Troca de turma</option>
                </select>
            </div>
            <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="min_value" placeholder="Valor mín."></div>
            <div class="col-md-2"><input class="form-control" type="number" step="0.01" name="max_value" placeholder="Valor máx."></div>
            <div class="col-md-1">
                <select class="form-select" name="urgency">
                    <option value="">Urgência</option>
                    <option value="low">Baixa</option>
                    <option value="normal">Normal</option>
                    <option value="high">Alta</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="unit_id">
                    <option value="">Unidade</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= (int) $unit['id'] ?>"><?= htmlspecialchars($unit['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="team_id">
                    <option value="">Equipe</option>
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= (int) $team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <textarea class="form-control" name="steps" rows="3" required placeholder="Formato: Nome da etapa|SLA horas|Aprovador 1,Aprovador 2&#10;Ex: Validação gestor|8|Gestor Unidade"></textarea>
            </div>
        </div>
        <button class="btn btn-primary mt-3">Salvar configuração</button>
    </form>

    <table class="table table-dark-custom mt-3">
        <thead><tr><th>Fluxo</th><th>Tipo</th><th>Regra</th><th>Etapas</th></tr></thead>
        <tbody>
        <?php foreach ($definitions as $definition): ?>
            <tr>
                <td><?= htmlspecialchars($definition['name']) ?></td>
                <td><?= htmlspecialchars($definition['request_type']) ?></td>
                <td>
                    valor: <?= htmlspecialchars((string) ($definition['min_value'] ?? '-')) ?> - <?= htmlspecialchars((string) ($definition['max_value'] ?? '-')) ?><br>
                    unidade: <?= htmlspecialchars((string) ($definition['unit_id'] ?? '-')) ?> |
                    equipe: <?= htmlspecialchars((string) ($definition['team_id'] ?? '-')) ?> |
                    urgência: <?= htmlspecialchars((string) ($definition['urgency'] ?? '-')) ?>
                </td>
                <td>
                    <?php foreach ($steps as $step): ?>
                        <?php if ((int) $step['workflow_definition_id'] === (int) $definition['id']): ?>
                            <div>#<?= (int) $step['step_order'] ?> <?= htmlspecialchars($step['name']) ?> (<?= (int) $step['sla_hours'] ?>h) — <?= htmlspecialchars((string) $step['approvers']) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
