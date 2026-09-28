<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';

echo "Database connection successful!\n";

$stmt = $pdo->query("SELECT id, name, email, role, status FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Current Users:\n";
foreach ($users as $user) {
    echo "- ID: {$user['id']}, {$user['name']}, Email: {$user['email']}, Role: {$user['role']}, Status: {$user['status']}\n";
}
?>