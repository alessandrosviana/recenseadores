# Tarefa 2.0: Credenciais de banco de dados via variáveis de ambiente

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-002 — Credenciais de banco de dados via variáveis de ambiente

## Dependências

- Nenhuma

## Estimativa

- **Tamanho**: P
- **Horas estimadas**: 1h

## Visão Geral

Substituir credenciais hardcoded (root/senha vazia) em `config/database.php` por leitura via `getenv()` com fallback para valores locais. Criar template `config/database.example.php` documentado. Adicionar `config/database.php` ao `.gitignore`.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — nomenclatura e formatação
</skills>

<requirements>
1. Modificar `config/database.php` para ler `$host`, `$port`, `$db_name`, `$username`, `$password` de `getenv()` com fallback (`??` ou operador ternário) para os valores atuais
2. Criar `config/database.example.php` com placeholders documentados (`getenv('DB_HOST')`, etc.)
3. Adicionar `config/database.php` ao `.gitignore` (preservar o arquivo local existente)
4. Não alterar a conexão PDO, atributos, ou `BASE_URL`
</requirements>

## Subtarefas

- [ ] 2.1 Modificar `config/database.php` — substituir hardcoded por `getenv('DB_HOST') ?? 'localhost'`, etc.
- [ ] 2.2 Criar `config/database.example.php` com placeholders e comentários
- [ ] 2.3 Adicionar `config/database.php` ao `.gitignore`

## Detalhes de Implementação

Ver `techspec-hardening-seguranca.md`, seção "Ordem de Construção", Fase 1, passo 3.

Padrão a usar em `config/database.php`:
```php
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3366';
$db_name = getenv('DB_NAME') ?: 'sistema_recenseadores';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
```

## Critérios de Sucesso

- A aplicação conecta ao banco normalmente em ambiente local (sem variáveis de ambiente setadas)
- `config/database.php` está no `.gitignore` e não aparece em `git status` como untracked
- `config/database.example.php` existe com placeholders documentados
- Em produção, se variáveis de ambiente `DB_HOST`, `DB_USER`, `DB_PASS` estiverem definidas, são usadas

## Testes da Tarefa

- [ ] Testes de unidade: N/A
- [ ] Testes de integração: carregar `config/database.php` e confirmar que `$pdo` é uma instância PDO válida
- [ ] Testes E2E: acessar `index.php` e `pages/login.php` — confirmar que carregam sem erro de conexão

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `config/database.php` (modificar)
- `config/database.example.php` (criar)
- `.gitignore` (modificar — adicionar `config/database.php`)
