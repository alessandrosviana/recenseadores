<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';

echo "Database connection successful!\n";

$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Columns in 'users' table:\n";
foreach ($columns as $col) {
    echo "- $col\n";
}

$required = ['rg', 'address', 'education_level'];
$missing = array_diff($required, $columns);

if (empty($missing)) {
    echo "\nALL REQUIRED COLUMNS PRESENT for register.php\n";
} else {
    echo "\nMISSING COLUMNS: " . implode(", ", $missing) . "\n";
    echo "You need to update the database schema.\n";
}
?>