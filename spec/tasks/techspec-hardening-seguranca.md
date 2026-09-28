# Tech Spec — Hardening de Segurança do Sistema de Recenseadores CAU/DF

## Requisitos Atendidos

- REQ-001 — Bloqueio de scripts de debug/manutenção expostos
- REQ-002 — Credenciais de banco de dados via variáveis de ambiente
- REQ-003 — Proteção CSRF em todos os formulários
- REQ-004 — Sanitização XSS no campo area_details
- REQ-005 — Validação server-side de uploads de arquivos
- REQ-006 — Endurecimento da recuperação de senha
- REQ-007 — Configuração segura de sessão
- REQ-008 — Ocultar detalhes de erro do banco de dados
- REQ-009 — Bloqueio de arquivos de log e debug expostos
- REQ-010 — Rate limiting no login
- REQ-011 — Validação server-side de CPF
- REQ-012 — Remoção de auto-migration do dashboard admin
- REQ-013 — Headers de segurança HTTP
- REQ-014 — Logger de email seguro

## Resumo Executivo

A estratégia de implementação é **ponte-de-correção** (point-fix) em PHP puro sobre a arquitetura existente, sem introduzir frameworks, bibliotecas externas ou alterar fluxos de usuário. As 14 correções agrupam-se em quatro categorias técnicas: (1) configuração de servidor via `.htaccess` (REQ-001, 005, 009, 013), (2) modificações em arquivos de configuração compartilhados `config/session.php` e `config/database.php` (REQ-002, 007, 008, 013), (3) introdução de funções utilitárias reutilizáveis em um novo `includes/security.php` (REQ-003, 004, 010, 011), e (4) correções pontuais em páginas individuais (REQ-004, 005, 006, 012, 014).

A ordem de implementação prioriza as correções de configuração de servidor (`.htaccess`) e credenciais primeiro, pois bloqueiam vetores de ataque imediatos sem tocar no código da aplicação. Em seguida, as funções utilitárias compartilhadas (`includes/security.php`) que habilitam CSRF, validação de CPF e rate limiting. Por fim, as correções pontuais em páginas individuais.

## Arquitetura do Sistema

### Visão Geral dos Componentes

O sistema é uma aplicação PHP procedural single-package sem framework, servida por Apache (HostGator). A arquitetura existente:

```
config/
  database.php    → conexão PDO (incluído em todas as páginas)
  session.php     → bootstrap de sessão + timeout (incluído em todas as páginas)
includes/
  header.php      → HTML <head> + nav (incluído em todas as páginas)
  footer.php      → fechamento HTML
  mailer.php      → helper de email
pages/
  login.php, register.php, forgot_password.php
  admin/dashboard.php, edit_route.php, edit_user.php, view_user.php, ...
  recenseador/dashboard.php, generate_contract.php, ...
uploads/           → arquivos enviados (documents, reports, payments, admin_routes)
sessions/          → armazenamento de sessões PHP
```

**Componentes novos:**

- `includes/security.php` — módulo de funções de segurança reutilizáveis (CSRF, CPF, rate limiting, sanitização XSS). Incluído após `session.php` nas páginas que processam POST.
- `.htaccess` (raiz) — controle de acesso a scripts de debug e arquivos de log
- `uploads/.htaccess` — desabilita execução de PHP no diretório de uploads
- `config/database.example.php` — template de configuração sem credenciais reais
- `migrations/add_archive_columns.php` — migration isolada (extraída do dashboard)

**Componentes modificados:**

- `config/session.php` — adicionar headers de segurança, cookies seguros, `session_regenerate_id`, include de `security.php`
- `config/database.php` — ler credenciais de `getenv()` com fallback
- `includes/mailer.php` — retornar resultado real, não logar corpo
- `pages/login.php` — rate limiting, `session_regenerate_id(true)`, CSRF
- `pages/register.php` — CSRF, validação CPF, validação upload server-side
- `pages/forgot_password.php` — CSRF, rate limiting, complexidade de senha, validação CPF
- `pages/admin/dashboard.php` — CSRF, remover auto-migration, ocultar `getMessage()`
- `pages/admin/user_routes.php` — sanitizar `area_details`
- `pages/recenseador/dashboard.php` — CSRF, ocultar `getMessage()`
- `pages/recenseador/generate_contract.php` — sanitizar `area_details`
- `pages/upload_docs.php` — CSRF, validação MIME server-side
- `pages/view_docs.php` — CSRF
- Todos os demais pages com POST — adicionar CSRF

