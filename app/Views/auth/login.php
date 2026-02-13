<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="panel-card p-4">
            <div class="text-center mb-3">
                <h4 class="mb-0">DRAKE2 | Ops Center</h4>
                <small class="text-secondary">Acesso seguro multiempresa</small>
            </div>
            <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post" action="/login">
                <div class="mb-3"><input class="form-control" type="email" name="email" placeholder="E-mail corporativo" required></div>
                <div class="mb-3"><input class="form-control" type="password" name="password" placeholder="Senha" required></div>
                <button class="btn btn-success w-100" type="submit">Entrar no Centro de Operações</button>
            </form>
        </div>
    </div>
</div>
