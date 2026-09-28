# PRD — Hardening de Segurança do Sistema de Recenseadores CAU/DF

## Visão Geral

O Sistema de Recenseadores de Obras do CAU/DF está em produção e funcional, processando dados pessoais sensíveis (CPF, RG, CNH, certidões de antecedentes criminais, comprovante de residência) de cidadãos. Uma auditoria de segurança identificou **15 vulnerabilidades** (3 críticas, 4 altas, 5 médias, 3 baixas) que permitem comprometimento total do sistema sem autenticação, expondo dados pessoais sob risco de violação da LGPD.

Este PRD define o escopo de **hardening de segurança** — correção das vulnerabilidades identificadas **sem alterar qualquer funcionalidade já implementada**. O sistema continua operando com o mesmo fluxo de cadastro, aprovação, atribuição de rotas, wizard de andamento, calculadora de remuneração e pagamentos.

## Objetivos

- **Eliminar todas as 3 vulnerabilidades críticas** que permitem comprometimento sem autenticação
- **Mitigar as 4 vulnerabilidades altas** que permitem ataques CSRF, XSS e execução de código via upload
- **Reduzir as 5 vulnerabilidades médias** que expõem dados internos ou permitem brute force
- **Conformidade LGPD** — proteger dados pessoais de cidadãos contra vazamento
- **Zero regressão funcional** — nenhum fluxo de usuário existente deve ser alterado
- **Métrica de sucesso:** 0 scripts de debug acessíveis via web, 100% dos formulários com CSRF, 0 XSS confirmado por teste

## Histórias de Usuário

- Como **administrador do CAU/DF**, eu quero que o sistema seja protegido contra ataques externos para que dados de recenseadores não sejam vazados
- Como **recenseador**, eu quero que minha senha e documentos pessoais estejam seguros para que minha identidade não seja fraudada
- Como **administrador**, eu quero que ações administrativas (aprovar, rejeitar, criar admin) exijam confirmação de intenção para que não sejam executadas por ataque de CSRF
- Como **administrador**, eu quero que arquivos enviados sejam validados server-side para que código malicioso não seja executado no servidor
- Como **recenseador**, eu quero que a recuperação de senha seja robusta para que minha conta não seja takeover por terceiros
- Como **responsável LGPD do CAU/DF**, eu quero que logs e arquivos de debug não exponham dados pessoais para que o órgão esteja em conformidade com a Lei Geral de Proteção de Dados

## Funcionalidades Principais

### REQ-001 — Bloqueio de scripts de debug/manutenção expostos

O projeto possui 50+ scripts PHP na raiz (reset_admin.php, setup.php, repair_db.php, clean_db.php, list_users.php, check_*.php, fix_*.php, debug_*.php, etc.) acessíveis via web sem autenticação. reset_admin.php reseta a senha admin para "123456" e setup.php cria admin com senha "admin123".

- Criar `.htaccess` na raiz bloqueando acesso a todos os scripts de debug/maintenance/migration
- Padrão: `Require all denied` para arquivos matching `^(reset_admin|setup|repair_db|clean_db|reset_dados|list_all|list_users|check_|fix_|debug_|scan_|test_|migrate_|update_schema|add_|doc_|master_fix|binary_clean|final_clean|regex_clean|user_final_fix)\.php$`
- Não remover os arquivos do repositório (podem ser úteis em ambiente local)
- Não alterar nenhum script existente — apenas bloquear acesso web

#### Critérios de Aceite

- Acessar `https://[dominio]/reset_admin.php` retorna HTTP 403
- Acessar `https://[dominio]/setup.php` retorna HTTP 403
- Acessar `https://[dominio]/list_users.php` retorna HTTP 403
- Acessar `https://[dominio]/index.php` continua funcionando normalmente (200 OK)
- Acessar `https://[dominio]/pages/login.php` continua funcionando normalmente (200 OK)

---

### REQ-002 — Credenciais de banco de dados via variáveis de ambiente

As credenciais de banco de dados (usuário `root`, senha vazia) estão hardcoded em `config/database.php` e versionadas no git.

- Substituir valores hardcoded por `getenv()` com fallback para valores locais
- Criar `config/database.example.php` como template documentado
- Adicionar `config/database.php` ao `.gitignore` (preservar o arquivo local existente)
- O arquivo local continua funcionando — apenas deixa de ser versionado com credenciais reais

#### Critérios de Aceite

- `config/database.php` está no `.gitignore`
- `config/database.example.php` existe com placeholders documentados
- A aplicação conecta ao banco normalmente em ambiente local sem alteração de funcionalidade
- Em ambiente de produção, as credenciais são lidas de variáveis de ambiente

