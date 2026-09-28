<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando nomes de documentos restantes...\n";
    
    // Lista de padrões para buscar (wildcard do SQL garante que vai pegar independentemente do caractere corrompido)
    $updates = [
        "UPDATE documents SET document_type = 'Comprovante de Endereço' WHERE document_type LIKE 'Comprovante de Endere%o'",
        "UPDATE documents SET document_type = 'Antecedentes Criminais Estadual 1º Grau' WHERE document_type LIKE 'Antecedentes Criminais Estadual 1% Grau'",
        "UPDATE documents SET document_type = 'Antecedentes Criminais Estadual 2º Grau' WHERE document_type LIKE 'Antecedentes Criminais Estadual 2% Grau'"
    ];
    
    foreach ($updates as $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo "Corrigido (" . $stmt->rowCount() . " registros)!\n";
        }
    }
    
    echo "\nConcluído!";

} catch(PDOException $e) {
    echo $e->getMessage();
}
?>
