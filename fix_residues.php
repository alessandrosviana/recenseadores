<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    $replacements = [
        'Bot├ónico' => 'Botânico',
        'Com├®rcio' => 'Comércio',
        'Jarim' => 'Jardim' // Trocando "Jarim" por "Jardim"
    ];
    
    foreach ($replacements as $bad => $good) {
        $pdo->query("UPDATE routes SET address_complement = REPLACE(address_complement, '$bad', '$good')");
        $pdo->query("UPDATE routes SET address_neighborhood = REPLACE(address_neighborhood, '$bad', '$good')");
        $pdo->query("UPDATE routes SET start_location = REPLACE(start_location, '$bad', '$good')");
    }
    echo "Resíduos limpos!";

} catch(PDOException $e) {
    echo $e->getMessage();
}
?>
