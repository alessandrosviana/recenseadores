<?php
header('Content-Type: text/plain; charset=utf-8');
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando Limpeza Cirúrgica de Acentos...\n";

    $replacements = [
        // Padrões detectados na varredura
        'Bras?Â¡lia' => 'Brasília',
        'respons?í­vel' => 'responsável',
        't?Â®cnico' => 'técnico',
        'Bot?ónico' => 'Botânico',
        'Fiscaliza?º?úo' => 'Fiscalização',
        'Identifica?º?úo' => 'Identificação',
        'Mans?í es' => 'Mansões',
        'Den??ncia' => 'Denúncia',
        'Fus?úo' => 'Fusão',
        'Habita?º?í es' => 'Habitações',
        'Ceil?óndia' => 'Ceilândia',
        'Parano?í­' => 'Paranoá',
        'S?úo' => 'São',
        'Sebasti?úo' => 'Sebastião',
        'identifica?º?í es' => 'identificações',
        'Andamento' => 'Andamento', // Apenas para garantir
        'Habita?º?í£o' => 'Habitação',
        '?Â¡' => 'á',
        '?í­' => 'á', // Em alguns casos respons?í-vel
        '?Â®' => 'é',
        '?ó' => 'â', // Bot?ónico
        '?º?ú' => 'ção',
        '??' => 'ú' // Den??ncia
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

    echo "\nLimpeza Cirúrgica Concluída!\n";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
