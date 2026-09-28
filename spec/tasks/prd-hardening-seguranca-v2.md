# PRD — Hardening de Segurança v2 (Reauditoria) do Sistema de Recenseadores CAU/DF

## Visão Geral

A primeira rodada de hardening (PRD v1, REQ-001 a REQ-014) foi implementada e validada. Uma reauditoria de segurança conduzida com framework de cybersecurity analysis (CIA triad, STRIDE, OWASP Top 10, MITRE ATT&CK) identificou **5 vulneralidades residuais e novas** (1 crítica, 1 alta, 2 médias, 1 baixa) que persistem ou foram introduzidas apesar do hardening anterior.

A mais grave (VULN-01) permite que qualquer pessoa na internet acesse documentos sensíveis de identidade (RG, CNH, antecedentes criminais, comprovante de residência) de todos os recenseadores sem autenticação, configurando violação direta da LGPD (Art. 46).

Este PRD define o escopo da **segunda rodada de hardening** — correção das 5 vulneralidades identificadas **sem alterar funcionalidade existente**.

## Objetivos

- **Eliminar a 1 vulnerabilidade crítica** (VULN-01: acesso não-autenticado a documentos)
- **Mitigar a 1 vulnerabilidade alta** (VULN-02: credenciais ainda versionadas no git)
- **Reduzir as 2 vulnerabilidades médias** (VULN-03: IDOR em aprovação de docs; VULN-04: política de senha fraca)
- **Mitigar a 1 vulnerabilidade baixa** (VULN-05: auto-login pós-cadastro)
- **Conformidade LGPD** — garantir que dados pessoais sensíveis exigem autenticação para acesso
- **Zero regressão funcional** — nenhum fluxo de usuário existente deve ser alterado
- **Métrica de sucesso:** 0 documentos acessíveis sem sessão válida, 0 credenciais no git, 0 IDOR confirmado por teste

## Histórias de Usuário

- Como **responsável LGPD do CAU/DF**, eu quero que documentos sensíveis (RG, CNH, antecedentes) exigam autenticação para acesso para que dados pessoais não sejam expostos publicamente na internet
- Como **administrador**, eu quero que a aprovação de documentos só afete documentos do usuário sendo analisado para que não haja erro de aprovação cruzada
- Como **recenseador**, eu quero que minha senha tenha requisitos de complexidade adequados para que minha conta resista a ataques de credential stuffing
- Como **administrador de infraestrutura**, eu quero que credenciais de banco não estejam no repositório git para que desenvolvedores e atacantes não tenham acesso direto ao banco
- Como **administrador do CAU/DF**, eu quero que usuários recém-cadastrados não acessem o painel até serem aprovados para que o fluxo de credenciamento seja respeitado

## Funcionalidades Principais

### REQ-V2-01 — Servir documentos exclusivamente via PHP com autenticação (CRITICAL)

`uploads/.htaccess` (criado na v1) bloqueia execução de PHP mas concede `Require all granted` para arquivos PDF/JPG/PNG, permitindo acesso direto e sem autenticação a documentos sensíveis. Os nomes de arquivo seguem o padrão `{user_id}_{input_name}_{timestamp}.{ext}`, onde `user_id` é sequencial e `timestamp` é previsível, permitindo enumeração.

- Criar `pages/serve_document.php` que:
  - Verifica sessão autenticada (`user_id` + `role`)
  - Recupera o caminho do documento do banco pelo `doc_id` (nunca confiar no path passado pelo usuário)
  - Valida que o usuário tem permissão (admin pode ver qualquer documento; recenseador só pode ver os próprios)
  - Serve o arquivo com `readfile()` + headers apropriados (`Content-Type`, `Content-Disposition: inline`)
  - Rejeita path traversal (`..`, `://`, null bytes)
- Alterar `uploads/.htaccess` de `Require all granted` para `Require all denied` (bloquear TODO acesso web direto)
- Atualizar `pages/view_docs.php` para usar `serve_document.php?doc_id=N` em vez do path direto
- Atualizar `pages/recenseador/dashboard.php` (substituição de documentos) para usar o mesmo endpoint
- Não alterar o armazenamento nem a estrutura de pastas — apenas o modo de servir os arquivos

