ALTER TABLE logistics_requests
  ADD COLUMN employee_id BIGINT UNSIGNED NULL AFTER tenant_id,
  ADD COLUMN schedule_id BIGINT UNSIGNED NULL AFTER employee_id,
  ADD COLUMN unit_id BIGINT UNSIGNED NULL AFTER schedule_id,
  ADD COLUMN team_id BIGINT UNSIGNED NULL AFTER unit_id,
  ADD COLUMN operational_status VARCHAR(30) NOT NULL DEFAULT 'solicitado' AFTER status,
  ADD COLUMN requires_compliance TINYINT(1) NOT NULL DEFAULT 1 AFTER operational_status,
  ADD COLUMN requested_start DATETIME NULL AFTER requires_compliance,
  ADD COLUMN requested_end DATETIME NULL AFTER requested_start,
  ADD COLUMN embarked_at DATETIME NULL AFTER requested_end,
  ADD INDEX idx_logistics_operational_status (tenant_id, operational_status),
  ADD CONSTRAINT fk_logistics_request_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
  ADD CONSTRAINT fk_logistics_request_schedule FOREIGN KEY (schedule_id) REFERENCES schedules(id),
  ADD CONSTRAINT fk_logistics_request_unit FOREIGN KEY (unit_id) REFERENCES units(id),
  ADD CONSTRAINT fk_logistics_request_team FOREIGN KEY (team_id) REFERENCES teams(id);

CREATE TABLE logistics_rights (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Direito logístico',
  logistics_request_id BIGINT UNSIGNED NULL,
  right_type VARCHAR(50) NOT NULL DEFAULT 'geral',
  provider VARCHAR(120) NULL,
  reference_code VARCHAR(80) NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'ativo',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_logistics_rights_request (tenant_id, logistics_request_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (logistics_request_id) REFERENCES logistics_requests(id)
);

CREATE TABLE travel_itineraries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Itinerário',
  logistics_request_id BIGINT UNSIGNED NULL,
  origin VARCHAR(120) NOT NULL DEFAULT 'A definir',
  destination VARCHAR(120) NOT NULL DEFAULT 'A definir',
  departure_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  arrival_at DATETIME NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'solicitado',
  delay_minutes INT NOT NULL DEFAULT 0,
  cancellation_reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_itineraries_period (tenant_id, departure_at),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (logistics_request_id) REFERENCES logistics_requests(id)
);

CREATE TABLE travel_legs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Trecho',
  itinerary_id BIGINT UNSIGNED NULL,
  leg_order INT NOT NULL DEFAULT 1,
  transport_mode VARCHAR(30) NOT NULL DEFAULT 'a_definir',
  carrier VARCHAR(120) NULL,
  origin VARCHAR(120) NOT NULL DEFAULT 'A definir',
  destination VARCHAR(120) NOT NULL DEFAULT 'A definir',
  departure_at DATETIME NULL,
  arrival_at DATETIME NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'solicitado',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_travel_legs_itinerary (tenant_id, itinerary_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (itinerary_id) REFERENCES travel_itineraries(id)
);

CREATE TABLE logistics_documents (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Documento',
  logistics_request_id BIGINT UNSIGNED NULL,
  document_type VARCHAR(60) NOT NULL DEFAULT 'geral',
  file_path VARCHAR(255) NOT NULL DEFAULT '/uploads/pending.pdf',
  status VARCHAR(30) NOT NULL DEFAULT 'pendente',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_logistics_documents_request (tenant_id, logistics_request_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (logistics_request_id) REFERENCES logistics_requests(id)
);

CREATE TABLE logistics_costs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Custo logístico',
  logistics_request_id BIGINT UNSIGNED NULL,
  unit_id BIGINT UNSIGNED NULL,
  team_id BIGINT UNSIGNED NULL,
  cost_type VARCHAR(60) NOT NULL DEFAULT 'geral',
  amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
  currency CHAR(3) NOT NULL DEFAULT 'BRL',
  cost_date DATE NOT NULL DEFAULT (CURRENT_DATE),
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_logistics_costs_period (tenant_id, cost_date),
  INDEX idx_logistics_costs_unit_team (tenant_id, unit_id, team_id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (logistics_request_id) REFERENCES logistics_requests(id),
  FOREIGN KEY (unit_id) REFERENCES units(id),
  FOREIGN KEY (team_id) REFERENCES teams(id)
);

CREATE TABLE logistics_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  logistics_request_id BIGINT UNSIGNED NOT NULL,
  changed_by_user_id BIGINT UNSIGNED NOT NULL,
  from_status VARCHAR(30) NULL,
  to_status VARCHAR(30) NOT NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_logistics_status_history (tenant_id, logistics_request_id, created_at),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (logistics_request_id) REFERENCES logistics_requests(id),
  FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
);

CREATE TABLE employee_impediments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL DEFAULT 'Impedimento',
  employee_id BIGINT UNSIGNED NULL,
  impediment_type VARCHAR(60) NOT NULL DEFAULT 'geral',
  reason VARCHAR(255) NOT NULL DEFAULT 'A definir',
  starts_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ends_at DATETIME NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_employee_impediments_status (tenant_id, employee_id, status),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (employee_id) REFERENCES employees(id)
);
