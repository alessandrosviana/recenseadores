<?php
require_once 'config/database.php';

try {
    echo "Updating schema to version 8 (Add is_archived to routes)...<br>";

    // Check if column exists
    $check = $pdo->query("SHOW COLUMNS FROM routes LIKE 'is_archived'")->fetch();
    if (!$check) {
        $sqlAlter = "ALTER TABLE routes ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0";
        $pdo->exec($sqlAlter);
        echo "<strong style='color:green'>SUCCESS:</strong> Column 'is_archived' added to 'routes' table!<br>";
    } else {
        echo "<strong style='color:orange'>INFO:</strong> Column 'is_archived' already exists in 'routes' table.<br>";
    }

    echo "Schema updated successfully!";

} catch (PDOException $e) {
    echo "<strong style='color:red'>ERROR:</strong> " . $e->getMessage();
}
?>
