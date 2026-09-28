<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $stmt = $pdo->query("SELECT id, title, start_location FROM routes WHERE id=10");
    foreach($stmt->fetchAll() as $r) {
        echo "ID: {$r['id']} \nTitle: {$r['title']} \nLoc: {$r['start_location']}\n";
    }
} catch(PDOException $e) { echo $e->getMessage(); }
?>
