<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando Nomes e Endereços de Usuários...\n";
    
    $replacements = [
        'JO?âO' => 'JOÃO',
        'ROLD?âO' => 'ROLDÃO',
        '?í¼rea' => 'Área',
        'Brand?úo' => 'Brandão',
        'Lávia' => 'Lívia',
        '?Â¡' => 'á',
        '?í­' => 'í'
    ];
    
    foreach ($replacements as $search => $replace) {
        $up = $pdo->prepare("UPDATE users SET name = REPLACE(name, ?, ?), address = REPLACE(address, ?, ?), city = REPLACE(city, ?, ?) ");
        $up->execute([$search, $replace, $search, $replace, $search, $replace]);
        if ($up->rowCount() > 0) {
            echo "Corrigido: $search -> $replace\n";
        }
    }

} catch(PDOException $e) {
    echo $e->getMessage();
}
?>
