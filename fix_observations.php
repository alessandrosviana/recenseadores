<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando observações/relatórios do recenseador...\n";
    
    $stmt = $pdo->query("SELECT id, observation FROM routes WHERE observation IS NOT NULL AND observation != ''");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($routes as $r) {
        $clean_obs = $r['observation'];
        
        // Padrões detectados
        $clean_obs = preg_replace('/conclu.{1,5}da/u', 'concluída', $clean_obs);
        $clean_obs = preg_replace('/n.{1,5}o era/u', 'não era', $clean_obs);
        $clean_obs = preg_replace('/respons.{1,5}vel/u', 'responsável', $clean_obs);
        
        // Substituições de caracteres específicos
        $clean_obs = str_replace('|-®', 'é', $clean_obs);
        $clean_obs = str_replace('?®', 'é', $clean_obs);
        $clean_obs = str_replace('|-ú', 'ã', $clean_obs);
        $clean_obs = str_replace('?ú', 'ã', $clean_obs);
        $clean_obs = str_replace('|-í', 'á', $clean_obs);
        $clean_obs = str_replace('?í', 'á', $clean_obs);
        $clean_obs = str_replace('|-i', 'í', $clean_obs);
        
        // Cobre 'não' genérico se sobrou
        $clean_obs = preg_replace('/n.{1,3}o /u', 'não ', $clean_obs);
        $clean_obs = preg_replace('/ N.{1,3}o/u', ' Não', $clean_obs);
        
        if ($clean_obs !== $r['observation']) {
            $up = $pdo->prepare("UPDATE routes SET observation = ? WHERE id = ?");
            $up->execute([$clean_obs, $r['id']]);
            echo "ID {$r['id']} (Relatório) corrigido.\n";
        }
    }
    
    echo "\nConcluído!";

} catch(PDOException $e) {
    echo $e->getMessage();
}
?>
