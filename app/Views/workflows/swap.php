<h3>Solicitação de Troca de Turma</h3>
<form method="post" action="/workflows/team-swap">
    <div class="row g-3">
        <div class="col-md-6"><input class="form-control" name="employee_from" placeholder="Colaborador Origem" required></div>
        <div class="col-md-6"><input class="form-control" name="employee_to" placeholder="Colaborador Destino" required></div>
        <div class="col-md-6"><input class="form-control" type="date" name="schedule_date" required></div>
        <div class="col-md-6"><input class="form-control" name="reason" placeholder="Motivo" required></div>
    </div>
    <button class="btn btn-primary mt-3">Enviar para aprovação</button>
</form>
