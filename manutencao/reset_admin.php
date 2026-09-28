<?php
require __DIR__ . '/config/database.php';
$hash = password_hash('123456', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@sistema.com'");
$stmt->execute([$hash]);
echo "Senha restaurada com sucesso para 123456!";
?>