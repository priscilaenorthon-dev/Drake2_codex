<div class="panel-card p-3">
    <h3>Relatório de Auditoria</h3>
    <form class="row g-2" method="get" action="/reports/audit">
        <div class="col-md-2"><input type="date" class="form-control" name="from" value="<?= htmlspecialchars($filters['from']) ?>"></div>
        <div class="col-md-2"><input type="date" class="form-control" name="to" value="<?= htmlspecialchars($filters['to']) ?>"></div>
        <div class="col-md-3">
            <select class="form-select" name="actor_id">
                <option value="">Todos os usuários</option>
                <?php foreach ($actors as $actor): ?>
                    <option value="<?= (int) $actor['id'] ?>" <?= (string) $filters['actor_id'] === (string) $actor['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($actor['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><input type="text" class="form-control" name="resource" placeholder="Recurso" value="<?= htmlspecialchars($filters['resource']) ?>"></div>
        <div class="col-md-2"><input type="text" class="form-control" name="action" placeholder="Ação" value="<?= htmlspecialchars($filters['action']) ?>"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100" type="submit">Filtrar</button></div>
    </form>

    <table class="table table-dark-custom mt-3">
        <thead>
        <tr>
            <th>Data</th>
            <th>Usuário</th>
            <th>Recurso</th>
            <th>Ação</th>
            <th>IP</th>
            <th>Correlation ID</th>
            <th>Before/After</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td><?= htmlspecialchars($row['actor_name']) ?></td>
                <td><?= htmlspecialchars($row['resource']) ?></td>
                <td><span class="badge text-bg-secondary"><?= htmlspecialchars($row['action']) ?></span></td>
                <td><?= htmlspecialchars((string) ($row['ip_address'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string) ($row['correlation_id'] ?? '-')) ?></td>
                <td>
                    <small><strong>Before:</strong> <?= htmlspecialchars((string) ($row['before_data'] ?? '{}')) ?></small><br>
                    <small><strong>After:</strong> <?= htmlspecialchars((string) ($row['after_data'] ?? '{}')) ?></small>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
