<div class="panel-card p-3">
    <h3>Novo registro - <?= htmlspecialchars((string) $module) ?></h3>
    <form method="post" action="/crud/store?module=<?= urlencode((string) $module) ?>">
        <div class="mb-3">
            <label class="form-label">Nome / Título</label>
            <input class="form-control" name="name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="active">Ativo</option>
                <option value="pending">Pendente</option>
                <option value="planned">Planejado</option>
                <option value="open">Aberto</option>
            </select>
        </div>
        <button class="btn btn-success">Salvar</button>
    </form>
</div>
