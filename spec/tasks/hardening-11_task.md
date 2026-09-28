# Tarefa 11.0: Logger de email seguro

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-014 — Logger de email seguro

## Dependências

- Nenhuma

## Estimativa

- **Tamanho**: P
- **Horas estimadas**: 30min

## Visão Geral

Corrigir `includes/mailer.php`: (1) retornar resultado real de `mail()` em vez de sempre `true`, (2) não logar corpo completo do email (apenas destinatário, assunto e status) para evitar exposição de dados pessoais.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — estilo procedural
- `.agents/rules/logging.md` — não logar PII
</skills>

<requirements>
1. Alterar `return true;` (linha 32) para `return $mailSent;`
2. Remover `$logContent .= "BODY:\n$body\n";` ou substituir por `$logContent .= "BODY: [redacted - contains PII]\n";`
3. Não alterar a assinatura da função `sendEmail($to, $subject, $body)` nem os callers
4. O log continua sendo escrito em `emails_log.txt` (já bloqueado via .htaccess pela task 1.0)
</requirements>

## Subtarefas

- [ ] 11.1 Alterar `return true` para `return $mailSent`
- [ ] 11.2 Remover/substituir log do corpo do email

## Detalhes de Implementação

**Em `includes/mailer.php`:**

Antes (linha 18-24):
```php
$logContent .= "BODY:\n$body\n";
```
Depois:
```php
$logContent .= "BODY: [redacted - may contain PII]\n";
```

Antes (linha 32):
```php
return true; // Return true to simulate success for the user UI
```
Depois:
```php
return $mailSent;
```

## Critérios de Sucesso

- Se `mail()` falha, `sendEmail()` retorna `false`
- Se `mail()` sucesso, `sendEmail()` retorna `true`
- `emails_log.txt` não contém o corpo do email (apenas destinatário, assunto, status)
- Os callers de `sendEmail()` continuam funcionando sem alteração

## Testes da Tarefa

- [ ] Testes de unidade: N/A
- [ ] Testes de integração: chamar `sendEmail()` e verificar retorno; verificar conteúdo do log
- [ ] Testes E2E: N/A

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `includes/mailer.php` (modificar — linhas 18-24 e 32)
