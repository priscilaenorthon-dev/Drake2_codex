<div class="panel-card p-3">
    <h3>Monitoramento de SLA por Etapa</h3>
    <p class="text-secondary">Acompanhe tempo total por instância e estouro de prazo por etapa.</p>

    <table class="table table-dark-custom mt-3">
        <thead><tr><th>Instância</th><th>Fluxo</th><th>Tipo</th><th>Status</th><th>Tempo total (min)</th><th>Etapas</th></tr></thead>
        <tbody>
        <?php foreach ($instances as $instance): ?>
            <tr>
                <td>#<?= (int) $instance['id'] ?></td>
                <td><?= htmlspecialchars($instance['workflow_name']) ?></td>
                <td><?= htmlspecialchars($instance['request_type']) ?></td>
                <td><?= htmlspecialchars($instance['status']) ?></td>
                <td><?= (int) $instance['total_elapsed_minutes'] ?></td>
                <td>
                    <?php foreach ($steps as $step): ?>
                        <?php if ((int) $step['workflow_instance_id'] === (int) $instance['id']): ?>
                            <?php
                                $late = false;
                                if (!empty($step['sla_deadline_at']) && in_array($step['status'], ['in_progress', 'pending'], true)) {
                                    $late = strtotime((string) $step['sla_deadline_at']) < time();
                                }
                            ?>
                            <div>
                                <strong>#<?= (int) $step['step_order'] ?> <?= htmlspecialchars($step['name']) ?></strong>
                                - <?= htmlspecialchars($step['status']) ?>
                                - <?= (int) $step['elapsed_minutes'] ?> min
                                <?= $late ? '<span class="badge text-bg-danger">SLA estourado</span>' : '' ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
