<h2 class="mb-3">Administração de Acessos</h2>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel-card p-3">
            <h5>Criar papel</h5>
            <form method="post" action="/access/roles/store" class="d-grid gap-2">
                <input class="form-control" type="text" name="name" placeholder="Nome do papel" required>
                <button class="btn btn-success" type="submit">Salvar</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel-card p-3">
            <h5>Papéis do tenant</h5>
            <table class="table table-dark table-striped align-middle">
                <thead>
                <tr>
                    <th>Papel</th>
                    <th>Permissões</th>
                    <th>Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td>
                            <form method="post" action="/access/roles/update" class="d-flex gap-2">
                                <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                                <input class="form-control form-control-sm" type="text" name="name" value="<?= htmlspecialchars($role['name']) ?>" required>
                                <button class="btn btn-sm btn-outline-light" type="submit">Renomear</button>
                            </form>
                        </td>
                        <td class="small"><?= htmlspecialchars($role['permissions'] ?? 'Sem permissões') ?></td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#role-perm-<?= (int) $role['id'] ?>">Permissões</button>
                                <form method="post" action="/access/roles/delete" onsubmit="return confirm('Excluir papel?');">
                                    <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                                    <button class="btn btn-sm btn-danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="collapse" id="role-perm-<?= (int) $role['id'] ?>">
                        <td colspan="3">
                            <form method="post" action="/access/roles/permissions" class="row g-2 align-items-center">
                                <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                                <?php $current = array_map('trim', explode(',', (string) ($role['permissions'] ?? ''))); ?>
                                <?php foreach ($permissions as $permission): ?>
                                    <div class="col-md-4">
                                        <label class="form-check-label">
                                            <input class="form-check-input me-2" type="checkbox" name="permission_ids[]" value="<?= (int) $permission['id'] ?>" <?= in_array($permission['name'], $current, true) ? 'checked' : '' ?>>
                                            <?= htmlspecialchars($permission['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-success" type="submit">Salvar permissões</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
