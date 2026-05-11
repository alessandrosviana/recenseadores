<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sistema_recenseadores', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if columns exist
    $rs = $pdo->query("SHOW COLUMNS FROM users LIKE 'processo_sei'");
    if ($rs->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN processo_sei VARCHAR(100) DEFAULT NULL");
        echo "Added processo_sei\n";
    }

    $rs = $pdo->query("SHOW COLUMNS FROM users LIKE 'contrato'");
    if ($rs->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN contrato VARCHAR(100) DEFAULT NULL");
        echo "Added contrato\n";
    }
    
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
