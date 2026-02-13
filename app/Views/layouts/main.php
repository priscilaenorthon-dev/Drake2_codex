<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drake2 Operations Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/drake.css" rel="stylesheet">
</head>
<body>
<?php if (str_contains($templatePath, 'auth/login.php')): ?>
<div class="container py-5">
    <?php require $templatePath; ?>
</div>
<?php else: ?>
<div class="container-fluid">
    <div class="row">
        <aside class="col-lg-2 col-md-3 p-3 sidebar">
            <div class="brand mb-4">DRAKE2 | OPS</div>
            <nav class="nav flex-column gap-1">
                <a class="nav-link" href="/dashboard">Painel</a>
                <a class="nav-link" href="/crud?module=schedules">Escalas</a>
                <a class="nav-link" href="/crud?module=trainings">Compliance</a>
                <a class="nav-link" href="/crud?module=logistics_requests">Logística</a>
                <a class="nav-link" href="/crud?module=vacation_requests">RH</a>
                <a class="nav-link" href="/crud?module=operations_records">Operações</a>
                <a class="nav-link" href="/workflows">Workflows</a>
                <a class="nav-link" href="/workflows/config">Configuração de Fluxos</a>
                <a class="nav-link" href="/workflows/monitor">SLA Workflows</a>
                <a class="nav-link" href="/reports">Relatórios</a>
                <a class="nav-link text-danger" href="/logout">Sair</a>
            </nav>
        </aside>
        <main class="col-lg-10 col-md-9 p-4">
            <div class="topbar p-3 mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <strong>Centro de Controle Operacional</strong>
                    <div class="small text-secondary">Escalas, Compliance, Logística, RH e Operações</div>
                </div>
                <span class="badge text-bg-success">Online</span>
            </div>
            <?php require $templatePath; ?>
        </main>
    </div>
</div>
<?php endif; ?>
</body>
</html>
