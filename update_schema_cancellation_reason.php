<?php
require_once __DIR__ . '/config/database.php';

try {
    // Verificar se a coluna já existe
    $checkColumn = $pdo->query("SHOW COLUMNS FROM routes LIKE 'cancellation_reason'")->fetch();
    if (!$checkColumn) {
        $pdo->exec("ALTER TABLE routes ADD COLUMN cancellation_reason TEXT NULL AFTER renewal_reason");
        echo "SUCESSO: Coluna 'cancellation_reason' adicionada com sucesso na tabela 'routes'!\n";
    } else {
        echo "INFO: Coluna 'cancellation_reason' já existe na tabela 'routes'.\n";
    }
} catch (PDOException $e) {
    echo "ERRO ao rodar migração: " . $e->getMessage() . "\n";
}
?>
