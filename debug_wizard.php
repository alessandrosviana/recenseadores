<?php
require_once 'config/database.php';
$routes = $pdo->query("SELECT id, title, wizard_step, sei_pagamento FROM routes")->fetchAll();
echo "<pre>";
print_r($routes);
echo "</pre>";
?>
