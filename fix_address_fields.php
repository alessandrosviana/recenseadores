<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "Limpando campos de endereço secundários...\n";
    
    $stmt = $pdo->query("SELECT id, address_complement, address_city, address_neighborhood FROM routes");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($routes as $r) {
        $clean_comp = preg_replace('/fiscalia.{1,5}o/u', 'fiscalização', $r['address_complement']);
        $clean_comp = preg_replace('/Fiscaliza.{1,5}o/u', 'Fiscalização', $clean_comp);
        
        $clean_city = preg_replace('/Bras.{1,5}lia/u', 'Brasília', $r['address_city']);
        $clean_neigh = preg_replace('/Ceil.{1,5}ndia/u', 'Ceilândia', $r['address_neighborhood']);
        $clean_neigh = preg_replace('/S.{1,3}o Sebasti.{1,3}o/u', 'São Sebastião', $clean_neigh);

        if ($clean_comp !== $r['address_complement'] || $clean_city !== $r['address_city'] || $clean_neigh !== $r['address_neighborhood']) {
            $up = $pdo->prepare("UPDATE routes SET address_complement = ?, address_city = ?, address_neighborhood = ? WHERE id = ?");
            $up->execute([$clean_comp, $clean_city, $clean_neigh, $r['id']]);
            echo "ID {$r['id']} atualizado: {$clean_comp} | {$clean_city}\n";
        }
    }
    
    echo "\nConcluído!";

} catch(PDOException $e) {
    echo $e->getMessage();
}
?>
