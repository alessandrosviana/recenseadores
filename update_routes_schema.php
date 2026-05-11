<?php
require_once 'config/database.php';

try {
    echo "Atualizando tabela routes...<br>";

    // Adicionar colunas para observação e arquivos
    $sql = "ALTER TABLE routes 
            ADD COLUMN observation TEXT NULL AFTER status,
            ADD COLUMN report_file_1 VARCHAR(255) NULL AFTER observation,
            ADD COLUMN report_file_2 VARCHAR(255) NULL AFTER report_file_1,
            ADD COLUMN report_file_3 VARCHAR(255) NULL AFTER report_file_2,
            ADD COLUMN completed_at TIMESTAMP NULL AFTER created_at";

    $pdo->exec($sql);

    echo "<strong style='color:green'>SUCESSO:</strong> Colunas 'observation', 'report_file_1-3' e 'completed_at' adicionadas!";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column") !== false) {
        echo "<strong style='color:orange'>AVISO:</strong> Colunas já existem.";
    } else {
        echo "<strong style='color:red'>ERRO:</strong> " . $e->getMessage();
    }
}
?>