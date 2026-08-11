<?php
require_once __DIR__ . '/config/database.php';

try {
    // Verificar se a coluna já existe
    $checkColumn = $pdo->query("SHOW COLUMNS FROM routes LIKE 'renewal_reason'")->fetch();
    if (!$checkColumn) {
        $pdo->exec("ALTER TABLE routes ADD COLUMN renewal_reason TEXT NULL AFTER observation");
        echo "SUCESSO: Coluna 'renewal_reason' adicionada com sucesso na tabela 'routes'!\n";
    } else {
        echo "INFO: Coluna 'renewal_reason' já existe na tabela 'routes'.\n";
    }
} catch (PDOException $e) {
    echo "ERRO ao rodar migração: " . $e->getMessage() . "\n";
}
?>
