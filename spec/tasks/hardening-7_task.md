# Tarefa 7.0: Endurecimento da recuperação de senha

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-006 — Endurecimento da recuperação de senha

## Dependências

- 3.0 (includes/security.php — validar_cpf, check_rate_limit, record_failed_attempt, reset_rate_limit)
- 5.0 (CSRF — csrf_verify já adicionado em forgot_password.php)

## Estimativa

- **Tamanho**: M
- **Horas estimadas**: 2-3h

## Visão Geral

Endurecer o fluxo de recuperação de senha em `pages/forgot_password.php`: adicionar rate limiting (5 tentativas por CPF por hora), aumentar senha mínima para 8 caracteres com complexidade (1 letra + 1 número), notificar por email após reset, validar CPF com `validar_cpf()`. Não alterar o fluxo visual de duas etapas.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — estilo procedural
- `.agents/rules/logging.md` — log de tentativas de reset
</skills>

<requirements>
1. Na etapa de verificação (action=verify): validar CPF com `validar_cpf()` antes da query; aplicar `check_rate_limit()` por CPF; se bloqueado, exibir mensagem; registrar tentativa falha com `record_failed_attempt()`
2. Na etapa de reset (action=reset_password): validar que senha tem no mínimo 8 caracteres, pelo menos 1 letra e 1 número; se inválida, exibir erro e manter na etapa 2
3. Após reset bem-sucedido: chamar `sendEmail()` (do mailer existente) notificando o usuário; resetar rate limit com `reset_rate_limit()`
4. Não alterar os campos do formulário, o fluxo de duas etapas, ou a aparência visual
</requirements>

## Subtarefas

- [ ] 7.1 Adicionar validação de CPF na etapa de verificação
- [ ] 7.2 Adicionar rate limiting (check_rate_limit + record_failed_attempt) na etapa de verificação
- [ ] 7.3 Aumentar validação de senha: mínimo 8 chars + 1 letra + 1 número (substituir `strlen < 6` por `strlen < 8` + regex)
- [ ] 7.4 Adicionar notificação por email após reset bem-sucedido (usar `sendEmail` do mailer existente)
- [ ] 7.5 Adicionar `reset_rate_limit()` após reset bem-sucedido

## Detalhes de Implementação

**Rate limiting na verificação:**
```php
// Antes da query de verificação
if (!validar_cpf($cpf)) {
    $message = '<div class="alert danger">CPF inválido.</div>';
} elseif (!check_rate_limit($pdo, 'reset_' . $cpf, 5, 60)) {
    $message = '<div class="alert danger">Muitas tentativas. Tente novamente em 1 hora.</div>';
} else {
    // query existente...
    if ($user) {
        reset_rate_limit($pdo, 'reset_' . $cpf);
        // prosseguir para etapa 2
    } else {
        record_failed_attempt($pdo, 'reset_' . $cpf);
        $message = '...CPF ou Data não conferem...';
    }
}
```

**Validação de senha:**
```php
if (empty($password) || strlen($password) < 8 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    $message = 'A senha deve ter no mínimo 8 caracteres, com pelo menos 1 letra e 1 número.';
}
```

## Critérios de Sucesso

- Recuperar senha com CPF inválido (dígitos errados) é rejeitado com mensagem clara
- Após 5 tentativas falhas de verificação para o mesmo CPF, o sistema bloqueia por 1 hora
- Tentar criar senha com 6 caracteres é rejeitado (mínimo agora é 8)
- Tentar criar senha sem letra ou sem número é rejeitado
- Após reset bem-sucedido, email é logado em `emails_log.txt`
- O fluxo normal (CPF válido + data correta + senha válida) continua funcionando

## Testes da Tarefa

- [ ] Testes de unidade: `validar_cpf` com CPFs válidos e inválidos
- [ ] Testes de integração: 5 tentativas falhas seguidas → bloqueio; reset com senha fraca → rejeição
- [ ] Testes E2E: fluxo completo de recuperação com dados válidos e senha forte

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `pages/forgot_password.php` (modificar)
- `includes/mailer.php` (já existe — apenas usar)
