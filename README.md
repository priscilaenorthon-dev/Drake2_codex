# Drake2 MVP (PHP 8 + MySQL 8)

Sistema web SaaS multiempresa (multi-tenant) em arquitetura MVC, inspirado no DRAKE (Sapiensia), cobrindo módulos de Escalas, Compliance, Logística, RH e Operações com RBAC, auditoria e relatórios.

## Funcionalidades do MVP

- Multiempresa com `tenant_id` em entidades de domínio.
- Autenticação segura com `password_hash` + `password_verify`.
- RBAC básico (papéis x permissões).
- Trilhas de auditoria (`audit_logs`) para criação e exclusão via CRUD.
- Módulos com CRUD:
  - Cadastros base (empresas/unidades/locais/cargos/equipes/turnos/usuários/colaboradores)
  - Escalas
  - Compliance / qualificações e treinamentos
  - Logística
  - RH
  - Operações
- Workflows:
  - Aprovações configuráveis (fila de `approval_requests`)
  - Troca de turma
  - Validação de impedimentos (treinamento vencido)
- Painel com indicadores operacionais.
- Relatórios com filtro por período e exportação CSV.
- API interna REST (endpoints iniciais para escalas e treinamentos vencendo).
- UI operacional com layout dark, sidebar e cards estilo centro de comando (inspirado no DRAKE).

## Estrutura de pastas

- `app/Controllers`: camada HTTP e fluxos.
- `app/Services`: regras de negócio.
- `app/Repositories`: acesso a dados.
- `app/Views`: interface Bootstrap.
- `database/migrations`: modelagem SQL MySQL 8.
- `database/seeds`: dados iniciais.
- `scripts`: migração e seed.
- `tests`: testes básicos.

## Instalação (Linux/XAMPP)

1. Clone e acesse o repositório.
2. Copie ambiente:
   ```bash
   cp .env.example .env
   ```
3. Ajuste credenciais do MySQL no `.env`.
4. Crie banco:
   ```sql
   CREATE DATABASE drake2_mvp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
5. Instale dependências:
   ```bash
   composer install
   ```
6. Rode migrações:
   ```bash
   php scripts/migrate.php
   ```
7. Rode seeds:
   ```bash
   php scripts/seed.php
   ```
8. Suba servidor local:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
9. Acesse `http://localhost:8000/login`.

### Usuário seed

- Email: `admin@tenant.com`
- Senha: `123456`

## Endpoints API (internos)

- `GET /api/schedules`
- `GET /api/trainings-expiring`

## Testes

```bash
composer test
```

Cobre mínimo solicitado:
- auth (hash/verify)
- permissões
- validação de escalas por impedimento
- vencimento de treinamento

## Backlog V2 (Portal do colaborador)

1. App/portal do colaborador (web responsivo + mobile) para consulta de escala pessoal.
2. Timeline logística (embarques, vouchers, solicitações, status em tempo real).
3. Agenda de treinamentos com notificações push/e-mail e confirmação de presença.
4. Central de documentos (qualificações, certificados, comprovantes) com assinatura eletrônica.
5. Mensageria interna segmentada por unidade/equipe/escala.
6. Timesheet self-service com aprovação em cadeia e integração com folha.
7. ChatOps para operações (ocorrências, handover de turno, alertas críticos).
8. BI avançado com painéis de custo operacional, absenteísmo e compliance preditivo.
