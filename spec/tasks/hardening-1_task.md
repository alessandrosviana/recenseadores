# Tarefa 1.0: Bloqueio de acesso via .htaccess a scripts de debug e arquivos de log

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-001 — Bloqueio de scripts de debug/manutenção expostos
- REQ-009 — Bloqueio de arquivos de log e debug expostos

## Dependências

- Nenhuma

## Estimativa

- **Tamanho**: P
- **Horas estimadas**: 1-2h

## Visão Geral

Criar arquivo `.htaccess` na raiz do projeto bloqueando acesso web a todos os scripts PHP de debug/manutenção/migration (50+ arquivos) e a arquivos de log/audit (.txt, .log). Também criar `.htaccess` em `uploads/` desabilitando execução de PHP. Não altera nenhum arquivo PHP existente — apenas bloqueia acesso web.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — manter estilo de configuração Apache
</skills>

<requirements>
1. Criar `.htaccess` na raiz do projeto
2. Bloquear acesso a scripts matching: `reset_admin`, `setup`, `repair_db`, `clean_db`, `reset_dados_producao`, `list_all`, `list_users`, `check_*`, `fix_*`, `debug_*`, `scan_*`, `test_*`, `migrate_*`, `update_schema*`, `update_routes_schema*`, `add_*`, `doc_*`, `master_fix*`, `binary_clean`, `final_clean`, `regex_clean`, `user_final_fix`
3. Bloquear acesso a arquivos `.txt` e `.log` na raiz
4. Criar `uploads/.htaccess` com `php_flag engine off`
5. Adicionar `emails_log.txt` ao `.gitignore` (preservar arquivo local)
</requirements>

## Subtarefas

- [ ] 1.1 Criar `.htaccess` na raiz com regras `<FilesMatch>` para scripts de debug
- [ ] 1.2 Adicionar regra para bloquear arquivos `.txt` e `.log` no `.htaccess`
- [ ] 1.3 Criar `uploads/.htaccess` com `php_flag engine off` e `Require all granted`
- [ ] 1.4 Adicionar `emails_log.txt` ao `.gitignore`

## Detalhes de Implementação

Ver detalhes completos na `techspec-hardening-seguranca.md`, seção "Ordem de Construção", Fase 1, passos 1-2.

O `.htaccess` deve usar `<FilesMatch>` com `Require all denied` (Apache 2.4+ / HostGator). Para `uploads/`, além de `php_flag engine off`, adicionar também `<FilesMatch "\.php$"> Require all denied </FilesMatch>` como fallback caso `php_flag` não funcione (CGI/FastCGI).

## Critérios de Sucesso

- Acessar `reset_admin.php` via browser retorna HTTP 403
- Acessar `setup.php` via browser retorna HTTP 403
- Acessar `list_users.php` via browser retorna HTTP 403
- Acessar `emails_log.txt` via browser retorna HTTP 403
- Acessar `index.php` via browser retorna HTTP 200 (não bloqueado)
- Acessar `pages/login.php` via browser retorna HTTP 200
- Acessar `uploads/test.php` não executa PHP

## Testes da Tarefa

- [ ] Testes de unidade: N/A (configuração de servidor)
- [ ] Testes de integração: acessar cada categoria de script bloqueado via browser/curl e confirmar 403
- [ ] Testes E2E: acessar páginas principais (index, login, register, admin dashboard) e confirmar funcionamento normal

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `.htaccess` (criar — raiz)
- `uploads/.htaccess` (criar)
- `.gitignore` (modificar — adicionar `emails_log.txt`)