---

### REQ-003 — Proteção CSRF em todos os formulários

Nenhum formulário do sistema possui token CSRF. Todas as ações POST (login, registro, aprovar, rejeitar, cancelar, atribuir rota, criar admin, resetar senha, completar rota, arquivar, renovar, toggle active, salvar cálculo) são vulneráveis.

- Gerar token CSRF criptograficamente seguro na sessão (`bin2hex(random_bytes(32))`)
- Adicionar campo hidden `csrf` em todos os formulários HTML
- Validar token em todo POST usando `hash_equals()` antes de processar a ação
- Em caso de token inválido, retornar HTTP 403 sem processar
- Regenerar token após login (prevenir fixation)
- Não alterar o comportamento visual ou fluxo de nenhum formulário

#### Critérios de Aceite

- Todo formulário HTML contém `<input type="hidden" name="csrf" value="...">`
- Submeter um formulário sem o token csrf retorna HTTP 403
- Submeter um formulário com token válido executa a ação normalmente
- Submeter um formulário com token de outra sessão retorna HTTP 403
- O fluxo de login, registro, aprovação, atribuição de rota e todas as demais ações continuam funcionando

---

### REQ-004 — Sanitização XSS no campo area_details

O campo `area_details` (preenchido via Quill WYSIWYG) é renderizado como HTML raw em `pages/admin/user_routes.php:186` e `pages/recenseador/generate_contract.php:221`, permitindo injeção de JavaScript.

- Aplicar `htmlspecialchars()` ou sanitização HTML permitida (tags de formatação básicas: `<p>`, `<strong>`, `<em>`, `<ul>`, `<ol>`, `<li>`) ao renderizar `area_details`
- Não alterar como o campo é armazenado no banco — apenas como é exibido
- A aparência visual do conteúdo formatado deve ser preservada

#### Critérios de Aceite

- Injetar `<script>alert(1)</script>` no campo area_details não executa JavaScript na renderização
- Texto formatado com negrito, itálico e listas continua sendo exibido com formatação
- O contrato gerado em `generate_contract.php` exibe o conteúdo formatado normalmente

---

### REQ-005 — Validação server-side de uploads de arquivos

O bloco de validação de extensão PDF em `register.php:98-103` está vazio (não rejeita arquivos não-PDF). Não há verificação de MIME type nem limite de tamanho server-side. Diretório de upload com permissões 0777.

- Validar MIME type real do arquivo com `mime_content_type()` ou `finfo` (não apenas extensão)
- Validar tamanho máximo server-side (10MB por arquivo)
- Restringir extensões permitidas: PDF para documentos, JPG/PNG para imagens (onde já permitido)
- Criar `.htaccess` em `uploads/` com `php_flag engine off` para impedir execução de PHP no diretório
- Não alterar a interface de upload nem os campos aceitos — apenas adicionar validação server-side

#### Critérios de Aceite

- Enviar um arquivo `.php` renomeado para `.pdf` é rejeitado (MIME type não confere)
- Enviar um arquivo maior que 10MB é rejeitado com mensagem clara
- Enviar um PDF válido é aceito e armazenado normalmente
- Acessar `uploads/script.php` não executa código PHP (engine off)
- O fluxo de registro e upload de documentos continua funcionando normalmente

---

### REQ-006 — Endurecimento da recuperação de senha

A recuperação de senha atual requer apenas CPF + data de nascimento (dados públicos) sem limite de tentativas, sem notificação e com senha mínima de 6 caracteres.

- Implementar rate limiting: máximo 5 tentativas de verificação por CPF por hora
- Aumentar senha mínima para 8 caracteres com pelo menos 1 letra e 1 número
- Notificar por email (usando o mailer existente) após reset bem-sucedido
- Não alterar o fluxo visual de duas etapas nem os campos do formulário
- Não adicionar MFA nem OTP neste escopo (futuro)

#### Critérios de Aceite

- Após 5 tentativas falhas de verificação, o sistema bloqueia novas tentativas para aquele CPF por 1 hora
- Tentar criar senha com menos de 8 caracteres exibe erro
- Tentar criar senha sem letra ou sem número exibe erro
- Após reset bem-sucedido, um email é logado em `emails_log.txt` (usando mailer existente)
- O fluxo de recuperação normal (CPF válido + data correta + nova senha válida) continua funcionando

---

### REQ-007 — Configuração segura de sessão

O cookie de sessão não possui flags `HttpOnly` e `Secure`. Não há `session_regenerate_id()` após login (session fixation). Diretório de sessões com permissões 0777.

