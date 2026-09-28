# Tarefa 5.0: Proteção CSRF em todas as páginas com POST

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-003 — Proteção CSRF em todos os formulários

## Dependências

- 3.0 (includes/security.php — funções csrf_token, csrf_field, csrf_verify)
- 4.0 (config/session.php — include de security.php e session regenerada)

## Estimativa

- **Tamanho**: G
- **Horas estimadas**: 4-6h

## Visão Geral

Adicionar proteção CSRF a todos os formulários POST do sistema. Para cada página que processa POST: (1) adicionar `csrf_verify()` no início do handler POST, (2) adicionar `<?= csrf_field() ?>` dentro de cada `<form>`. Adicionar `session_regenerate_id(true)` após login bem-sucedido.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — manter estilo HTML/PHP existente
</skills>

<requirements>
1. Em `pages/login.php`: adicionar `csrf_verify()` antes de processar POST; adicionar `csrf_field()` no form; adicionar `session_regenerate_id(true)` após `password_verify` bem-sucedido
2. Em `pages/register.php`: adicionar `csrf_verify()` e `csrf_field()`
3. Em `pages/forgot_password.php`: adicionar `csrf_verify()` e `csrf_field()` em ambos os forms (verify e reset_password)
4. Em `pages/admin/dashboard.php`: adicionar `csrf_verify()` no início do bloco POST; adicionar `csrf_field()` em TODOS os forms (aprovar, rejeitar, atribuir rota, cancelar, arquivar, desarquivar, renovar, criar admin, toggle active, salvar cálculo, upload payment, update wizard, reject completion)
5. Em `pages/recenseador/dashboard.php`: adicionar `csrf_verify()` e `csrf_field()` em todos os forms (start, complete, accept, reject, replace doc)
6. Em `pages/upload_docs.php`: adicionar `csrf_verify()` e `csrf_field()`
7. Em `pages/view_docs.php`: adicionar `csrf_verify()` e `csrf_field()` em todos os forms (approve/reject doc, approve/reject user)
8. Em `pages/admin/edit_route.php`: adicionar `csrf_verify()` e `csrf_field()`
9. Em `pages/admin/edit_user.php`: adicionar `csrf_verify()` e `csrf_field()`
</requirements>

## Subtarefas

- [ ] 5.1 `pages/login.php` — csrf_verify + csrf_field + session_regenerate_id(true)
- [ ] 5.2 `pages/register.php` — csrf_verify + csrf_field
- [ ] 5.3 `pages/forgot_password.php` — csrf_verify + csrf_field (2 forms)
- [ ] 5.4 `pages/admin/dashboard.php` — csrf_verify + csrf_field (todos os forms)
- [ ] 5.5 `pages/recenseador/dashboard.php` — csrf_verify + csrf_field (todos os forms)
- [ ] 5.6 `pages/upload_docs.php` — csrf_verify + csrf_field
- [ ] 5.7 `pages/view_docs.php` — csrf_verify + csrf_field (todos os forms)
- [ ] 5.8 `pages/admin/edit_route.php` + `edit_user.php` — csrf_verify + csrf_field

## Detalhes de Implementação

Ver `techspec-hardening-seguranca.md`, seção "Ordem de Construção", Fase 4.

**Padrão para cada página:**
- No início do bloco `if ($_SERVER["REQUEST_METHOD"] == "POST")`, adicionar: `csrf_verify();`
- Dentro de cada `<form action="" method="post">` (ou `method="post"`), adicionar: `<?php echo csrf_field(); ?>`
- Para forms que enviam para outras páginas (ex: `action="admin/dashboard.php"`), o token ainda é válido pois está na mesma sessão

**Atenção:** o dashboard admin tem MUITOS forms. Usar `replaceAll` ou buscar cada `<form` individualmente.

## Critérios de Sucesso

- Todo formulário HTML contém `<input type="hidden" name="csrf" value="...">`
- Submeter form sem token csrf retorna HTTP 403
- Submeter form com token válido executa a ação normalmente
- Submeter form com token de sessão diferente retorna HTTP 403
- Após login, o session ID é diferente do pré-login
- Todos os fluxos funcionam: login, registro, aprovação, rejeição, atribuição de rota, cancelamento, arquivamento, renovação, criação de admin, toggle active, cálculo, wizard, upload de docs, complete route, accept route, edit route, edit user

## Testes da Tarefa

- [ ] Testes de unidade: N/A
- [ ] Testes de integração: submeter cada form sem token e com token inválido — confirmar 403
- [ ] Testes E2E: executar fluxo completo de cadastro → aprovação → rota → wizard → cálculo → liquidação

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `pages/login.php` (modificar)
- `pages/register.php` (modificar)
- `pages/forgot_password.php` (modificar)
- `pages/admin/dashboard.php` (modificar)
- `pages/admin/edit_route.php` (modificar)
- `pages/admin/edit_user.php` (modificar)
- `pages/recenseador/dashboard.php` (modificar)
- `pages/upload_docs.php` (modificar)
- `pages/view_docs.php` (modificar)
