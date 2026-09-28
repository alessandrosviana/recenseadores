<?php
require_once 'config/database.php';

$ids_to_delete = [23, 24];

foreach ($ids_to_delete as $id) {
    $stmt = $pdo->prepare("DELETE FROM routes WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo "Sucesso: Rota ID $id removida.\n";
    }
}
?>
