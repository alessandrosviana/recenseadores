<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

// Force login check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

// Fetch Pending Users
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' AND role = 'recenseador'")->fetchAll();
$pending_html = '';

if (count($pending_users) > 0) {
    foreach ($pending_users as $user) {
        $pending_html .= '
        <div class="pending-card">
            <div>
                <h4 style="margin: 0; color: #333;">' . mb_strtoupper(htmlspecialchars($user['name']), 'UTF-8') . '</h4>
                <p style="margin: 0.2rem 0; color: #666;">' . htmlspecialchars($user['email']) . '</p>
            </div>
            <div class="pending-actions">
                <a href="../view_docs.php?user_id=' . $user['id'] . '" target="_blank" class="btn btn-outline" style="padding: 0.5rem 1rem;"><i class="fas fa-search"></i> ANALISAR DOCUMENTOS</a>
                <form method="post" style="display:flex; align-items:center;" onsubmit="return confirm(\'Deseja aprovar este cadastro e documentos?\');">
                    <input type="hidden" name="user_id" value="' . $user['id'] . '">
                    <input type="hidden" name="action" value="approve">
                    <button class="action-btn btn-approve"><i class="fas fa-check"></i> Aprovar</button>
                </form>
                <form method="post" style="display:flex; align-items:center;" onsubmit="return confirm(\'Tem certeza que deseja reprovar este cadastro?\');"><input type="hidden" name="user_id" value="' . $user['id'] . '"><input type="hidden" name="action" value="reject"><button class="action-btn btn-reject" title="Reprovar Cadastro"><i class="fas fa-times"></i></button></form>
            </div>
        </div>';
    }
} else {
    $pending_html = '<div class="text-center py-5"><p class="text-muted">Nenhum cadastro pendente.</p></div>';
}

echo json_encode([
    'count' => count($pending_users),
    'html' => $pending_html
]);
?>