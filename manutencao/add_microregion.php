<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';

try {
    $pdo->exec("ALTER TABLE routes ADD COLUMN microregion VARCHAR(255) NULL AFTER description");
    echo "Column microregion added to routes table successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>