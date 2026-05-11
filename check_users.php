<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $stmt = $pdo->query("SELECT id, name, city, address FROM users");
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        echo "ID: {$u['id']} | Nome: {$u['name']} | Cidade: {$u['city']} | End: {$u['address']}\n";
    }
} catch(PDOException $e) { echo $e->getMessage(); }
?>
