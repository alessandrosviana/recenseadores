<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';
try {
    $rs = $pdo->query("SHOW COLUMNS FROM users LIKE 'approved_at'");
    if ($rs->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN approved_at TIMESTAMP NULL AFTER status");
        echo "Coluna 'approved_at' adicionada com sucesso!\n";
    } else {
        echo "Coluna 'approved_at' já existe.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
