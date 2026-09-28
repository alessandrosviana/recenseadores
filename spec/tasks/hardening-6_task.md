# Tarefa 6.0: Validação server-side de uploads de arquivos

<critical>Ler os arquivos de prd.md e techspec.md desta pasta, se você não ler esses arquivos sua tarefa será invalidada</critical>

## Requisitos Atendidos

- REQ-005 — Validação server-side de uploads de arquivos

## Dependências

- 1.0 (.htaccess em uploads/ com php_flag engine off)

## Estimativa

- **Tamanho**: M
- **Horas estimadas**: 2-3h

## Visão Geral

Adicionar validação server-side de MIME type e tamanho em todos os pontos de upload: `register.php` (documentos do cadastro), `upload_docs.php` (documento avulso), `admin/dashboard.php` (admin_route files, payment_pdf), `recenseador/dashboard.php` (report files). Preencher o bloco de validação vazio em `register.php:98-103`.

<skills>
### Conformidade com Skills Padrões

- `.agents/rules/code-standards.md` — estilo procedural
</skills>

<requirements>
1. Em `register.php`: preencher o bloco vazio de validação (linhas 98-103) — rejeitar se extensão não for PDF (ou jpg/png para doc_rg e doc_cnh)
2. Em todos os uploads: validar MIME type com `finfo_file()` (não apenas extensão)
3. Validar tamanho máximo: 10MB por arquivo (`$_FILES[...]['size'] > 10 * 1024 * 1024`)
4. Em caso de rejeição, pular o arquivo (não salvar, não abortar o registro)
5. Não alterar a interface de upload nem os campos `accept` do HTML
</requirements>

## Subtarefas

- [ ] 6.1 `register.php` — preencher bloco de validação de extensão (linhas 98-103) + adicionar validação MIME e tamanho
- [ ] 6.2 `upload_docs.php` — adicionar validação MIME com `finfo_file()` + tamanho 10MB
- [ ] 6.3 `admin/dashboard.php` — adicionar validação MIME nos uploads de admin_route files e payment_pdf
- [ ] 6.4 `recenseador/dashboard.php` — adicionar validação MIME nos uploads de report files

## Detalhes de Implementação

**MIME types permitidos:**
- PDF: `application/pdf`
- JPG: `image/jpeg`
- PNG: `image/png`

**Padrão de validação (inserir antes de `move_uploaded_file`):**
```php
$allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png'];
$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp_name);
if (!in_array($mime, $allowed_mimes)) continue; // rejeita silenciosamente
if ($_FILES[$input_name]['size'] > 10 * 1024 * 1024) continue; // 10MB
```

**Em `register.php`**, o bloco atual (linhas 98-103) está vazio. Preencher com:
```php
if ($ext != 'pdf') {
    if (!($input_name == 'doc_rg' || $input_name == 'doc_cnh') || !in_array($ext, ['jpg', 'jpeg', 'png'])) {
        continue; // rejeita extensão não permitida
    }
}
// Validar MIME type real
$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp_name);
if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'])) continue;
if ($_FILES[$input_name]['size'] > 10 * 1024 * 1024) continue;
```

## Critérios de Sucesso

- Enviar arquivo `.php` renomeado para `.pdf` é rejeitado (MIME type não confere)
- Enviar arquivo maior que 10MB é rejeitado
- Enviar PDF válido é aceito e armazenado normalmente
- Enviar JPG/PNG para doc_rg e doc_cnh é aceito
- Enviar JPG para doc_diploma (que aceita apenas PDF) é rejeitado
- O fluxo de registro com documentos válidos continua funcionando
- O upload de relatórios no dashboard recenseador continua funcionando
- O upload de payment_pdf no admin continua funcionando

## Testes da Tarefa

- [ ] Testes de unidade: N/A
- [ ] Testes de integração: tentar upload de arquivo .php renomeado .pdf — confirmar rejeição
- [ ] Testes E2E: fluxo de registro completo com PDFs válidos — confirmar sucesso

<critical>SEMPRE CRIE E EXECUTE OS TESTES DA TAREFA ANTES DE CONSIDERÁ-LA FINALIZADA</critical>

## Arquivos relevantes

- `pages/register.php` (modificar — linhas 92-113)
- `pages/upload_docs.php` (modificar)
- `pages/admin/dashboard.php` (modificar — uploads de admin_route e payment)
- `pages/recenseador/dashboard.php` (modificar — uploads de report)
