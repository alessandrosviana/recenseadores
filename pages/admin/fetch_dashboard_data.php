<?php
require_once '../../config/session.php';
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}

$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' AND role = 'recenseador'")->fetchAll();
$pending_html = '';

if (count($pending_users) > 0) {
    foreach ($pending_users as $user) {
        $pending_html .= '
        <div class="pending-card">
            <div>
                <h4 style="margin: 0; color: var(--ink-primary); font-weight: 700; font-size: 0.95rem;">' . htmlspecialchars($user['name']) . '</h4>
                <p style="margin: 0.2rem 0; color: var(--ink-tertiary); font-size: 0.82rem; display: flex; align-items: center; gap: 5px;"><i class="ph ph-envelope" style="font-size: 0.75rem;"></i> ' . htmlspecialchars($user['email']) . '</p>
            </div>
            <div class="pending-actions">
                <a href="../view_docs.php?user_id=' . $user['id'] . '" target="_blank" class="btn btn-outline" style="height: 40px; padding: 0 1rem; font-size: 0.78rem;"><i class="ph ph-magnifying-glass"></i> Analisar Docs</a>
                <form method="post" style="display:flex; align-items:center;" onsubmit="return confirm(\'Deseja aprovar este cadastro e documentos?\');">
                    ' . csrf_field() . '
                    <input type="hidden" name="user_id" value="' . $user['id'] . '">
                    <input type="hidden" name="action" value="approve">
                    <button class="btn-approve" style="height: 40px; padding: 0 1.2rem; font-size: 0.78rem;"><i class="ph ph-check"></i> Aprovar</button>
                </form>
                <form method="post" style="display:flex; align-items:center;" onsubmit="return confirm(\'Tem certeza que deseja reprovar este cadastro?\');">' . csrf_field() . '<input type="hidden" name="user_id" value="' . $user['id'] . '"><input type="hidden" name="action" value="reject"><button class="btn-reject" title="Reprovar Cadastro"><i class="ph ph-x"></i></button></form>
            </div>
        </div>';
    }
} else {
    $pending_html = '<div class="text-center py-5" style="border: 2px dashed var(--border-default); border-radius: var(--radius); background: var(--surface-1); padding: 3rem;"><i class="ph ph-check-circle" style="font-size: 2.5rem; color: var(--ink-muted); margin-bottom: 1rem;"></i><p class="text-muted">Nenhum cadastro pendente.</p></div>';
}

echo json_encode([
    'count' => count($pending_users),
    'html' => $pending_html
]);
?>
