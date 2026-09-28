<?php
/**
 * SCRIPT DE PREPARAÇÃO DE BANCO PARA PRODUÇÃO / HOSTGATOR
 * 
 * ATENÇÃO: Este script limpa os dados de teste (rotas, documentos e recenseadores)
 * MANTENDO a conta do Administrador (alessandro@caudf.gov.br e outros admins) intacta.
 * 
 * Para executar, acesse no navegador: reset_dados_producao.php?confirm=yes
 */

require_once __DIR__ . '/config/database.php';

$message = '';
$executed = false;

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    try {
        // 1. Desativar Foreign Key Checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // 2. Limpar tabela de documentos enviados pelos recenseadores
        $stmtDoc = $pdo->exec("TRUNCATE TABLE documents");

        // 3. Limpar tabela de rotas / tarefas
        $stmtRoutes = $pdo->exec("TRUNCATE TABLE routes");

        // 4. Limpar histórico / movimentações de rotas se existir a tabela
        try {
            $pdo->exec("TRUNCATE TABLE route_history");
        } catch (Exception $e) {
            // Tabela pode não existir, ignora silenciosamente
        }

        // 5. Excluir usuários recenseadores de teste, mantendo APENAS a função 'admin'
        $stmtUsers = $pdo->prepare("DELETE FROM users WHERE role != 'admin'");
        $stmtUsers->execute();
        $deletedUsersCount = $stmtUsers->rowCount();

        // 6. Reativar Foreign Key Checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        $executed = true;
        $message = "Banco de dados preparado para produção com sucesso!<br>" .
                   "- Todas as rotas e tarefas foram limpas.<br>" .
                   "- Todos os documentos anexados foram limpos da base.<br>" .
                   "- $deletedUsersCount usuário(s) recenseador(es) de teste foram removidos.<br>" .
                   "- <strong>Sua conta de Administrador foi mantida intacta!</strong>";

    } catch (Exception $e) {
        $message = "Erro ao executar o reset: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preparar Banco para Produção - CAU/DF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; display: flex; justify-content: center; align-items: center; min-height: 80vh; margin: 0; }
        .card { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08); max-width: 600px; width: 100%; border: 1px solid #e2e8f0; }
        h1 { font-size: 1.5rem; color: #0f172a; margin-top: 0; display: flex; align-items: center; gap: 10px; }
        p { line-height: 1.6; color: #475569; }
        .alert-warning { background: #fffbebf5; border: 1px solid #fde68a; color: #92400e; padding: 1rem; border-radius: 8px; font-size: 0.9rem; margin: 1.5rem 0; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 1.25rem; border-radius: 8px; font-size: 0.95rem; margin: 1.5rem 0; line-height: 1.7; }
        .btn-reset { display: inline-block; background: #dc2626; color: white; text-decoration: none; padding: 0.8rem 1.5rem; font-weight: 700; border-radius: 6px; font-size: 0.95rem; transition: background 0.2s; }
        .btn-reset:hover { background: #b91c1c; }
        .btn-back { display: inline-block; background: #0284c7; color: white; text-decoration: none; padding: 0.8rem 1.5rem; font-weight: 700; border-radius: 6px; font-size: 0.95rem; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🛠️ Preparação de Banco para Produção</h1>

        <?php if ($executed): ?>
            <div class="alert-success">
                <strong>✅ SUCESSO!</strong><br>
                <?php echo $message; ?>
            </div>
            <a href="pages/admin/dashboard.php" class="btn-back">Ir para o Painel do Administrador</a>
        <?php else: ?>
            <p>Este script é utilizado para <strong>limpar todos os dados de teste</strong> do banco de dados antes de realizar o envio para o servidor da <strong>HostGator Brasil</strong>.</p>
            
            <div class="alert-warning">
                <strong>⚠️ O que este script faz ao ser confirmado:</strong><br>
                • Apaga todas as tarefas e rotas de teste da tabela <code>routes</code>.<br>
                • Apaga todos os documentos de teste da tabela <code>documents</code>.<br>
                • Apaga todos os recenseadores de teste da tabela <code>users</code>.<br>
                • <strong>MANTÉM INTACTA</strong> a conta de acesso do Administrador.
            </div>

            <p style="font-size: 0.85rem; color: #64748b;">Certifique-se de que realizou o backup antes da execução caso deseje guardar o histórico de testes.</p>

            <a href="reset_dados_producao.php?confirm=yes" 
               onclick="return confirm('Deseja realmente ZERAR os dados de teste e manter apenas o Administrador para Produção?');" 
               class="btn-reset">
                🚀 CONFIRMAR E LIMPAR DADOS DE TESTE
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
