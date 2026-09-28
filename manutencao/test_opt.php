<?php
require 'C:\xampp\htdocs\recenseadores\config\database.php';
$approved_users = $pdo->query("SELECT * FROM users WHERE status = 'approved' AND role = 'recenseador'")->fetchAll();
foreach ($approved_users as $u) {
    echo '<option value="' . $u['id'] . '" data-microregion="' . htmlspecialchars($u['microregion'] ?? '') . '">' . mb_strtoupper(htmlspecialchars($u['name']), 'UTF-8') . '</option>' . PHP_EOL;
}
?>