- Configurar `session_set_cookie_params()` com `httponly => true`, `secure => true`, `samesite => 'Strict'` antes de `session_start()`
- Chamar `session_regenerate_id(true)` imediatamente após autenticação bem-sucedida no login
- Alterar permissões do diretório `sessions/` para 0700 (leitura/escrita apenas pelo owner)
- Não alterar o timeout de 30 minutos nem o comportamento de expiração

#### Critérios de Aceite

- O cookie de sessão possui flags `HttpOnly` e `Secure` (verificável via DevTools)
- Após login, o session ID é diferente do pré-login
- O diretório `sessions/` tem permissões 0700
- O fluxo de login e logout continua funcionando normalmente
- A expiração por inatividade de 30 minutos continua funcionando

---

### REQ-008 — Ocultar detalhes de erro do banco de dados

Múltiplos pontos exibem `$e->getMessage()` ao usuário (config/database.php:18, admin/dashboard.php:130, register.php:128, recenseador/dashboard.php:34), expondo SQL, estrutura de tabelas e caminhos do servidor.

- Substituir exibição de `$e->getMessage()` por mensagem genérica ao usuário
- Logar o erro real em arquivo de log (`scratch/error.log` ou similar, já no `.gitignore`)
- Manter `PDO::ERRMODE_EXCEPTION` — apenas alterar o que é exibido, não o comportamento
- Não alterar o fluxo de tratamento de erros (ex: duplicate entry ainda mostra "Email ou CPF já cadastrado")

#### Critérios de Aceite

- Forçar um erro de banco de dados exibe mensagem genérica ("Erro interno. Tente novamente.") sem detalhes SQL
- O erro real é registrado no arquivo de log
- O erro de "Duplicate entry" continua exibindo "Email ou CPF já cadastrado"
- A aplicação continua funcionando normalmente em todos os fluxos

---

### REQ-009 — Bloqueio de arquivos de log e debug expostos

Arquivos `emails_log.txt`, `audit_ascii.txt`, `audit_result.txt`, `debug_user.txt`, `debug_user_ascii.txt` estão na raiz e acessíveis via web, expondo dados pessoais.

- Adicionar regra no `.htaccess` da raiz bloqueando acesso a arquivos `.txt` e `.log`
- Adicionar `emails_log.txt` ao `.gitignore` (preservar o arquivo local existente)
- Não mover nem renomear os arquivos — apenas bloquear acesso web

#### Critérios de Aceite

- Acessar `https://[dominio]/emails_log.txt` retorna HTTP 403
- Acessar `https://[dominio]/audit_result.txt` retorna HTTP 403
- O mailer continua escrevendo em `emails_log.txt` normalmente (apenas acesso web bloqueado)

---

### REQ-010 — Rate limiting no login

O endpoint de login não possui limite de tentativas, permitindo brute force de senhas.

- Implementar contador de tentativas falhas por email/IP usando a sessão ou uma tabela no banco
- Bloquear após 5 tentativas falhas consecutivas por 15 minutos
- Exibir mensagem clara informando o bloqueio e tempo restante
- Resetar contador após login bem-sucedido
- Não alterar o formulário visual nem os campos

#### Critérios de Aceite

- Após 5 tentativas com senha incorreta, o login é bloqueado por 15 minutos
- A mensagem de bloqueio indica o tempo restante
- Após login bem-sucedido, o contador é resetado
- O login com credenciais corretas (sem exceder tentativas) continua funcionando

---

### REQ-011 — Validação server-side de CPF

O CPF é aceito sem validação de dígitos verificadores — apenas máscara visual JavaScript.

- Implementar função de validação de CPF (dígitos verificadores) em PHP
- Validar no registro e na recuperação de senha antes de processar
- Exibir mensagem clara em caso de CPF inválido
- Não alterar a máscara JavaScript nem o campo visual

#### Critérios de Aceite

- Cadastrar com CPF "000.000.000-00" (dígitos inválidos) é rejeitado
- Cadastrar com CPF válido é aceito normalmente
- Recuperar senha com CPF inválido é rejeitado
- A máscara visual de CPF continua funcionando no frontend

---

### REQ-012 — Remoção de auto-migration do dashboard admin

`admin/dashboard.php:6-17` executa `ALTER TABLE` a cada carregamento de página, podendo causar locks em concorrência.

- Remover o bloco de auto-migration do `dashboard.php`
- Criar um script de migration isolado (ex: `migrations/add_archive_columns.php`) para executar manualmente uma única vez
- Adicionar o migration ao bloqueio do `.htaccess` (não acessível via web)
- O dashboard assume que as colunas já existem (foram criadas pelo migration)

#### Critérios de Aceite

