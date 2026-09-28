<?php
require_once 'config/database.php';
$u = $pdo->query("SELECT id, name FROM users")->fetchAll();
echo "USERS:\n";
print_r($u);
$r = $pdo->query("SELECT id, title, user_id, wizard_step FROM routes")->fetchAll();
echo "ROUTES:\n";
print_r($r);
?>
