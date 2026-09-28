# Tarefa 8.0: Rate limiting no login

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-010 — Rate limiting no login

## Dependências

- 3.0 (includes/security.php — check_rate_limit, record_failed_attempt, reset_rate_limit)
- 5.0 (CSRF já adicionado em login.php)

## Estimativa

- **Tamanho**: M
- **Horas estimadas**: 2h

## Visão Geral

Adicionar rate limiting no endpoint de login (`pages/login.php`): máximo 5 tentativas falhas por email em 15 minutos. Após 5 falhas, bloquear e exibir mensagem com tempo restante. Resetar contador após login bem-sucedido.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — estilo procedural
- `.agents/rules/logging.md` — log de tentativas de login falhas
</skills>

<requirements>
1. Antes de processar o POST de login: verificar `check_rate_limit($pdo, 'login_' . $email, 5, 15)`
2. Se bloqueado: exibir mensagem "Muitas tentativas. Tente novamente em X minutos." sem processar o login
3. Se login falha (senha incorreta ou usuário não existe): chamar `record_failed_attempt($pdo, 'login_' . $email)`
4. Se login bem-sucedido: chamar `reset_rate_limit($pdo, 'login_' . $email)`
5. Não alterar o formulário visual nem os campos
</requirements>

## Subtarefas

- [ ] 8.1 Adicionar `check_rate_limit` antes do processamento de POST
- [ ] 8.2 Adicionar `record_failed_attempt` no bloco de falha (else do password_verify)
- [ ] 8.3 Adicionar `reset_rate_limit` no bloco de sucesso (após session_regenerate_id)

## Detalhes de Implementação

**Em `pages/login.php`, dentro do bloco `if ($_SERVER["REQUEST_METHOD"] == "POST")`:**

```php
$email = trim($_POST['email']);
$password = $_POST['password'];

// Rate limiting
if (!check_rate_limit($pdo, 'login_' . $email, 5, 15)) {
    $message = '<div class="alert alert-danger">...Muitas tentativas de login. Tente novamente em alguns minutos.</div>';
} else {
    // query existente...
    if ($user && password_verify(...)) {
        // login bem-sucedido
        reset_rate_limit($pdo, 'login_' . $email);
        session_regenerate_id(true);
        // redirect...
    } else {
        record_failed_attempt($pdo, 'login_' . $email);
        $message = '...Email ou senha incorretos...';
    }
}
```

## Critérios de Sucesso

- Após 5 tentativas com senha incorreta, o login é bloqueado por 15 minutos
- A mensagem de bloqueio é exibida no mesmo estilo das mensagens existentes
- Após login bem-sucedido, o contador é resetado (próximas 5 tentativas são permitidas)
- Login com credenciais corretas (sem exceder tentativas) funciona normalmente
- Conta desativada (`is_active == 0`) ainda mostra mensagem de conta desativada (não conta como tentativa falha de senha)

## Testes da Tarefa

- [ ] Testes de unidade: N/A
- [ ] Testes de integração: 5 logins falhos → 6ª tentativa bloqueada; login bem-sucedido → contador resetado
- [ ] Testes E2E: fluxo de login normal com credenciais corretas

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `pages/login.php` (modificar)
