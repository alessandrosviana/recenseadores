# Tarefa 3.0: Módulo de funções de segurança compartilhado (includes/security.php)

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-003 — Proteção CSRF em todos os formulários (funções csrf_*)
- REQ-004 — Sanitização XSS no campo area_details (função sanitize_html)
- REQ-010 — Rate limiting no login (funções check_rate_limit, record_failed_attempt, reset_rate_limit)
- REQ-011 — Validação server-side de CPF (função validar_cpf)

## Dependências

- Nenhuma

## Estimativa

- **Tamanho**: G
- **Horas estimadas**: 4-6h

## Visão Geral

Criar `includes/security.php` com todas as funções de segurança reutilizáveis em estilo procedural (consistente com `includes/mailer.php`). Este módulo é pré-requisito para as tasks 4.0, 5.0, 7.0, 8.0 e 9.0. Também criar a tabela `rate_limit_attempts` no banco de dados.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — estilo procedural, nomenclatura
- `.agents/rules/logging.md` — estrutura de log para erros de segurança
</skills>

<requirements>
1. Criar `includes/security.php` com as funções: `csrf_token()`, `csrf_field()`, `csrf_verify()`, `validar_cpf()`, `sanitize_html()`, `check_rate_limit()`, `record_failed_attempt()`, `reset_rate_limit()`
2. `csrf_token()`: gera token com `bin2hex(random_bytes(32))` e armazena em `$_SESSION['csrf_token']`
3. `csrf_field()`: retorna string HTML `<input type="hidden" name="csrf" value="...">`
4. `csrf_verify()`: valida `$_POST['csrf']` com `hash_equals()`, encerra com 403 se inválido
5. `validar_cpf()`: valida 11 dígitos + 2 dígitos verificadores (algoritmo oficial)
6. `sanitize_html()`: whitelist de tags `<p>`, `<strong>`, `<em>`, `<ul>`, `<ol>`, `<li>`, `<br>`, remove `<script>`, atributos `on*`
7. `check_rate_limit()`: conta tentativas na tabela `rate_limit_attempts` dentro da janela de tempo
8. Criar tabela `rate_limit_attempts` no banco (via script SQL ou migration)
</requirements>

## Subtarefas

- [ ] 3.1 Implementar `csrf_token()`, `csrf_field()`, `csrf_verify()`
- [ ] 3.2 Implementar `validar_cpf(string $cpf): bool` com algoritmo de dígitos verificadores
- [ ] 3.3 Implementar `sanitize_html(string $html): string` com regex whitelist
- [ ] 3.4 Implementar `check_rate_limit()`, `record_failed_attempt()`, `reset_rate_limit()` usando PDO global
- [ ] 3.5 Criar tabela `rate_limit_attempts` no banco de dados
- [ ] 3.6 Criar `scratch/test_security.php` com testes manuais de cada função

## Detalhes de Implementação

Ver `techspec-hardening-seguranca.md`, seção "Interfaces Principais" para assinaturas das funções e seção "Modelos de Dados" para o schema da tabela `rate_limit_attempts`.

**CSRF — `csrf_verify()`** deve ser chamada no início de todo handler POST, antes de qualquer processamento. Se o token não confere, responder `http_response_code(403)` e `exit('Erro de validação.')`.

**CPF — algoritmo**: limpar não-dígitos, verificar 11 dígitos, rejeitar todos iguais (000..., 111...), calcular d1 e d2 com multiplicadores decrescentes.

**sanitize_html** — usar `strip_tags()` com whitelist, depois regex para remover atributos `on\w+="..."` e `javascript:`.

## Critérios de Sucesso

- `csrf_token()` gera string de 64 chars hex, consistente dentro da sessão
- `csrf_verify()` com token válido retorna, com token inválido encerra com 403
- `validar_cpf('111.444.777-35')` retorna `true`; `validar_cpf('000.000.000-00')` retorna `false`
- `sanitize_html('<p>ok</p><script>alert(1)</script>')` retorna `<p>ok</p>alert(1)` (sem script tag)
- `check_rate_limit()` retorna `true` após 4 tentativas, `false` após 5
- Tabela `rate_limit_attempts` existe no banco

## Testes da Tarefa

- [ ] Testes de unidade: executar `scratch/test_security.php` — validar todas as funções com casos positivos e negativos
- [ ] Testes de integração: `check_rate_limit` com dados reais na tabela
- [ ] Testes E2E: N/A (funções serão testadas via tasks dependentes)

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `includes/security.php` (criar)
- `scratch/test_security.php` (criar — teste manual, bloqueado pelo .htaccess da task 1.0)
- Banco de dados — tabela `rate_limit_attempts` (criar)
