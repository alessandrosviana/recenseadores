<?php
require_once 'config/database.php';

try {
    // 1. Adicionar novas colunas
    $sql = "ALTER TABLE routes 
            ADD COLUMN demand_type ENUM('padrao', 'especifica', 'mista') DEFAULT 'especifica' AFTER user_id,
            ADD COLUMN area_details TEXT AFTER demand_type,
            ADD COLUMN google_maps_link VARCHAR(512) AFTER area_details,
            ADD COLUMN start_date DATE AFTER google_maps_link,
            ADD COLUMN end_date DATE AFTER start_date,
            ADD COLUMN ref_image VARCHAR(255) AFTER end_date,
            ADD COLUMN ref_pdf_1 VARCHAR(255) AFTER ref_image,
            ADD COLUMN ref_pdf_2 VARCHAR(255) AFTER ref_pdf_1,
            ADD COLUMN acceptance_pdf VARCHAR(255) AFTER ref_pdf_2,
            ADD COLUMN rejection_reason TEXT AFTER status";
    
    $pdo->exec($sql);
    echo "Colunas adicionadas com sucesso!\n";

    // 2. Atualizar o ENUM de status
    // Nota: No MySQL/MariaDB, para alterar um ENUM, usamos MODIFY COLUMN
    $sqlStatus = "ALTER TABLE routes MODIFY COLUMN status ENUM('pending_acceptance', 'accepted', 'rejected', 'in_progress', 'completed') DEFAULT 'pending_acceptance'";
    $pdo->exec($sqlStatus);
    echo "Status atualizado com sucesso!\n";

    // 3. Criar pasta de uploads se não existir
    $uploadDir = 'uploads/routes';
    if (!is_dir($uploadDir)) {
        if (mkdir($uploadDir, 0777, true)) {
            echo "Diretório $uploadDir criado com sucesso!\n";
        } else {
            echo "Erro ao criar diretório $uploadDir\n";
        }
    }

} catch (PDOException $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>
