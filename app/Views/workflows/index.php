<div class="panel-card p-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-0">Fluxo de Aprovações</h3>
            <small class="text-secondary">Workflows para viagens, compras, férias e trocas de escala</small>
        </div>
        <a class="btn btn-secondary" href="/workflows/team-swap">Solicitar Troca de Turma</a>
    </div>
    <table class="table table-dark-custom mt-3">
        <thead><tr><th>ID</th><th>Tipo</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $item): ?>
            <tr>
                <td><?= (int) $item['id'] ?></td>
                <td><?= htmlspecialchars($item['request_type']) ?></td>
                <td><span class="badge text-bg-warning badge-status"><?= htmlspecialchars($item['status']) ?></span></td>
                <td>
                    <a class="btn btn-sm btn-success" href="/workflows/approve?id=<?= (int) $item['id'] ?>&status=approved">Aprovar</a>
                    <a class="btn btn-sm btn-danger" href="/workflows/approve?id=<?= (int) $item['id'] ?>&status=rejected">Rejeitar</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