## Design de Implementação

### Interfaces Principais

Novo arquivo `includes/security.php` com funções procedurais (sem interfaces/OO — consistente com o estilo do projeto):

```php
<?php
// includes/security.php — funções de segurança reutilizáveis

/** Gera ou recupera token CSRF da sessão atual */
function csrf_token(): string {}

/** Renderiza campo hidden do token CSRF para formulários */
function csrf_field(): string {}

/** Valida token CSRF do POST. Encerra com 403 se inválido */
function csrf_verify(): void {}

/** Valida CPF (11 dígitos + dígitos verificadores). Retorna bool */
function validar_cpf(string $cpf): bool {}

/** Sanitiza HTML permitindo apenas tags de formatação básicas */
function sanitize_html(string $html): string {}

/** Registra tentativa falha de login e verifica se IP/email está bloqueado */
function check_rate_limit(PDO $pdo, string $key, int $max = 5, int $window_min = 15): bool {}

/** Registra tentativa falha no log de rate limiting */
function record_failed_attempt(PDO $pdo, string $key): void {}

/** Reseta contador de tentativas após sucesso */
function reset_rate_limit(PDO $pdo, string $key): void {}
```

### Modelos de Dados

**Nova tabela para rate limiting** (criada via migration):

```sql
CREATE TABLE IF NOT EXISTS rate_limit_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    limit_key VARCHAR(255) NOT NULL,   -- email ou CPF ou IP
    attempt_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_time (limit_key, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

A tabela é leve e auto-limpa: queries contam apenas registros dentro da janela de tempo. Um cleanup periódico (opcional) remove registros antigos.

**Schema de sessão** — sem alteração. O token CSRF é armazenado em `$_SESSION['csrf_token']`.

### Endpoints de API

Sem novos endpoints. Todas as correções aplicam-se aos endpoints POST existentes:

| Endpoint | Correções aplicadas |
|---|---|
| `POST pages/login.php` | CSRF, rate limiting, session_regenerate_id |
| `POST pages/register.php` | CSRF, validação CPF, validação upload |
| `POST pages/forgot_password.php` | CSRF, rate limiting, validação CPF, complexidade senha |
| `POST pages/admin/dashboard.php` | CSRF, ocultar getMessage, remover auto-migration |
| `POST pages/recenseador/dashboard.php` | CSRF, ocultar getMessage |
| `POST pages/upload_docs.php` | CSRF, validação MIME |
| `POST pages/view_docs.php` | CSRF |
| Todos os demais POST | CSRF |

## Pontos de Integração

Sem novas integrações externas. As integrações existentes permanecem:

- **ViaCEP** (`viacep.com.br`) — busca de CEP no registro. Afetada por CSP (REQ-013): deve estar na `connect-src` do Content-Security-Policy.
- **Google Fonts, FontAwesome (CDNJS), Quill (CDN)** — assets via CDN. Afetados por CSP: devem estar em `style-src` e `script-src`.
- **PHP `mail()`** — envio de email. Afetado por REQ-014 (retorno real + log sem corpo).

## Verificações Técnicas

### Segurança

- **CSRF**: todos os formulários validam token com `hash_equals()` (timing-safe). Token regenerado após login.
- **XSS**: `area_details` sanitizado com whitelist de tags (`<p>`, `<strong>`, `<em>`, `<ul>`, `<ol>`, `<li>`, `<br>`). Atributos e `<script>` removidos.
- **Upload**: MIME type validado com `finfo_file()` (não apenas extensão). Tamanho validado server-side. `.htaccess` em `uploads/` desabilita `php_engine`.
- **Sessão**: cookies `HttpOnly` + `Secure` + `SameSite=Strict`. `session_regenerate_id(true)` no login. Diretório `sessions/` com permissões 0700.
- **Rate limiting**: contador por chave (email/IP/CPF) em tabela dedicada. Janela deslizante de 15 minutos. 5 tentativas máximas.
- **CPF**: validação de 11 dígitos numéricos + dois dígitos verificadores (algoritmo oficial).
- **Credenciais DB**: `getenv()` com fallback para valores locais. Arquivo não versionado.
- **Headers**: CSP, X-Frame-Options DENY, X-Content-Type-Options nosniff, HSTS.

### Arquitetura

- **Sem nova camada arquitetural** — `includes/security.php` é um arquivo de funções procedural, consistente com `includes/mailer.php` existente.
- **`.htaccess`** é a única configuração de servidor — compatível com Apache/HostGator.
- **Sem dependência de mod_rewrite** — apenas `Require all denied` e `php_flag` (mod_authz_core + mod_php).
- **Migration isolada** (`migrations/add_archive_columns.php`) executa uma única vez, fora do ciclo de request.

### Infraestrutura

- **Deploy**: copiar arquivos via FTP/sFTP (prática existente no HostGator). Sem build step.
- **Rollback**: reverter `.htaccess` e arquivos PHP modificados via git. As tabelas novas (`rate_limit_attempts`) são aditivas e podem ser dropadas sem impacto.
- **Zero downtime**: todas as correções são compatíveis com o código em execução. O `.htaccess` entra em vigor imediatamente. As funções de CSRF degradam graciosamente (formulários antigos sem token recebem 403 — aceitável pois a janela é o tempo entre deploy do frontend e do backend, que é simultâneo neste deploy simples).

## Abordagem de Testes

### Testes Unidade

O projeto não possui suite de testes automatizados. Para as funções de `includes/security.php`, criar um script de validação manual `scratch/test_security.php` (bloqueado pelo `.htaccess`):

- `validar_cpf()`: CPFs válidos (5 casos), CPFs inválidos (todos iguais, dígitos errados, < 11 dígitos, letras)
- `sanitize_html()`: HTML com `<script>`, HTML com tags permitidas, HTML vazio, HTML com atributos on*
- `csrf_token()`: geração, consistência dentro da sessão, diferença entre sessões
- `check_rate_limit()`: bloqueio após 5 tentativas, reset após sucesso, janela deslizante

### Testes de Integração

Manuais, executados no ambiente de homologação:

1. Login com credenciais válidas — funciona, session ID muda
2. Login com credenciais inválidas 6 vezes — bloqueio na 6ª
3. Registro com CPF inválido — rejeitado
4. Registro com PDF válido — aceito
5. Registro com `.php` renomeado `.pdf` — rejeitado (MIME)
6. Aprovar cadastro sem token CSRF — 403
7. Aprovar cadastro com token CSRF válido — funciona
8. Acessar `reset_admin.php` via browser — 403
9. Acessar `emails_log.txt` via browser — 403
10. Renderizar `area_details` com `<script>` — não executa

### Testes de E2E

Sem suite E2E existente. Validação manual dos fluxos completos após deploy:

- Fluxo de cadastro completo → aprovação admin → atribuição de rota → aceite → execução → conclusão → cálculo → liquidação
- Fluxo de recuperação de senha completo
- Verificar headers de segurança via DevTools (Network tab)

## Sequenciamento de Desenvolvimento

### Ordem de Construção

**Fase 1 — Configuração de servidor (bloqueia vetores imediatos, sem tocar código):**
1. `.htaccess` na raiz — bloquear scripts debug + arquivos .txt/.log (REQ-001, 009)
2. `uploads/.htaccess` — desabilitar execução PHP (REQ-005 parcial)
3. `config/database.php` → `getenv()` + `config/database.example.php` + `.gitignore` (REQ-002)

**Fase 2 — Módulo de segurança compartilhado:**
4. `includes/security.php` — todas as funções (CSRF, CPF, XSS, rate limiting) (REQ-003, 004, 010, 011)
5. `migrations/add_archive_columns.php` + remover auto-migration do dashboard (REQ-012)
6. Tabela `rate_limit_attempts` no banco (executar migration)

**Fase 3 — Configuração de sessão e headers:**
7. `config/session.php` — cookies seguros, session_regenerate_id, headers HTTP, include security.php (REQ-007, 013)

**Fase 4 — Correções pontuais em páginas:**
8. `pages/login.php` — CSRF, rate limiting, session_regenerate_id (REQ-003, 010)
9. `pages/register.php` — CSRF, validação CPF, validação upload MIME (REQ-003, 005, 011)
10. `pages/forgot_password.php` — CSRF, rate limiting, complexidade senha, validação CPF (REQ-003, 006, 011)
11. `pages/admin/dashboard.php` — CSRF, ocultar getMessage (REQ-003, 008)
12. `pages/admin/user_routes.php` — sanitizar area_details (REQ-004)
13. `pages/recenseador/dashboard.php` — CSRF, ocultar getMessage (REQ-003, 008)
14. `pages/recenseador/generate_contract.php` — sanitizar area_details (REQ-004)
15. `pages/upload_docs.php` — CSRF, validação MIME (REQ-003, 005)
16. `pages/view_docs.php` — CSRF (REQ-003)
17. Demais páginas com POST (`edit_route.php`, `edit_user.php`) — CSRF (REQ-003)

**Fase 5 — Mailer e finalização:**
18. `includes/mailer.php` — retorno real, log sem corpo (REQ-014)
19. `config/database.php` — ocultar getMessage na conexão (REQ-008)
20. Validação manual completa

### Dependências Técnicas

- `includes/security.php` (passo 4) é pré-requisito de todos os CSRF e validações (passos 8-17)
- Tabela `rate_limit_attempts` (passo 6) é pré-requisito dos rate limits (passos 8, 10)
- `config/session.php` (passo 7) é pré-requisito dos headers (todas as páginas) e do include de security.php
- O `.htaccess` (passo 1) não tem dependência — pode ser deployado isoladamente

## Monitoramento e Observabilidade

### Error Tracking

Sem ferramenta externa (Sentry, etc.). Estratégia:

- Log de erros em `scratch/error.log` (já coberto pelo `.gitignore` via `*.log`)
- Formato: `[timestamp] [level] [file:line] message` (sem PII)
- Capturar: PDOExceptions, falhas de upload, tentativas de CSRF rejeitadas, bloqueios de rate limiting

### Logging Estruturado

- **Campos obrigatórios**: timestamp, nível (ERROR/WARNING/INFO), arquivo, linha
- **Eventos a logar**: erro de DB, CSRF rejeitado, rate limit acionado, upload rejeitado, login falho
- **Dados a NÃO logar**: CPF, email, senha, corpo de email, conteúdo de documentos

### Health Checks

Sem endpoints de health check (aplicação PHP sem framework). Monitoramento via:

- Acesso a `index.php` retorna 200 (liveness)
- Conexão com DB bem-sucedida (implícito — se DB falha, página morre com mensagem genérica após REQ-008)

### Métricas de Negócio

- Contador de tentativas de login falhas (tabela `rate_limit_attempts`)
- Contador de CSRF rejeitados (log)
- Contador de uploads rejeitados por MIME inválido (log)

### Alertas

Sem sistema de alertas automatizado. Recomendação futura: monitorar `scratch/error.log` via cron e alertar admin por email se taxa de erro > threshold.

## Considerações Técnicas

### Decisões Principais

1. **`.htaccess` ao invés de mover arquivos**: preserva a estrutura do repositório e funciona em HostGator sem acesso shell. Alternativa rejeitada: renomear scripts para `.php.bak` — quebra referências e é reversível por acidente.

2. **Funções procedurais em `includes/security.php`**: consistente com o estilo do projeto (`includes/mailer.php` já é procedural). Alternativa rejeitada: classe singleton — adiciona complexidade sem benefício em um projeto sem PSR-4/autoloader.

3. **Rate limiting via tabela MySQL**: persistente across requests, não depende de sessão (que é destruída no logout). Alternativa rejeitada: arquivo flat — problemas de concorrência e locking. Alternativa rejeitada: APCu/Redis — não disponível no HostGator shared.

4. **Sanitização XSS via regex whitelist**: sem dependência externa (HTML Purifier exige Composer). Whitelist de tags: `<p>`, `<strong>`, `<em>`, `<ul>`, `<ol>`, `<li>`, `<br>`. Remove `<script>`, `on*` attributes, `<iframe>`, etc. Risco residual aceitável para campo admin-only.

5. **CSRF com session storage**: sem dependência externa, token por sessão. Alternativa rejeitada: double-submit cookie — mais complexo, benefício marginal para aplicação single-domain.

6. **MIME validation com `finfo_file()`**: função nativa PHP (extensão fileinfo, habilitada por padrão desde PHP 5.3). Alternativa rejeitada: `$_FILES['...']['type']` — enviado pelo cliente, não confiável.

### Riscos Conhecidos

- **CSP muito restritiva pode quebrar iframes de PDF**: `view_docs.php` usa `<iframe>` para preview de PDFs. A CSP deve permitir `frame-src 'self'` para os PDFs em `uploads/`. Testar após deploy.
- **Cookie `Secure` em HTTP**: se o servidor não tiver HTTPS, o cookie não é enviado. O PRD assume HTTPS configurado a nível de infra. Verificar antes do deploy.
- **Token CSRF em formulários com AJAX**: o dashboard admin faz fetch via JavaScript (`fetch_dashboard_data.php`). Esse endpoint faz GET, não POST — CSRF não se aplica. Mas se houver POST via fetch no futuro, o token deve ser enviado no header.
- **Rate limiting em ambiente com NAT**: múltiplos usuários atrás do mesmo IP podem ser bloqueados coletivamente. Mitigação: limitar por email (não apenas IP) e usar IP como secondary key.

### Conformidade com Skills Padrões

O projeto não utiliza DDD/Bounded Contexts (arquitetura procedural PHP). A rule `architecture-ddd.md` não se aplica. As rules aplicáveis são:

- `.agents/rules/code-standards.md` — nomenclatura, formatação (manter estilo procedural existente)
- `.agents/rules/logging.md` — estrutura de log (aplicar em `scratch/error.log`)

### Arquivos relevantes e dependentes

**Arquivos a criar:**
- `.htaccess` (raiz)
- `uploads/.htaccess`
- `includes/security.php`
- `config/database.example.php`
- `migrations/add_archive_columns.php`
- `scratch/test_security.php` (validação manual)

**Arquivos a modificar:**
- `config/session.php` — cookies, headers, regenerate_id, include security.php
- `config/database.php` — getenv, ocultar getMessage
- `includes/mailer.php` — retorno real, log sem corpo
- `.gitignore` — adicionar config/database.php, emails_log.txt
- `pages/login.php` — CSRF, rate limiting
- `pages/register.php` — CSRF, validação CPF, validação upload
- `pages/forgot_password.php` — CSRF, rate limiting, complexidade senha, CPF
- `pages/admin/dashboard.php` — CSRF, remover auto-migration, ocultar getMessage
- `pages/admin/user_routes.php` — sanitizar area_details
- `pages/admin/edit_route.php` — CSRF
- `pages/admin/edit_user.php` — CSRF
- `pages/admin/view_user.php` — CSRF (se tiver POST)
- `pages/recenseador/dashboard.php` — CSRF, ocultar getMessage
- `pages/recenseador/generate_contract.php` — sanitizar area_details
- `pages/upload_docs.php` — CSRF, validação MIME
- `pages/view_docs.php` — CSRF

**Schema DB:**
- Nova tabela: `rate_limit_attempts`
- Colunas existentes `routes.is_archived` e `routes.archive_reason` — garantir existência via migration isolada
