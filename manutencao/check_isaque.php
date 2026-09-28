<?php
require_once 'config/database.php';

$stmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM routes r JOIN users u ON r.user_id = u.id WHERE u.name LIKE '%Isaque%'");
$stmt->execute();
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Routes for Isaque:\n";
foreach ($routes as $r) {
    echo "ID: {$r['id']}, Title: {$r['title']}, Status: {$r['status']}, User: {$r['user_name']}\n";
}
?>
