# Tarefa 4.0: Configuração segura de sessão e headers HTTP

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-007 — Configuração segura de sessão
- REQ-013 — Headers de segurança HTTP

## Dependências

- 3.0 (includes/security.php — para include após session_start)

## Estimativa

- **Tamanho**: M
- **Horas estimadas**: 2-3h

## Visão Geral

Modificar `config/session.php` para: (1) configurar cookie de sessão com flags `HttpOnly`, `Secure`, `SameSite=Strict` antes de `session_start()`, (2) adicionar headers de segurança HTTP (CSP, X-Frame-Options, X-Content-Type-Options, HSTS), (3) alterar permissões do diretório `sessions/` para 0700, (4) incluir `includes/security.php` após `session_start()`.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — estilo procedural
</skills>

<requirements>
1. Chamar `session_set_cookie_params()` com `['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => true, 'samesite' => 'Strict']` antes de `session_start()`
2. Adicionar headers via `header()` antes do output: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Strict-Transport-Security: max-age=31536000`, `Content-Security-Policy` permitindo self + Google Fonts + CDNJS + Quill CDN + ViaCEP
3. Alterar `mkdir($session_dir, 0777, true)` para `mkdir($session_dir, 0700, true)`
4. Adicionar `require_once __DIR__ . '/../includes/security.php'` após `session_start()`
5. Não alterar o timeout de 30 minutos nem o comportamento de expiração por inatividade
</requirements>

## Subtarefas

- [ ] 4.1 Adicionar `session_set_cookie_params()` com flags seguras antes de `session_start()`
- [ ] 4.2 Adicionar headers de segurança HTTP (X-Frame-Options, X-Content-Type-Options, HSTS)
- [ ] 4.3 Definir Content-Security-Policy permitindo self + CDNs usados + ViaCEP + frame-src self para PDFs
- [ ] 4.4 Alterar permissões de `sessions/` de 0777 para 0700
- [ ] 4.5 Adicionar `require_once` de `includes/security.php` após `session_start()`

## Detalhes de Implementação

Ver `techspec-hardening-seguranca.md`, seção "Ordem de Construção", Fase 3, passo 7.

**CSP** deve permitir:
- `default-src 'self'`
- `style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.quilljs.com`
- `script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.quilljs.com`
- `font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com`
- `img-src 'self' data:`
- `connect-src 'self' https://viacep.com.br`
- `frame-src 'self'` (para iframes de PDF em view_docs.php)

**Atenção**: `'unsafe-inline'` em style/script é necessário porque o projeto usa CSS e JS inline extensivamente. Reduzir no futuro.

## Critérios de Sucesso

- Cookie de sessão possui flags `HttpOnly` e `Secure` (verificar via DevTools → Application → Cookies)
- Páginas respondem com headers `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Strict-Transport-Security`
- Google Fonts, FontAwesome, Quill carregam sem erro de CSP
- Busca de CEP via ViaCEP funciona sem erro de CSP
- Iframes de preview de PDF em `view_docs.php` funcionam
- Diretório `sessions/` tem permissões 0700
- Login e logout continuam funcionando
- Expiração por inatividade de 30 minutos continua funcionando

## Testes da Tarefa

- [ ] Testes de unidade: N/A
- [ ] Testes de integração: verificar headers via `curl -I` nas páginas principais
- [ ] Testes E2E: navegar em index, login, register, admin dashboard — confirmar que todos os assets carregam sem erros de CSP no console

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `config/session.php` (modificar)