#### Critérios de Aceite

- Acessar `https://[dominio]/uploads/5_doc_rg_1695912000.jpg` retorna HTTP 403
- Acessar `https://[dominio]/uploads/qualquer.pdf` retorna HTTP 403
- Admin autenticado consegue visualizar/baixar documentos de qualquer usuário via `serve_document.php`
- Recenseador autenticado consegue visualizar apenas seus próprios documentos
- Usuário não autenticado recebe HTTP 403 ao acessar `serve_document.php`
- Path traversal (`serve_document.php?doc_id=../../config/database.php`) é rejeitado
- O fluxo de visualização de documentos em `view_docs.php` continua funcionando para admin
- O iframe de preview de PDF em `view_docs.php` continua funcionando

---

### REQ-V2-02 — Remover credenciais do versionamento Git (HIGH)

Apesar de `config/database.php` estar no `.gitignore` (adicionado na v1, REQ-002), o arquivo já era rastreado pelo git antes da adição, e o Git continua versionando-o. `git ls-files --error-unmatch config/database.php` confirma que o arquivo está rastreado.

- Executar `git rm --cached config/database.php` para remover do index sem deletar o arquivo local
- Commitar a remoção
- Garantir que `config/database.example.php` (já existe) é o template documentado
- Garantir que o arquivo local `config/database.php` continua funcionando (apenas deixa de ser versionado)
- Adicionar verificação no README ou COMO_RODAR.md instruindo a copiar `database.example.php` para `database.php` em novos ambientes

#### Critérios de Aceite

- `git ls-files --error-unmatch config/database.php` retorna erro (não rastreado)
- `config/database.php` existe localmente e a aplicação conecta normalmente
- `config/database.example.php` está versionado com placeholders documentados
- O histórico do git ainda contém versões anteriores (não há rewrite de histórico neste escopo)

---

### REQ-V2-03 — Corrigir IDOR na aprovação de documentos (MEDIUM)

`pages/view_docs.php:30-34` aprova/rejeita documentos pelo `doc_id` sem verificar se o documento pertence ao `user_id` sendo visualizado. Um admin pode aprovar documento de outro usuário manipulando `doc_id` no POST.

- Adicionar `AND user_id = ?` na query de UPDATE de aprovação/rejeição de documentos
- Passar o `$user_id` da URL (já validado como int) como parâmetro adicional
- Se `rowCount() === 0`, exibir mensagem "Documento não pertence a este usuário"
- Não alterar o fluxo visual nem os botões de aprovar/rejeitar

#### Critérios de Aceite

- Aprovar documento `doc_id=10` na visualização do `user_id=5` quando o doc pertence ao `user_id=7` não afeta o documento
- Aprovar documento do usuário correto funciona normalmente
- A mensagem de erro é exibida quando o documento não pertence ao usuário
- O status do documento no banco não é alterado em caso de mismatch

---

### REQ-V2-04 — Fortalecer política de senha (MEDIUM)

`includes/security.php:103-115` (`validate_password_strength`) exige apenas 8 caracteres, 1 letra e 1 número. Senhas como `aaaaaa1` são aceitas. Não há requisito de maiúscula, minúscula, caractere especial ou verificação contra senhas comuns.

- Adicionar requisito de pelo menos 1 letra maiúscula
- Adicionar requisito de pelo menos 1 letra minúscula
- Adicionar requisito de pelo menos 1 caractere especial (`!@#$%^&*()_+-=[]{}|;:,.<>?`)
- Aumentar mínimo para 10 caracteres (alinhado com NIST SP 800-63B)
- Adicionar blacklist de senhas comuns (`password`, `123456`, `qwerty`, `admin`, `iloveyou`, etc.)
- Aplicar a validação fortalecida em `register.php` e `forgot_password.php` (reset)
- Atualizar placeholders nos formulários para refletir a nova política ("Mínimo 10 caracteres, com maiúscula, minúscula, número e especial")
- Não alterar o layout visual nem os campos do formulário

#### Critérios de Aceite

