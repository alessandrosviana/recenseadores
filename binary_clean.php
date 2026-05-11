<?php
$host = 'localhost';
$db_name = 'sistema_recenseadores';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    echo "--- LIMPANDO CARACTERES BINÁRIOS (BRASÍLIA, CERTIDÃO, FISCALIZAÇÃO) ---\n";
    
    $stmt = $pdo->query("SELECT id, title, start_location, microregion FROM routes");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($routes as $r) {
        // Padrão genérico para Brasília com caracteres corrompidos
        $clean_loc = preg_replace('/Bras.{1,5}lia/u', 'Brasília', $r['start_location']);
        $clean_title = preg_replace('/Fiscaliza.{1,10}o/u', 'Fiscalização', $r['title']);
        $clean_title = preg_replace('/Den.{1,5}ncia/u', 'Denúncia', $clean_title);
        
        if ($clean_loc !== $r['start_location'] || $clean_title !== $r['title']) {
            $up = $pdo->prepare("UPDATE routes SET start_location = ?, title = ? WHERE id = ?");
            $up->execute([$clean_loc, $clean_title, $r['id']]);
            echo "ID {$r['id']} (Rota) corrigido.\n";
        }
    }

    $stmt = $pdo->query("SELECT id, name, address, city FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $u) {
        $clean_city = preg_replace('/Bras.{1,5}lia/u', 'Brasília', $u['city']);
        $clean_addr = preg_replace('/Bras.{1,5}lia/u', 'Brasília', $u['address']);
        
        if ($clean_city !== $u['city'] || $clean_addr !== $u['address']) {
            $up = $pdo->prepare("UPDATE users SET city = ?, address = ? WHERE id = ?");
            $up->execute([$clean_city, $clean_addr, $u['id']]);
            echo "ID {$u['id']} (Usuário) corrigido.\n";
        }
    }

    echo "\nLimpeza concluída.";

} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
