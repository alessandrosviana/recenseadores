<?php
require_once 'config/database.php';

try {
    echo "Updating schema to version 5 (Admin Route Attachments)...<br>";

    $columns = ['admin_file_1', 'admin_file_2', 'admin_file_3'];
    
    foreach ($columns as $col) {
        try {
            $pdo->query("SELECT $col FROM routes LIMIT 1");
            echo "Column '$col' already exists.<br>";
        } catch (PDOException $e) {
            $sql = "ALTER TABLE routes ADD COLUMN $col VARCHAR(255) NULL";
            $pdo->exec($sql);
            echo "<strong style='color:green'>SUCCESS:</strong> Column '$col' added!<br>";
        }
    }

    echo "Schema updated successfully!";

} catch (PDOException $e) {
    echo "<strong style='color:red'>ERROR:</strong> " . $e->getMessage();
}
?>
