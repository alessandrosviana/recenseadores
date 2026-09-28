<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';
try {
    $stmt = $pdo->exec("UPDATE users SET approved_at = created_at WHERE status = 'approved' AND approved_at IS NULL");
    echo "Preenchidos $stmt registros com a data de cadastro.\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
