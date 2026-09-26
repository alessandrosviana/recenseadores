<?php
// config/database.example.php — Template de configuracao (sem credenciais reais)
// Copie para config/database.php e ajuste os valores ou defina via variaveis de ambiente.

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'sistema_recenseadores';
$username = getenv('DB_USER') ?: '';
$password = getenv('DB_PASS') ?: '';

define('BASE_URL', '/');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexao com o banco de dados.");
}
?>
