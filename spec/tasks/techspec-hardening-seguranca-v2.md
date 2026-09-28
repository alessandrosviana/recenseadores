# Tech Spec — Hardening de Segurança v2 (Reauditoria)

## Contexto

PRD: `spec/tasks/prd-hardening-seguranca-v2.md`
Branch: `melhoria-de-seguranca`
Stack: PHP 7+ / MySQL (MariaDB 10.4) / Apache (XAMPP/HostGator) / PDO

## Arquitetura de Segurança Atual (após v1)

- CSRF tokens em todos os forms (`includes/security.php`)
- Prepared statements em todas as queries
- Session hardening (HttpOnly, Secure, SameSite=Strict)
- Headers HTTP (CSP, X-Frame-Options, HSTS, nosniff)
- Rate limiting (login + reset senha)
- `.htaccess` bloqueando scripts de debug e logs
- `uploads/.htaccess` com `php_flag engine off`

## Mudanças Implementadas (v2)

### REQ-V2-01 — Servir documentos com autenticação

**Problema:** `uploads/.htaccess` concedia `Require all granted` para PDF/images, permitindo acesso sem auth.

**Solução:**
- `serve_upload.php` (raiz): handler genérico que intercepta todo acesso a `uploads/` via RewriteRule
  - Verifica sessão autenticada
  - Admin: acesso a qualquer arquivo
  - Recenseador: busca no banco (documents + routes) se o arquivo pertence ao usuário
  - Path traversal bloqueado via `realpath()` + validação de prefixo
- `uploads/.htaccess`: RewriteRule redireciona todo request para `serve_upload.php`
- `pages/serve_document.php`: endpoint específico para view_docs.php (busca por doc_id no banco)
- `pages/view_docs.php`: links de preview/baixar apontam para `serve_document.php?doc_id=N`

### REQ-V2-02 — Remover credenciais do git

- `git rm --cached config/database.php` executado
- Arquivo local preservado (apenas deixou de ser rastreado)
- `config/database.example.php` já existe como template

### REQ-V2-03 — IDOR em aprovação de documentos

- `pages/view_docs.php:32`: adicionado `AND user_id = ?` na query de UPDATE
- `$user_id` (da URL, já validado como int) passado como parâmetro

### REQ-V2-04 — Política de senha fortalecida

- `includes/security.php:validate_password_strength()`:
  - Mínimo 10 caracteres (era 8)
  - Requisito de maiúscula, minúscula, número e especial (era apenas letra + número)
  - Blacklist de 17 senhas comuns
  - Aplicado em register.php e forgot_password.php

### REQ-V2-05 — Remover auto-login pós-cadastro

- `pages/register.php:129-131`: removido set de $_SESSION + redirect para dashboard
- Adicionado redirect para `login.php?registered=true`
- `pages/login.php`: exibe mensagem "Cadastro enviado com sucesso! Aguarde aprovação."

### Arquivos não-sistema movidos para `manutencao/`

- 69 arquivos de debug/migration/manutenção movidos da raiz para `manutencao/`
- `manutencao/.htaccess`: `Require all denied`
- Raiz `.htaccess`: simplificado, `RedirectMatch 403 ^/manutencao/.*$`
- Raiz contém apenas: `index.php`, `logout.php`, `serve_upload.php`, `.htaccess`, `.gitignore`, `database.sql`, `database_v2.sql`, docs e configs de AI

## Arquivos Criados

| Arquivo | Função |
|---|---|
| `serve_upload.php` | Handler auth para acessos a uploads/ |
| `pages/serve_document.php` | Endpoint auth para documentos por doc_id |
| `pages/serve_route_file.php` | Endpoint auth para anexos de rotas |
| `manutencao/.htaccess` | Bloqueia acesso web à pasta |
| `manutencao/` (69 arquivos) | Scripts de debug/migration movidos |

## Arquivos Modificados

| Arquivo | Mudança |
|---|---|
| `uploads/.htaccess` | RewriteRule para serve_upload.php + deny PHP |
| `.htaccess` | Simplificado, bloqueia /manutencao/ |
| `includes/security.php` | validate_password_strength fortalecida |
| `pages/view_docs.php` | Links → serve_document.php; IDOR fix |
| `pages/register.php` | Remove auto-login, redirect login |
| `pages/login.php` | Mensagem de cadastro sucesso |
| `COMO_RODAR.md` | Referência setup.php atualizada |
| `config/database.php` | Removido do tracking git |

## Riscos e Mitigações

- **mod_rewrite:** necessário ativo no Apache (confirmado no XAMPP; HostGator suporta)
- **Performance:** serve_upload.php adiciona 1 query ao banco por acesso a documento (aceitável)
- **Compatibilidade:** session cookie `secure=true` requer HTTPS em produção
