<div class="panel-card p-3">
    <h3>Relatórios Operacionais</h3>
    <?php $currentMode = $mode ?? 'schedules'; ?>
    <form class="row g-3" method="get" action="/reports">
        <div class="col-md-2">
            <select class="form-select" name="mode">
                <option value="schedules" <?= $currentMode === 'schedules' ? 'selected' : '' ?>>Escalas</option>
                <option value="logistics" <?= $currentMode === 'logistics' ? 'selected' : '' ?>>Logística</option>
            </select>
        </div>
        <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>"></div>
        <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Filtrar</button></div>
        <div class="col-md-2"><a class="btn btn-outline-secondary" href="/reports?mode=<?= urlencode($currentMode) ?>&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv">CSV</a></div>
    </form>

    <?php if ($currentMode === 'schedules'): ?>
        <div class="mt-3"><a class="btn btn-outline-light" href="/reports?mode=schedules&from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=pdf">PDF</a></div>
        <table class="table table-dark-custom mt-3">
            <thead><tr><th>ID</th><th>Colaborador</th><th>Data</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr><td><?= (int) $row['id'] ?></td><td><?= htmlspecialchars($row['employee_name']) ?></td><td><?= htmlspecialchars($row['schedule_date']) ?></td><td><span class="badge text-bg-secondary badge-status"><?= htmlspecialchars($row['status']) ?></span></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?php $metrics = $kpi ?? ['total_itineraries' => 0, 'delay_rate' => 0, 'cancel_rate' => 0]; ?>
        <div class="row mt-3">
            <div class="col-md-4"><div class="panel-card p-3"><small>Total itinerários</small><h4><?= (int) $metrics['total_itineraries'] ?></h4></div></div>
            <div class="col-md-4"><div class="panel-card p-3"><small>Taxa de atraso</small><h4><?= number_format((float) $metrics['delay_rate'], 2, ',', '.') ?>%</h4></div></div>
            <div class="col-md-4"><div class="panel-card p-3"><small>Taxa de cancelamento</small><h4><?= number_format((float) $metrics['cancel_rate'], 2, ',', '.') ?>%</h4></div></div>
        </div>

        <table class="table table-dark-custom mt-3">
            <thead><tr><th>Unidade</th><th>Equipe</th><th>Solicitações</th><th>Custo total (BRL)</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $row['unit_name']) ?></td>
                    <td><?= htmlspecialchars((string) $row['team_name']) ?></td>
                    <td><?= (int) $row['requests'] ?></td>
                    <td>R$ <?= number_format((float) $row['total_cost'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
