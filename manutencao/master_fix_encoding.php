<?php
header('Content-Type: text/plain; charset=utf-8');
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando Limpeza Mestra de Acentos...\n";

    $replacements = [
        'Ã¡' => 'á', 'Ã ' => 'à', 'Ã¢' => 'â', 'Ã£' => 'ã',
        'Ã©' => 'é', 'Ã¨' => 'è', 'Ãª' => 'ê',
        'Ã' => 'í',  'Ã¬' => 'ì', 'Ã®' => 'î',
        'Ã³' => 'ó', 'Ã²' => 'ò', 'Ã´' => 'ô', 'Ãµ' => 'õ',
        'Ãº' => 'ú', 'Ã¹' => 'ù', 'Ã»' => 'û',
        'Ã§' => 'ç', 'Ã‡' => 'Ç',
        'Â°' => '°', 'Âº' => 'º', 'Âª' => 'ª',
        '?Ã³' => 'ó', '?Ã£' => 'ã', '?Ãº' => 'ú', '?Ã©' => 'é',
        '?Âº' => 'º', '?Âª' => 'ª', '?Ã§' => 'ç',
        '?Â°' => '°',
        'S?Ãºo' => 'São', 'Ceil?Ã³ndia' => 'Ceilândia',
        'identifica?Âº?Ãºo' => 'identificação'
    ];

    $tables = ['routes', 'users', 'logs', 'notifications', 'settings'];
    
    foreach ($tables as $table) {
        echo "Limpando tabela: $table...\n";
        
        // Pegar todas as colunas de texto
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
                        echo "  - Coluna [$name]: Corrigido '$search' -> '$replace' (" . $stmtUpdate->rowCount() . " linhas)\n";
                    }
                }
            }
        }
    }

    echo "\nConcluído! Todos os acentos conhecidos foram normalizados.\n";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
