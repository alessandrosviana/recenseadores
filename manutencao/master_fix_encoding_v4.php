<?php
header('Content-Type: text/plain; charset=utf-8');
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando Limpeza Exaustiva de Acentos (V4)...\n";

    $replacements = [
        // Padrões detectados na nova imagem do recenseador
        'Bras?í­lia' => 'Brasília',
        'Certid?íºo' => 'Certidão',
        'fiscalia?º?íºo' => 'fiscalização',
        'Fiscaliza?º?íºo' => 'Fiscalização',
        'identifica?º?íºo' => 'identificação',
        'Bras?í­lia' => 'Brasília',
        'Bras?Â¡lia' => 'Brasília',
        
        // Variações de interrogação com í
        'íºo' => 'ão',
        'í­' => 'í',
        '?í­' => 'í',
        '?íºo' => 'ão',
        '?º?íº' => 'ção',
        '?º?í' => 'ção',
        
        // Padrões UTF-8 comuns
        'Ã¡' => 'á', 'Ã©' => 'é', 'Ã³' => 'ó', 'Ãº' => 'ú', 'Ã£' => 'ã', 'Ãµ' => 'õ',
        'Ã§' => 'ç', 'Ã ' => 'à', 'Â°' => '°', 'Âº' => 'º', 'Âª' => 'ª'
    ];

    $tables = ['routes', 'users', 'notifications', 'settings'];
    
    foreach ($tables as $table) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() == 0) continue;

        echo "Limpando tabela: $table...\n";
        
        $stmt = $pdo->query("SHOW COLUMNS FROM $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            $name = $col['Field'];
            $type = strtolower($col['Type']);
            
            if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                foreach ($replacements as $search => $replace) {
                    $stmtUpdate = $pdo->prepare("UPDATE $table SET $name = REPLACE($name, ?, ?) WHERE $name LIKE ?");
                    $stmtUpdate->execute([$search, $replace, "%$search%"]);
                    if ($stmtUpdate->rowCount() > 0) {
                        echo "  - Coluna [$name]: '$search' -> '$replace' (" . $stmtUpdate->rowCount() . " linhas)\n";
                    }
                }
            }
        }
    }

    echo "\nLimpeza V4 Concluída!\n";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
