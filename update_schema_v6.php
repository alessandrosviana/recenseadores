<?php
require_once 'config/database.php';

try {
    echo "Updating schema to version 6 (Wizard on Routes)...<br>";

    // Add wizard_step column to routes
    try {
        $pdo->query("SELECT wizard_step FROM routes LIMIT 1");
        echo "Column 'wizard_step' already exists in routes.<br>";
    } catch (PDOException $e) {
        $sql = "ALTER TABLE routes ADD COLUMN wizard_step INT DEFAULT 1";
        $pdo->exec($sql);
        echo "<strong style='color:green'>SUCCESS:</strong> Column 'wizard_step' added to routes!<br>";
    }

    // Add sei_pagamento column to routes
    try {
        $pdo->query("SELECT sei_pagamento FROM routes LIMIT 1");
        echo "Column 'sei_pagamento' already exists in routes.<br>";
    } catch (PDOException $e) {
        $sql = "ALTER TABLE routes ADD COLUMN sei_pagamento VARCHAR(255) NULL";
        $pdo->exec($sql);
        echo "<strong style='color:green'>SUCCESS:</strong> Column 'sei_pagamento' added to routes!<br>";
    }

    echo "Schema updated successfully!";

} catch (PDOException $e) {
    echo "<strong style='color:red'>ERROR:</strong> " . $e->getMessage();
}
?>
