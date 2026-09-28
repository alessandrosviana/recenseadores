<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';

echo "Testing user insertion...\n";

try {
    $sql = "INSERT INTO users (
        name, email, password, cpf, phone, rg, gender, birth_date, 
        address, city, state, cep, education_level, course_detail, 
        additional_info, status
    ) VALUES (
        'Test User', 'test_" . time() . "@example.com', 'password', '000.000.000-" . rand(10, 99) . "', 
        '123456789', 'RG123', 'Outro', '2000-01-01', 
        'Test Address', 'Test City', 'SC', '12345678', 
        'Tecnico', 'Test Course', 'Info', 'pending'
    )";

    $pdo->exec($sql);
    echo "USER INSERTED SUCCESSFULLY! ID: " . $pdo->lastInsertId();

    // Check if table works
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 1");
    $user = $stmt->fetch();
    echo "\nInserted User Name: " . $user['name'] . "\n";

} catch (PDOException $e) {
    echo "INSERT FAILED: " . $e->getMessage();
}
?>