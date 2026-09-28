<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $stmt = $pdo->query("SELECT * FROM routes WHERE id=10");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch(PDOException $e) { echo $e->getMessage(); }
?>
