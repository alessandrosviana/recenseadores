<?php
header('Content-Type: text/plain; charset=utf-8');
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando Limpeza Refinada de Acentos...\n";

    // ORDEM IMPORTANTE: Longos primeiro, curtos depois
    $replacements = [
        // Padrões com ponto de interrogação
        '?Ã³' => 'ó', '?Ã£' => 'ã', '?Ãº' => 'ú', '?Ã©' => 'é', '?Ã¡' => 'á',
        '?Ã§' => 'ç', '?Âº' => 'º', '?Âª' => 'ª', '?Â°' => '°',
        'í³' => 'ó', 'í£' => 'ã', 'íº' => 'ú', 'í©' => 'é', 'í¡' => 'á',
        
        // Padrões UTF-8 quebrados comuns
        'Ã¡' => 'á', 'Ã©' => 'é', 'Ã³' => 'ó', 'Ãº' => 'ú', 'Ã£' => 'ã', 'Ãµ' => 'õ',
        'Ã¢' => 'â', 'Ãª' => 'ê', 'Ã´' => 'ô', 'Ã§' => 'ç', 'Ã ' => 'à',
        'Â°' => '°', 'Âº' => 'º', 'Âª' => 'ª',
        
        // Casos residuais do erro anterior (Ã -> í)
        'í³' => 'ó', 'í£' => 'ã', 'íº' => 'ú', 'í©' => 'é', 'í¡' => 'á', 'í§' => 'ç',
        
        // Casos isolados (DEVE SER POR ÚLTIMO)
        'Ã' => 'í' 
    ];

    $tables = ['routes', 'users', 'notifications', 'settings'];
    
    foreach ($tables as $table) {
        // Verificar se a tabela existe
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

    echo "\nLimpeza Refinada Concluída!\n";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
