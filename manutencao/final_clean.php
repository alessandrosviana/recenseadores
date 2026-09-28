<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando Mansões e Habitações...\n";
    
    // Usar os IDs específicos detectados na varredura para precisão total
    $pdo->query("UPDATE routes SET start_location = REPLACE(start_location, 'Mans?í es', 'Mansões') WHERE id = 5");
    $pdo->query("UPDATE routes SET start_location = REPLACE(start_location, 'Habita?º?í es', 'Habitações') WHERE id = 13");
    
    echo "Limpeza final concluída.";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
