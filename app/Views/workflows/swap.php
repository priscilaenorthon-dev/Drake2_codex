<h3>Nova Solicitação com Workflow</h3>
<form method="post" action="/workflows/team-swap">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="request_type" required>
                <option value="travel">Viagem</option>
                <option value="purchase">Compra</option>
                <option value="vacation">Férias</option>
                <option value="team_swap" selected>Troca de turma</option>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Valor (R$)</label><input class="form-control" type="number" step="0.01" name="amount" placeholder="0.00"></div>
        <div class="col-md-4">
            <label class="form-label">Urgência</label>
            <select class="form-select" name="urgency">
                <option value="low">Baixa</option>
                <option value="normal" selected>Normal</option>
                <option value="high">Alta</option>
            </select>
        </div>
        <div class="col-md-6"><input class="form-control" type="number" name="unit_id" placeholder="ID da unidade"></div>
        <div class="col-md-6"><input class="form-control" type="number" name="team_id" placeholder="ID da equipe"></div>
        <div class="col-md-6"><input class="form-control" name="employee_from" placeholder="Colaborador Origem" required></div>
        <div class="col-md-6"><input class="form-control" name="employee_to" placeholder="Colaborador Destino"></div>
        <div class="col-md-6"><input class="form-control" type="date" name="schedule_date"></div>
        <div class="col-md-6"><input class="form-control" name="reason" placeholder="Motivo" required></div>
    </div>
    <button class="btn btn-primary mt-3">Enviar para aprovação</button>
</form>
