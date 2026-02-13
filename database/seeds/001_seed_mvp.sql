INSERT INTO tenants (id, name, created_at, updated_at) VALUES (1, 'Tenant Demo', NOW(), NOW());

INSERT INTO companies (tenant_id, name, status, created_at, updated_at) VALUES (1, 'Sapiensia Offshore', 'active', NOW(), NOW());
INSERT INTO units (tenant_id, company_id, name, status, created_at, updated_at) VALUES (1, 1, 'Base Rio', 'active', NOW(), NOW());
INSERT INTO teams (tenant_id, unit_id, name, status, created_at, updated_at) VALUES (1, 1, 'Equipe Alfa', 'active', NOW(), NOW());
INSERT INTO positions (tenant_id, name, status, created_at, updated_at) VALUES (1, 'Supervisor de Operações', 'active', NOW(), NOW());
INSERT INTO shifts (tenant_id, name, start_time, end_time, status, created_at, updated_at) VALUES (1, '12x36', '06:00:00', '18:00:00', 'active', NOW(), NOW());

INSERT INTO employees (tenant_id, unit_id, team_id, position_id, name, status, created_at, updated_at)
VALUES (1, 1, 1, 1, 'Maria Operadora', 'active', NOW(), NOW());

INSERT INTO users (tenant_id, name, email, password_hash, status, created_at, updated_at)
VALUES (1, 'Admin Tenant', 'admin@tenant.com', '$2y$10$Ua20pYYOPf6mbQr6ecumf.0BOwkY4Gj42bPCHNx6EerklI8ac4dBu', 'active', NOW(), NOW());

INSERT INTO roles (id, tenant_id, name, created_at, updated_at) VALUES (1, 1, 'Administrador', NOW(), NOW());
INSERT INTO permissions (id, name, created_at, updated_at) VALUES
(1, 'dashboard.view', NOW(), NOW()),
(2, 'crud.manage', NOW(), NOW()),
(3, 'workflow.approve', NOW(), NOW()),
(4, 'reports.view', NOW(), NOW());
INSERT INTO role_permissions (role_id, permission_id) VALUES (1,1),(1,2),(1,3),(1,4);
INSERT INTO user_roles (user_id, role_id) VALUES (1,1);

INSERT INTO schedules (tenant_id, employee_id, unit_id, shift_id, schedule_date, status, created_at, updated_at)
VALUES (1, 1, 1, 1, CURDATE(), 'planned', NOW(), NOW());

INSERT INTO trainings (tenant_id, name, mode, validity_days, status, created_at, updated_at)
VALUES (1, 'NR-37', 'presencial', 365, 'active', NOW(), NOW());

INSERT INTO employee_trainings (tenant_id, employee_id, training_id, valid_until, evidence_path, created_at, updated_at)
VALUES (1, 1, 1, DATE_ADD(CURDATE(), INTERVAL 15 DAY), '/docs/nr37_maria.pdf', NOW(), NOW());

INSERT INTO logistics_requests (tenant_id, name, status, created_at, updated_at)
VALUES (1, 'Embarque Base Rio -> Plataforma X', 'pending', NOW(), NOW());

INSERT INTO vacation_requests (tenant_id, name, status, created_at, updated_at)
VALUES (1, 'Férias Maria Operadora Jan/2027', 'pending', NOW(), NOW());

INSERT INTO workflow_definitions (id, tenant_id, name, request_type, is_active, created_at, updated_at)
VALUES
  (1, 1, 'Viagem urgente offshore', 'travel', 1, NOW(), NOW()),
  (2, 1, 'Compra acima de 10 mil', 'purchase', 1, NOW(), NOW()),
  (3, 1, 'Férias padrão', 'vacation', 1, NOW(), NOW()),
  (4, 1, 'Troca de turma urgente', 'team_swap', 1, NOW(), NOW());

INSERT INTO workflow_conditions (workflow_definition_id, min_value, max_value, unit_id, team_id, urgency, created_at, updated_at)
VALUES
  (1, NULL, NULL, 1, NULL, 'high', NOW(), NOW()),
  (2, 10000, NULL, NULL, NULL, NULL, NOW(), NOW()),
  (3, NULL, NULL, NULL, NULL, NULL, NOW(), NOW()),
  (4, NULL, NULL, 1, 1, 'high', NOW(), NOW());

INSERT INTO workflow_steps (id, workflow_definition_id, step_order, name, sla_hours, created_at, updated_at)
VALUES
  (1, 1, 1, 'Validação gestor de unidade', 8, NOW(), NOW()),
  (2, 1, 2, 'Aprovação coordenação logística', 12, NOW(), NOW()),
  (3, 2, 1, 'Validação solicitante + orçamento', 24, NOW(), NOW()),
  (4, 2, 2, 'Aprovação financeira', 48, NOW(), NOW()),
  (5, 3, 1, 'Aprovação liderança imediata', 24, NOW(), NOW()),
  (6, 3, 2, 'Conferência RH', 24, NOW(), NOW()),
  (7, 4, 1, 'Aprovação supervisor da equipe', 4, NOW(), NOW()),
  (8, 4, 2, 'Aprovação operações', 8, NOW(), NOW());

INSERT INTO workflow_step_approvers (workflow_step_id, approver_type, approver_reference, created_at, updated_at)
VALUES
  (1, 'role', 'Gestor Unidade', NOW(), NOW()),
  (2, 'role', 'Coordenação Logística', NOW(), NOW()),
  (3, 'role', 'Comprador', NOW(), NOW()),
  (4, 'role', 'Financeiro', NOW(), NOW()),
  (5, 'role', 'Liderança', NOW(), NOW()),
  (6, 'role', 'RH', NOW(), NOW()),
  (7, 'role', 'Supervisor', NOW(), NOW()),
  (8, 'role', 'Operações', NOW(), NOW());

INSERT INTO workflow_instances (id, tenant_id, workflow_definition_id, request_type, request_payload, status, current_step_order, started_at, finished_at, created_at, updated_at)
VALUES (1, 1, 1, 'travel', JSON_OBJECT('requester','Maria','route','Rio -> Plataforma X','amount',15000,'urgency','high'), 'in_progress', 1, NOW(), NULL, NOW(), NOW());

INSERT INTO workflow_instance_steps (workflow_instance_id, workflow_step_id, step_order, status, sla_deadline_at, started_at, acted_at, acted_by_user_id, elapsed_minutes, comments, created_at, updated_at)
VALUES
  (1, 1, 1, 'in_progress', DATE_ADD(NOW(), INTERVAL 8 HOUR), NOW(), NULL, NULL, NULL, NULL, NOW(), NOW()),
  (1, 2, 2, 'pending', DATE_ADD(NOW(), INTERVAL 20 HOUR), NULL, NULL, NULL, NULL, NULL, NOW(), NOW());

INSERT INTO approval_requests (tenant_id, workflow_instance_id, request_type, request_payload, status, created_at, updated_at)
VALUES (1, 1, 'travel', JSON_OBJECT('requester','Maria','route','Rio -> Plataforma X','amount',15000,'urgency','high'), 'in_progress', NOW(), NOW());
