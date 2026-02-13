# Mapeamento de SQL (app/Controllers + app/Repositories)

## Controllers

- `app/Controllers/ApiController.php`
  - `BaseRepository::allByTenant('schedules', tenant_id)`
  - `BaseRepository::allByTenant('employee_trainings', tenant_id)`

- `app/Controllers/DashboardController.php`
  - `SELECT COUNT(*) FROM approval_requests WHERE tenant_id = :tenant_id AND status = :status`
  - `SELECT COUNT(*) FROM schedules WHERE tenant_id = :tenant_id AND schedule_date = :today`
  - `SELECT COUNT(*) FROM employee_trainings WHERE tenant_id = :tenant_id AND valid_until <= :limit_date`
  - `SELECT COUNT(*) FROM logistics_requests WHERE tenant_id = :tenant_id AND status = :status`

- `app/Controllers/ReportController.php`
  - `SELECT s.*, e.name employee_name FROM schedules s JOIN employees e ON e.id = s.employee_id AND e.tenant_id = s.tenant_id WHERE s.tenant_id = :tenant AND s.schedule_date BETWEEN :from AND :to [AND s.unit_id = :unit_id]`

- `app/Controllers/WorkflowController.php`
  - `SELECT * FROM approval_requests WHERE tenant_id = :tenant_id ORDER BY id DESC`
  - `UPDATE approval_requests SET status = :status, updated_at = :updated_at WHERE tenant_id = :tenant_id AND id = :id`
  - `SELECT COUNT(*) FROM employee_trainings WHERE tenant_id = :tenant_id AND employee_id = :employee_id AND valid_until < :today`
  - `INSERT INTO approval_requests (tenant_id, request_type, request_payload, status, created_at, updated_at) VALUES (:tenant_id, :request_type, :request_payload, :status, :created_at, :updated_at)`

## Repositories

- `app/Repositories/BaseRepository.php`
  - `SELECT * FROM {table} WHERE tenant_id = :tenant ORDER BY id DESC`
  - `INSERT INTO {table} ({columns}) VALUES ({params})`
  - `UPDATE {table} SET {sets} WHERE tenant_id = :tenant_id AND id = :id`
  - `SELECT * FROM {table} WHERE tenant_id = :tenant AND id = :id`
  - `DELETE FROM {table} WHERE tenant_id = :tenant AND id = :id`

- `app/Repositories/UserRepository.php`
  - `SELECT * FROM users WHERE email = :email LIMIT 1`
  - `SELECT COUNT(*) FROM user_roles ur JOIN role_permissions rp ON rp.role_id = ur.role_id JOIN permissions p ON p.id = rp.permission_id WHERE ur.user_id = :user_id AND p.name = :permission`

## Regras aplicadas

- Queries por registro individual foram padronizadas para `tenant_id + id`.
- Consultas com interpolação direta (`query("... {$tenantId} ...")`) foram substituídas por `prepare/execute` com bind.
- Contexto de tenant centralizado em `TenantContext::tenantId()`, com exceção se não houver contexto válido.
