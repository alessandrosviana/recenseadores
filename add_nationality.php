<?php
include 'config/database.php';
try {
    $rs = $pdo->query("SHOW COLUMNS FROM users LIKE 'nationality'");
    if ($rs->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN nationality VARCHAR(100) AFTER rg");
        echo "Added nationality column to users table.\n";
    } else {
        echo "Nationality column already exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
