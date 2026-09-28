# Tarefa 12.0: Validação manual completa e teste de regressão funcional

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-001 a REQ-014 — Validação de todas as correções

## Dependências

- 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0, 11.0 (todas as tarefas anteriores)

## Estimativa

- **Tamanho**: M
- **Horas estimadas**: 2-4h

## Visão Geral

Executar validação manual completa de todas as correções de segurança e teste de regressão de todos os fluxos funcionais existentes. Confirmar que zero funcionalidades foram alteradas e que todas as vulnerabilidades foram mitigadas.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md`
- `.agents/rules/logging.md`
</skills>

<requirements>
1. Executar todos os testes do checklist de segurança (15 vulnerabilidades)
2. Executar todos os fluxos funcionais do sistema (regressão)
3. Verificar headers de segurança via DevTools/curl
4. Verificar que nenhum erro PHP é exibido ao usuário (error_reporting)
5. Documentar resultados em `spec/tasks/hardening-validation-report.md`
</requirements>

## Subtarefas

- [ ] 12.1 Checklist de segurança — validar cada REQ (001 a 014)
- [ ] 12.2 Fluxo de cadastro completo (registro → upload docs → aprovação admin)
- [ ] 12.3 Fluxo de login (válido, inválido, conta desativada, rate limiting)
- [ ] 12.4 Fluxo de recuperação de senha (válido, CPF inválido, senha fraca, rate limiting)
- [ ] 12.5 Fluxo admin (aprovar, rejeitar, atribuir rota, cancelar, arquivar, renovar, criar admin, toggle active)
- [ ] 12.6 Fluxo recenseador (aceitar rota, iniciar, completar, upload relatório)
- [ ] 12.7 Wizard de andamento completo (passos 1-6, calculadora, liquidação)
- [ ] 12.8 Verificar headers via `curl -I` em páginas principais
- [ ] 12.9 Verificar que `scratch/error.log` registra erros sem PII
- [ ] 12.10 Gerar relatório de validação

## Detalhes de Implementação

**Checklist de segurança (validar cada item):**

| ID | Teste | Resultado esperado |
|---|---|---|
| REQ-001 | Acessar reset_admin.php | HTTP 403 |
| REQ-002 | config/database.php no .gitignore | Confirmado |
| REQ-003 | POST sem CSRF token | HTTP 403 |
| REQ-004 | area_details com `<script>` | Não executa JS |
| REQ-005 | Upload .php renomeado .pdf | Rejeitado |
| REQ-006 | 5 tentativas reset senha | Bloqueado |
| REQ-007 | Cookie de sessão | HttpOnly + Secure |
| REQ-008 | Erro de DB | Mensagem genérica |
| REQ-009 | Acessar emails_log.txt | HTTP 403 |
| REQ-010 | 5 logins falhos | Bloqueado 15min |
| REQ-011 | CPF 000.000.000-00 | Rejeitado |
| REQ-012 | Dashboard sem ALTER TABLE | Sem auto-migration |
| REQ-013 | Headers HTTP | CSP, X-Frame, HSTS |
| REQ-014 | Log de email | Sem corpo (PII) |

**Fluxos de regressão (todos devem funcionar exatamente como antes):**
1. Cadastro de recenseador com documentos
2. Login de recenseador e admin
3. Aprovação e rejeição de cadastro
4. Atribuição de rota
5. Aceite de rota pelo recenseador
6. Início e conclusão de rota com upload de relatório
7. Wizard de andamento (passos 1-6)
8. Calculadora de remuneração
9. Liquidação de pagamento
10. Cancelamento, arquivamento e renovação de rotas
11. Criação de admin
12. Toggle active/inactive de recenseador
13. Edição de rota e usuário
14. Recuperação de senha
15. Geração de contrato e memória de cálculo PDF

## Critérios de Sucesso

- Todos os 14 itens do checklist de segurança passam
- Todos os 15 fluxos de regressão funcionam sem erro
- Nenhum erro PHP visível ao usuário
- `scratch/error.log` contém apenas erros sem PII
- Relatório de validação documentado em `spec/tasks/hardening-validation-report.md`

## Testes da Tarefa

- [ ] Testes de unidade: executar `scratch/test_security.php` (criado na task 3.0)
- [ ] Testes de integração: todos os 14 itens do checklist de segurança
- [ ] Testes E2E: todos os 15 fluxos de regressão funcional

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `spec/tasks/hardening-validation-report.md` (criar — relatório de validação)
- Todos os arquivos do projeto (validação)
