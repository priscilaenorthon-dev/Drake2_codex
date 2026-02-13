<div class="panel-card p-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0"><?= htmlspecialchars($meta['title']) ?></h3>
            <small class="text-secondary"><?= htmlspecialchars($meta['description'] ?? '') ?></small>
        </div>
        <a href="/crud/create?module=<?= urlencode($module) ?>" class="btn btn-success">Novo</a>
    </div>

    <table class="table table-dark-custom mt-3">
        <thead>
        <tr>
            <?php if (!empty($records)): foreach (array_keys($records[0]) as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; endif; ?>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($records as $record): ?>
            <tr>
                <?php foreach ($record as $value): ?><td><?= htmlspecialchars((string) $value) ?></td><?php endforeach; ?>
                <td><a href="/crud/delete?module=<?= urlencode($module) ?>&id=<?= (int) $record['id'] ?>" class="btn btn-sm btn-outline-danger">Excluir</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
