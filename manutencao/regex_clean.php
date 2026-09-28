<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando via Regex...\n";
    
    $stmt = $pdo->query("SELECT id, start_location FROM routes WHERE id IN (5, 13)");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        $clean = preg_replace('/Mans.{1,5}es/u', 'Mansões', $row['start_location']);
        $clean = preg_replace('/Habita.{1,10}es/u', 'Habitações', $clean);
        
        $update = $pdo->prepare("UPDATE routes SET start_location = ? WHERE id = ?");
        $update->execute([$clean, $row['id']]);
        echo "ID {$row['id']} atualizado.\n";
    }

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
