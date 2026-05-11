<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando Termos de Documentos...\n";
    
    $replacements = [
        'Insolv├¬ncia' => 'Insolvência',
        'Insolvencia' => 'Insolvência',
        'Recupera├º├úo' => 'Recuperação',
        'Recuperacao' => 'Recuperação',
        'Fal├¬ncia' => 'Falência',
        'Falencia' => 'Falência',
        'Insolvência' => 'Insolvência',
        'Insolvncia' => 'Insolvência'
    ];
    
    foreach ($replacements as $search => $replace) {
        $up = $pdo->prepare("UPDATE documents SET document_type = REPLACE(document_type, ?, ?) WHERE document_type LIKE ?");
        $up->execute([$search, $replace, "%$search%"]);
        if ($up->rowCount() > 0) {
            echo "Corrigido: $search -> $replace\n";
        }
    }

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
