<?php
require_once 'config/database.php';

try {
    echo "Verificando tabela routes...<br>";

    // Adicionar colunas start_time se não existir
    try {
        $pdo->query("SELECT start_time FROM routes LIMIT 1");
        echo "Coluna 'start_time' já existe.<br>";
    } catch (PDOException $e) {
        $sql = "ALTER TABLE routes ADD COLUMN start_time TIMESTAMP NULL AFTER created_at";
        $pdo->exec($sql);
        echo "<strong style='color:green'>SUCESSO:</strong> Coluna 'start_time' adicionada!<br>";
    }

    // Check if status enum includes 'cancelled' and 'delayed'
    // Actually best to just brute force alter just in case
    $sql = "ALTER TABLE routes MODIFY COLUMN status ENUM('assigned', 'in_progress', 'completed', 'cancelled', 'delayed') DEFAULT 'assigned'";
    $pdo->exec($sql);
    echo "Enum de status verificado/atualizado.<br>";

} catch (PDOException $e) {
    echo "<strong style='color:red'>ERRO GERAL:</strong> " . $e->getMessage();
}
?>