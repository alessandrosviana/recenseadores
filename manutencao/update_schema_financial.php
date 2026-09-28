<?php
require_once 'config/database.php';

try {
    echo "Adding financial columns to 'routes' table...<br>";

    $columns = [
        'calc_q_escritorio' => "INT DEFAULT 0",
        'calc_u_escritorio' => "DECIMAL(10,2) DEFAULT 0",
        'calc_q_km_fix' => "INT DEFAULT 0",
        'calc_u_km_fix' => "DECIMAL(10,2) DEFAULT 0",
        'calc_q_alim' => "INT DEFAULT 0",
        'calc_u_alim' => "DECIMAL(10,2) DEFAULT 0",
        'calc_q_km_var' => "INT DEFAULT 0",
        'calc_u_km_var' => "DECIMAL(10,2) DEFAULT 0",
        'calc_q_obras' => "INT DEFAULT 0",
        'calc_u_obras' => "DECIMAL(10,2) DEFAULT 0",
        'calc_total_fixed' => "DECIMAL(10,2) DEFAULT 0",
        'calc_total_variable' => "DECIMAL(10,2) DEFAULT 0",
        'calc_grand_total' => "DECIMAL(10,2) DEFAULT 0",
        'calc_gas_price' => "DECIMAL(10,3) DEFAULT 0"
    ];

    foreach ($columns as $col => $definition) {
        try {
            $pdo->query("SELECT $col FROM routes LIMIT 1");
            echo "Column '$col' already exists.<br>";
        } catch (PDOException $e) {
            $pdo->exec("ALTER TABLE routes ADD COLUMN $col $definition");
            echo "Column '$col' added.<br>";
        }
    }

    echo "<strong>Success:</strong> Database updated.";

} catch (PDOException $e) {
    echo "<strong>Error:</strong> " . $e->getMessage();
}
