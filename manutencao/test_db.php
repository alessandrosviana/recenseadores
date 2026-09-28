<?php
require_once 'config/database.php';
echo "Checking columns in routes table...<br>";
$stmt = $pdo->query("SHOW COLUMNS FROM routes");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($cols);
echo "<br><br>Updating a route to wizard_step = 5...<br>";
$stmt = $pdo->prepare("UPDATE routes SET wizard_step = 5 WHERE id = 1");
$stmt->execute();
echo "Done. Checking wizard_step for id=1...<br>";
$res = $pdo->query("SELECT wizard_step FROM routes WHERE id = 1")->fetch();
print_r($res);
?>
