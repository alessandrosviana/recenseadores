<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "--- LIMPANDO TABELA DE DOCUMENTOS ---\n";
    
    $replacements = [
        'Certid?íºo' => 'Certidão',
        'CertidÃ£o' => 'Certidão',
        'Ã£o' => 'ão',
        '?íºo' => 'ão'
    ];
    
    foreach ($replacements as $search => $replace) {
        $up = $pdo->prepare("UPDATE user_documents SET document_type = REPLACE(document_type, ?, ?) WHERE document_type LIKE ?");
        $up->execute([$search, $replace, "%$search%"]);
        if ($up->rowCount() > 0) {
            echo "Corrigido: $search -> $replace (" . $up->rowCount() . " registros)\n";
        }
    }

    echo "\nLimpeza de documentos concluída.";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
