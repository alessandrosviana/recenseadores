# Tarefa 10.0: Ocultar detalhes de erro do DB e remover auto-migration

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-008 — Ocultar detalhes de erro do banco de dados
- REQ-012 — Remoção de auto-migration do dashboard admin

## Dependências

- Nenhuma

## Estimativa

- **Tamanho**: P
- **Horas estimadas**: 1-2h

## Visão Geral

Duas correções pontuais: (1) substituir exibições de `$e->getMessage()` por mensagens genéricas, logando erro real em arquivo; (2) extrair o bloco de auto-migration de `admin/dashboard.php` para um script isolado e remover do dashboard.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — estilo procedural
- `.agents/rules/logging.md` — estrutura de log de erros
</skills>

<requirements>
1. Em `config/database.php:18`: substituir `die("Erro... " . $e->getMessage())` por `die("Erro na conexão com o banco de dados.")` + logar erro real em `scratch/error.log`
2. Em `pages/admin/dashboard.php:130`: substituir exibição de `$e->getMessage()` por mensagem genérica + log
3. Em `pages/register.php:128`: manter mensagem de "Duplicate entry" mas ocultar outros `getMessage()`
4. Em `pages/recenseador/dashboard.php:34`: substituir `$e->getMessage()` por mensagem genérica + log
5. Remover bloco de auto-migration de `admin/dashboard.php` (linhas 6-17)
6. Criar `migrations/add_archive_columns.php` com o ALTER TABLE isolado
</requirements>

## Subtarefas

- [ ] 10.1 Criar função helper `log_error(string $msg, string $file, int $line)` que escreve em `scratch/error.log` sem PII
- [ ] 10.2 `config/database.php` — ocultar getMessage, logar erro real
- [ ] 10.3 `admin/dashboard.php` — ocultar getMessage nos catch blocks
- [ ] 10.4 `register.php` — ocultar getMessage (manter Duplicate entry)
- [ ] 10.5 `recenseador/dashboard.php` — ocultar getMessage
- [ ] 10.6 Remover auto-migration de `admin/dashboard.php` (linhas 6-17)
- [ ] 10.7 Criar `migrations/add_archive_columns.php` e executar uma vez no banco

## Detalhes de Implementação

**Padrão para ocultar erros:**
```php
} catch (PDOException $e) {
    log_error($e->getMessage(), __FILE__, __LINE__);
    $message = '<div class="alert danger">Erro interno. Tente novamente.</div>';
}
```

**Manter Duplicate entry em register.php:**
```php
} catch (Exception $e) {
    $pdo->rollBack();
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $message = '...Email ou CPF já cadastrado...';
    } else {
        log_error($e->getMessage(), __FILE__, __LINE__);
        $message = '...Erro ao cadastrar. Tente novamente...';
    }
}
```

**Migration isolada (`migrations/add_archive_columns.php`):**
```php
<?php
require_once __DIR__ . '/../config/database.php';
$pdo->exec("ALTER TABLE routes ADD COLUMN IF NOT EXISTS is_archived TINYINT(1) NOT NULL DEFAULT 0");
$pdo->exec("ALTER TABLE routes ADD COLUMN IF NOT EXISTS archive_reason TEXT NULL DEFAULT NULL");
echo "Migration completa.";
```

## Critérios de Sucesso

- Forçar erro de DB exibe "Erro na conexão com o banco de dados." (sem SQL ou caminhos)
- Erro real é registrado em `scratch/error.log`
- "Duplicate entry" em registro ainda exibe "Email ou CPF já cadastrado"
- Dashboard admin carrega sem executar ALTER TABLE
- Colunas `is_archived` e `archive_reason` existem (criadas pelo migration)
- Funcionalidades de arquivar/desarquivar rotas continuam funcionando

## Testes da Tarefa

- [ ] Testes de unidade: N/A
- [ ] Testes de integração: parar DB temporariamente, acessar index.php — confirmar mensagem genérica
- [ ] Testes E2E: fluxo de arquivar/desarquivar rota funciona; registro com email duplicado mostra mensagem correta

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `config/database.php` (modificar — linha 18)
- `pages/admin/dashboard.php` (modificar — remover linhas 6-17, ocultar getMessage em 130)
- `pages/register.php` (modificar — linha 128)
- `pages/recenseador/dashboard.php` (modificar — linha 34)
- `migrations/add_archive_columns.php` (criar)
- `includes/security.php` (modificar — adicionar `log_error()` helper, ou criar `includes/helpers.php`)
