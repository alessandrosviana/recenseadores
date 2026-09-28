# Tasks — Hardening de Segurança v2

## Status: Todas implementadas

| ID | Task | Status | Arquivos |
|---|---|---|---|
| V2-01 | Servir documentos com autenticação | ✅ Done | serve_upload.php, serve_document.php, uploads/.htaccess, view_docs.php |
| V2-02 | Remover credenciais do git | ✅ Done | git rm --cached config/database.php |
| V2-03 | IDOR em aprovação de documentos | ✅ Done | view_docs.php |
| V2-04 | Política de senha fortalecida | ✅ Done | includes/security.php |
| V2-05 | Remover auto-login pós-cadastro | ✅ Done | register.php, login.php |
| V2-06 | Mover arquivos não-sistema para manutencao/ | ✅ Done | manutencao/ (69 arquivos), .htaccess |
