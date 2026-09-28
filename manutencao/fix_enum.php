<?php
require_once 'config/database.php';

try {
    echo "Tentando atualizar a tabela routes...<br>";

    // Alterar a coluna status para incluir 'cancelled' e 'delayed'
    $sql = "ALTER TABLE routes MODIFY COLUMN status ENUM('assigned', 'in_progress', 'completed', 'cancelled', 'delayed') DEFAULT 'assigned'";
    $pdo->exec($sql);

    echo "<strong style='color:green'>SUCESSO:</strong> Tabela 'routes' atualizada! Agora aceita status 'cancelled' e 'delayed'.";
} catch (PDOException $e) {
    echo "<strong style='color:red'>ERRO:</strong> " . $e->getMessage();
}
?>