<?php
require_once 'config/database.php';

// Check if admin exists
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin'");
if ($stmt->rowCount() == 0) {
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password, cpf, role, status) VALUES ('Administrador', 'admin@sistema.com', '$password', '000.000.000-00', 'admin', 'approved')";
    $pdo->exec($sql);
    echo "Admin user created.";
}
?>