- Cadastrar com senha `aaaaaa1` é rejeitado (falta maiúscula, especial, < 10 chars)
- Cadastrar com senha `password123` é rejeitado (blacklist)
- Cadastrar com senha `Recense@2026` é aceito
- Reset de senha com senha fraca é rejeitado
- A mensagem de erro indica qual requisito não foi atendido
- O fluxo de cadastro e reset com senha válida continua funcionando

---

### REQ-V2-05 — Remover auto-login pós-cadastro (LOW)

`pages/register.php:129-131` autentica o usuário automaticamente após o cadastro e redireciona ao dashboard, mesmo com `status = 'pending'`. O usuário acessa o painel sem aprovação admin.

- Remover as 3 linhas que setam `$_SESSION` e redirecionam ao dashboard
- Redirecionar para `login.php?registered=true` após cadastro bem-sucedido
- Exibir mensagem de sucesso no `login.php` quando `$_GET['registered']` estiver presente ("Cadastro enviado com sucesso! Aguarde aprovação do CAU/DF.")
- Não alterar o formulário de cadastro nem os campos

#### Critérios de Aceite

- Após cadastro bem-sucedido, o usuário é redirecionado para `login.php?registered=true`
- A tela de login exibe mensagem "Cadastro enviado com sucesso! Aguarde aprovação."
- O usuário NÃO está autenticado após o cadastro (sem session ativa)
- O login com as credenciais recém-cadastradas funciona (mas dashboard mostra "Em Análise")
- O fluxo de cadastro com dados válidos continua funcionando

---

## Experiência do Usuário

### Personas

- **Administrador CAU/DF** — usa o painel admin diariamente. As mudanças de segurança são transparentes — apenas o acesso a documentos passa por autenticação (já está autenticado).
- **Recenseador** — se cadastra e envia documentos. Após cadastro, vê mensagem de "Aguarde aprovação" em vez de acessar o painel. Senhas devem ser mais fortes (mensagens claras de validação).
- **Responsável LGPD** — auditor externo. Deve encontrar documentos sensíveis protegidos por autenticação e credenciais fora do repositório.

### Impacto na UX

- **REQ-V2-01:** Zero impacto para usuários autenticados. Documentos continuam sendo visualizados normalmente.
- **REQ-V2-02:** Zero impacto. Arquivo local continua funcionando.
- **REQ-V2-03:** Zero impacto. Aprovação de documentos do usuário correto funciona igual.
- **REQ-V2-04:** Pequeno impacto — senhas precisam ser mais complexas. Mensagens de erro claras guiam o usuário.
- **REQ-V2-05:** Mudança positiva — usuário vê claramente que precisa aguardar aprovação, em vez de acessar um painel vazio.

### Acessibilidade

- Sem impacto — nenhuma alteração de interface significativa

## Restrições Técnicas de Alto Nível

- **Stack existente:** PHP + MySQL + PDO, sem framework — as correções devem usar PHP puro e funções nativas
- **Servidor:** HostGator (Apache) — usar `.htaccess` para bloqueio de diretório
- **Compatibilidade PHP:** manter compatibilidade com a versão em uso no servidor
- **LGPD:** documentos pessoais (RG, CNH, antecedentes criminais) devem exigir autenticação para acesso
- **Zero downtime:** as correções devem ser aplicáveis sem interromper o serviço em produção
- **Sem novas dependências:** não introduzir bibliotecas externas — usar apenas funções nativas do PHP
- **Preservar funcionalidade:** nenhum fluxo existente (cadastro, aprovação, rotas, wizard, calculadora, pagamentos) deve ser alterado em comportamento

## Fora de Escopo

- **MFA / 2FA** — autenticação multifator continua fora do escopo (futura melhoria)
- **Criptografia de documentos at-rest** — documentos servidos via PHP mas armazenados em texto plano no disco (futura melhoria)
- **Rewrite de histórico git** — `git filter-branch` / BFG para remover credenciais do histórico será tratado separadamente se necessário
- **Renovação do sistema de uploads** — estrutura de pastas e nomenclatura permanecem; apenas o modo de servir muda
- **Testes automatizados** — não há suite de testes existente; testes manuais verificarão as correções
- **Auditoria de infraestrutura** — HTTPS, firewall, hardening do servidor MySQL ficam fora do escopo
- **Bug bounty / pentest externo** — fora do escopo desta iteracao
