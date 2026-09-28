<?php
require_once 'config/database.php';

try {
    echo "Updating schema to version 7 (Allow NULL CPF for admins)...<br>";

    // 1. Modificar coluna cpf para permitir NULL
    $sqlAlter = "ALTER TABLE users MODIFY cpf VARCHAR(14) NULL";
    $pdo->exec($sqlAlter);
    echo "<strong style='color:green'>SUCCESS:</strong> Column 'cpf' modified to allow NULL!<br>";

    // 2. Atualizar CPFs vazios para NULL para evitar conflitos de UNIQUE
    $sqlUpdate = "UPDATE users SET cpf = NULL WHERE cpf = ''";
    $count = $pdo->exec($sqlUpdate);
    echo "<strong style='color:green'>SUCCESS:</strong> Updated $count empty CPF(s) to NULL!<br>";

    echo "Schema updated successfully!";

} catch (PDOException $e) {
    echo "<strong style='color:red'>ERROR:</strong> " . $e->getMessage();
}
?>
