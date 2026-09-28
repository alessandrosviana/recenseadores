<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando Documentos via Regex...\n";
    
    $stmt = $pdo->query("SELECT id, document_type FROM documents");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rows as $row) {
        $clean = preg_replace('/Certid.{1,5}o/u', 'Certidão', $row['document_type']);
        $clean = preg_replace('/Escolaridade.{0,5}/u', 'Escolaridade', $clean);
        
        if ($clean !== $row['document_type']) {
            $update = $pdo->prepare("UPDATE documents SET document_type = ? WHERE id = ?");
            $update->execute([$clean, $row['id']]);
            echo "ID {$row['id']} corrigido para: $clean\n";
        }
    }

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
