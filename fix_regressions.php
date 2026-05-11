<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Corrigindo danos colaterais do Regex...\n";
    
    $replacements = [
        'Sãor' => 'Setor',
        'Sãos Dumont' => 'Santos Dumont',
        'Sãoeste' => 'Sudoeste',
        'Fiscalizaçãoda a Quadra' => 'Fiscalização em toda a Quadra',
        'Fiscalizaçãoeste Ferragens' => 'Fiscalização Noroeste Ferragens',
        'identificaçãoo' => 'identificação',
        'Fiscalização Bezer?úo' => 'Fiscalização Bezerrão'
    ];
    
    foreach ($replacements as $bad => $good) {
        $upLoc = $pdo->prepare("UPDATE routes SET start_location = REPLACE(start_location, ?, ?)");
        $upLoc->execute([$bad, $good]);
        
        $upTitle = $pdo->prepare("UPDATE routes SET title = REPLACE(title, ?, ?)");
        $upTitle->execute([$bad, $good]);
        
        echo "Revertido: $bad -> $good\n";
    }

} catch(PDOException $e) {
    echo $e->getMessage();
}
?>
