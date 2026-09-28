# Tarefa 9.0: Sanitização XSS no campo area_details

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-004 — Sanitização XSS no campo area_details

## Dependências

- 3.0 (includes/security.php — função sanitize_html)

## Estimativa

- **Tamanho**: P
- **Horas estimadas**: 1h

## Visão Geral

Aplicar `sanitize_html()` do `includes/security.php` ao renderizar o campo `area_details` em duas páginas: `pages/admin/user_routes.php:186` e `pages/recenseador/generate_contract.php:221`. Não alterar como o campo é armazenado — apenas como é exibido.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — estilo procedural
</skills>

<requirements>
1. Em `pages/admin/user_routes.php:186`: substituir `echo $route['area_details']` por `echo sanitize_html($route['area_details'] ?? '')`
2. Em `pages/recenseador/generate_contract.php:221`: substituir `echo $data['area_details']` por `echo sanitize_html($data['area_details'] ?? '')`
3. Verificar se há outros pontos que renderizam `area_details` sem escape e aplicar a mesma correção
</requirements>

## Subtarefas

- [ ] 9.1 `pages/admin/user_routes.php` — aplicar `sanitize_html()` no echo de `area_details`
- [ ] 9.2 `pages/recenseador/generate_contract.php` — aplicar `sanitize_html()` no echo de `area_details`
- [ ] 9.3 Buscar outros echo de `area_details` no projeto e aplicar sanitização

## Detalhes de Implementação

A função `sanitize_html()` (criada na task 3.0) permite apenas tags: `<p>`, `<strong>`, `<em>`, `<ul>`, `<ol>`, `<li>`, `<br>`. Remove `<script>`, atributos `on*`, `<iframe>`, etc.

**Antes:**
```php
<?php echo $route['area_details']; ?>
```

**Depois:**
```php
<?php echo sanitize_html($route['area_details'] ?? ''); ?>
```

## Critérios de Sucesso

- Injetar `<script>alert(1)</script>` no campo area_details não executa JavaScript na renderização
- Texto formatado com negrito, itálico e listas continua sendo exibido com formatação visual
- O contrato gerado em `generate_contract.php` exibe o conteúdo formatado normalmente
- A instrução/descrição da rota em `user_routes.php` exibe o conteúdo formatado normalmente

## Testes da Tarefa

- [ ] Testes de unidade: `sanitize_html('<p>ok</p><script>alert(1)</script>')` não contém `<script>`
- [ ] Testes de integração: criar rota com area_details contendo script, visualizar em user_routes.php — confirmar que não executa
- [ ] Testes E2E: fluxo de visualização de rota com area_details formatado — confirmar aparência normal

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `pages/admin/user_routes.php` (modificar — linha 186)
- `pages/recenseador/generate_contract.php` (modificar — linha 221)
