<?php
require_once 'config/database.php';

echo "### Verificando rotas duplicadas da Luciana ###\n";

$stmt = $pdo->prepare("
    SELECT r.id, r.title, r.wizard_step, r.status, u.name 
    FROM routes r 
    JOIN users u ON r.user_id = u.id 
    WHERE u.name LIKE '%LUCIANA%'
");
$stmt->execute();
$routes = $stmt->fetchAll();

foreach ($routes as $r) {
    echo "ID: {$r['id']} | Título: {$r['title']} | Passo: {$r['wizard_step']} | Status: {$r['status']}\n";
}
?>
