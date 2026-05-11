<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando textos livres de rotas...\n";
    
    $stmt = $pdo->query("SELECT id, title, start_location, microregion FROM routes");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($routes as $r) {
        $clean_loc = $r['start_location'];
        $clean_title = $r['title'];

        // Variações de "fiscalização"
        $clean_loc = preg_replace('/fiscalia.{1,5}o/u', 'fiscalização', $clean_loc);
        $clean_loc = preg_replace('/Fiscaliza.{1,5}o/u', 'Fiscalização', $clean_loc);
        
        $clean_title = preg_replace('/fiscalia.{1,5}o/u', 'fiscalização', $clean_title);
        $clean_title = preg_replace('/Fiscaliza.{1,5}o/u', 'Fiscalização', $clean_title);

        // Variações de Brasília, Ceilândia, São
        $clean_loc = preg_replace('/Bras.{1,5}lia/u', 'Brasília', $clean_loc);
        $clean_loc = preg_replace('/Ceil.{1,5}ndia/u', 'Ceilândia', $clean_loc);
        $clean_loc = preg_replace('/S.{1,3}o Sebasti.{1,3}o/u', 'São Sebastião', $clean_loc);
        $clean_loc = preg_replace('/S.{1,3}o/u', 'São', $clean_loc);

        if ($clean_loc !== $r['start_location'] || $clean_title !== $r['title']) {
            $up = $pdo->prepare("UPDATE routes SET start_location = ?, title = ? WHERE id = ?");
            $up->execute([$clean_loc, $clean_title, $r['id']]);
            echo "ID {$r['id']} atualizado: {$clean_title} | {$clean_loc}\n";
        }
    }
    
    echo "\nConcluído!";

} catch(PDOException $e) {
    echo $e->getMessage();
}
?>
