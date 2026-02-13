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
(4, 'reports.view', NOW(), NOW()),
(5, 'access.manage', NOW(), NOW());
INSERT INTO role_permissions (role_id, permission_id) VALUES (1,1),(1,2),(1,3),(1,4),(1,5);
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

INSERT INTO approval_requests (tenant_id, request_type, request_payload, status, created_at, updated_at)
VALUES (1, 'travel', JSON_OBJECT('requester','Maria','route','Rio -> Plataforma X'), 'pending', NOW(), NOW());
