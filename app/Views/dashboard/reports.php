<div class="panel-card p-3">
    <h3>Relatório de Escalas</h3>
    <form class="row g-3" method="get" action="/reports">
        <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= htmlspecialchars($from) ?>"></div>
        <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= htmlspecialchars($to) ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary" type="submit">Filtrar</button></div>
        <div class="col-md-2"><a class="btn btn-outline-secondary" href="/reports?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv">CSV</a></div>
        <div class="col-md-2"><a class="btn btn-outline-light" href="/reports?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=pdf">PDF</a></div>
    </form>
    <table class="table table-dark-custom mt-3">
        <thead><tr><th>ID</th><th>Colaborador</th><th>Data</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr><td><?= (int) $row['id'] ?></td><td><?= htmlspecialchars($row['employee_name']) ?></td><td><?= htmlspecialchars($row['schedule_date']) ?></td><td><span class="badge text-bg-secondary badge-status"><?= htmlspecialchars($row['status']) ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
