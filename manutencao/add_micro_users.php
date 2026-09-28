<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN microregion VARCHAR(255) NULL AFTER status");
    echo "Column microregion added to users table successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>