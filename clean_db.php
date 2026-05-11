<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando nomes de microrregiões...\n";
    
    // Corrige São Sebastião
    $stmt = $pdo->prepare("UPDATE routes SET microregion = 'São Sebastião (RA XIV)' WHERE microregion LIKE 'S%o Sebasti%o%'");
    $stmt->execute();
    echo "São Sebastião corrigido: " . $stmt->rowCount() . " registros.\n";

    // Corrige Brasília (se houver)
    $stmt = $pdo->prepare("UPDATE users SET city = 'Brasília' WHERE city LIKE 'Bras%ia%'");
    $stmt->execute();

    echo "Limpeza concluída.";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
