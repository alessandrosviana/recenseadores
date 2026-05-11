<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';

echo "Checking routes table...\n";
$stmt = $pdo->query("DESCRIBE routes");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('start_time', $columns)) {
    echo "Adding column: start_time (DATETIME)\n";
    try {
        $pdo->exec("ALTER TABLE routes ADD COLUMN start_time DATETIME NULL");
    } catch (PDOException $e) {
        echo "Error adding start_time: " . $e->getMessage() . "\n";
    }
} else {
    echo "Column start_time already exists.\n";
}

echo "Done.\n";
?>