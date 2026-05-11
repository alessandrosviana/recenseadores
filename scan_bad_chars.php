<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "--- VARREDURA DE CARACTERES ESTRANHOS NO MONITORAMENTO ---\n";
    // Procurar por títulos ou microrregiões que tenham caracteres não-ASCII típicos de erro
    $stmt = $pdo->query("SELECT id, title, microregion, start_location FROM routes");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $r) {
        if (preg_match('/[^\x20-\x7E\xA0-\xFF]/', $r['title'] . $r['microregion'] . $r['start_location'])) {
            echo "ID: {$r['id']}\n";
            echo "  Título: {$r['title']}\n";
            echo "  Micro:  {$r['microregion']}\n";
            echo "  Local:  {$r['start_location']}\n";
            echo "-----------------------------------\n";
        }
    }

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