- O dashboard admin carrega sem executar `ALTER TABLE`
- As colunas `is_archived` e `archive_reason` existem no banco (criadas pelo migration)
- As funcionalidades de arquivar/desarquivar rotas continuam funcionando
- O dashboard carrega mais rápido (sem query de verificação de colunas)

---

### REQ-013 — Headers de segurança HTTP

O sistema não envia headers de segurança: Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Strict-Transport-Security.

- Adicionar headers via `header()` no `config/session.php` (incluído em todas as páginas)
- `X-Frame-Options: DENY` — previne clickjacking
- `X-Content-Type-Options: nosniff` — previne MIME sniffing
- `Strict-Transport-Security: max-age=31536000` — força HTTPS
- `X-Content-Security-Policy` básico permitindo recursos do próprio domínio e CDNs já usados (Google Fonts, FontAwesome, Quill, ViaCEP)
- Não alterar nenhuma funcionalidade — apenas adicionar headers

#### Critérios de Aceite

- As páginas respondem com os headers de segurança definidos
- O carregamento de fonts (Google Fonts), ícones (FontAwesome) e editor (Quill) continua funcionando
- A busca de CEP via ViaCEP continua funcionando
- As iframes de preview de PDF em `view_docs.php` continuam funcionando (ajustar CSP se necessário)

---

### REQ-014 — Logger de email seguro

`includes/mailer.php:32` sempre retorna `true` mesmo se `mail()` falhar, dando confirmação falsa. O log grava corpo completo do email em texto plano.

- Retornar o resultado real de `mail()` (true/false)
- Manter o log em arquivo (já no `.gitignore` após REQ-009) mas registrar apenas destinatário, assunto e status — não o corpo completo com dados pessoais
- Não alterar a assinatura da função `sendEmail($to, $subject, $body)` nem os callers

#### Critérios de Aceite

- Se `mail()` falha, `sendEmail()` retorna `false`
- O log não contém o corpo do email (apenas destinatário, assunto, status)
- Os callers de `sendEmail()` continuam funcionando sem alteração

---

## Experiência do Usuário

### Personas

- **Administrador CAU/DF** — usa o painel admin diariamente para aprovar cadastros, atribuir rotas e liquidar pagamentos. As mudanças de segurança devem ser transparentes — nenhum fluxo alterado.
- **Recenseador** — se cadastra, envia documentos, aceita e executa rotas. As mudanças de segurança devem ser transparentes — apenas validações adicionais em caso de dados inválidos.
- **Responsável LGPD** — auditor externo que verifica conformidade. Deve encontrar dados pessoais protegidos.

### Impacto na UX

- **Zero alteração visual** — nenhuma tela, campo, botão ou fluxo é modificado
- Mensagens de erro em validações adicionais (CPF inválido, senha fraca, upload rejeitado) são exibidas no mesmo estilo das mensagens existentes
- O bloqueio de rate limiting exibe mensagem informativa dentro do card de login/recuperação

### Acessibilidade

- Sem impacto — nenhuma alteração de interface

## Restrições Técnicas de Alto Nível

- **Stack existente:** PHP + MySQL + PDO, sem framework — as correções devem usar PHP puro e funções nativas
- **Servidor:** HostGator (Apache) — usar `.htaccess` para configurações de segurança
- **Compatibilidade PHP:** manter compatibilidade com a versão em uso no servidor
- **LGPD:** dados pessoais (CPF, RG, documentos) devem ser protegidos contra acesso não autorizado e vazamento
- **Zero downtime:** as correções devem ser aplicáveis sem interromper o serviço em produção
- **Sem novas dependências:** não introduzir bibliotecas externas (Composer, etc.) — usar apenas funções nativas do PHP
- **Preservar funcionalidade:** nenhum fluxo existente (cadastro, aprovação, rotas, wizard, calculadora, pagamentos) deve ser alterado em comportamento

## Fora de Escopo

- **MFA / 2FA** — autenticação multifator não será implementada neste ciclo (futura melhoria)
- **Migração para framework** — o sistema permanece em PHP puro sem framework
- **Refatoração de código** — não há refatoração arquitetural, apenas correções de segurança pontuais
- **Novas funcionalidades** — nenhuma feature nova será adicionada
- **Testes automatizados** — não há suite de testes existente; testes manuais verificarão as correções
- **Auditoria de código completa** — este PRD endereça apenas as 15 vulnerabilidades identificadas na análise
- **HTTPS/TLS** — assumes-se que o servidor já tem ou terá certificado configurado a nível de infraestrutura
- **Backup/Disaster Recovery** — fora do escopo de segurança desta iteracao
- **Renovação do sistema de sessões** — apenas ajustes de configuração, não redesign completo
