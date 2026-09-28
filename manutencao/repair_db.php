<?php
require_once 'config/database.php';

try {
    echo "Running global repair...<br>";

    // Columns for routes
    $cols_to_add = [
        'wizard_step' => 'INT DEFAULT 2',
        'sei_pagamento' => 'VARCHAR(255) NULL',
        'admin_file_1' => 'VARCHAR(255) NULL',
        'admin_file_2' => 'VARCHAR(255) NULL',
        'admin_file_3' => 'VARCHAR(255) NULL'
    ];

    foreach ($cols_to_add as $col => $type) {
        try {
            $pdo->query("SELECT $col FROM routes LIMIT 1");
            echo "Column '$col' exists in routes.<br>";
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE routes ADD COLUMN $col $type");
            echo "Column '$col' ADDED to routes.<br>";
        }
    }

    // Initialize values for existing data
    $pdo->exec("UPDATE routes SET wizard_step = 2 WHERE wizard_step IS NULL OR wizard_step = 1");
    $pdo->exec("UPDATE routes SET wizard_step = 3 WHERE status = 'completed' AND wizard_step < 3");
    
    echo "Syncing users wizard_step to avoid confusion (although we use routes now)...<br>";
    $pdo->exec("UPDATE users SET wizard_step = 1 WHERE wizard_step IS NULL");

    echo "<b>REPAIR COMPLETED SUCCESSFULLY!</b>";

} catch (PDOException $e) {
    echo "<b>ERROR:</b> " . $e->getMessage();
}
?>
