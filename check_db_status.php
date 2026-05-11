<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- STATUS DA ROTA DO FLAVIO ---\n";
    $stmt = $pdo->prepare("SELECT r.id, r.title, r.status, r.wizard_step, r.microregion, u.name 
                          FROM routes r 
                          JOIN users u ON r.user_id = u.id 
                          WHERE u.name LIKE ?");
    $stmt->execute(['%FLAVIO%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "Nenhuma rota encontrada para FLAVIO.\n";
    } else {
        foreach ($results as $r) {
            echo "ID: {$r['id']} | Título: {$r['title']} | Status: {$r['status']} | Wizard Step: {$r['wizard_step']} | Micro: {$r['microregion']} | Nome: {$r['name']}\n";
        }
    }

    echo "\n--- CONTAGEM DE TAREFAS CONCLUÍDAS (STATUS='completed') ---\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM routes WHERE status = 'completed'");
    $count = $stmt->fetch();
    echo "Total no banco: " . $count['total'] . "\n";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
