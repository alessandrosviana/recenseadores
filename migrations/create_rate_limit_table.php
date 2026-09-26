<?php
/**
 * migrations/create_rate_limit_table.php — Cria tabela de rate limiting
 * Executar uma unica vez: php migrations/create_rate_limit_table.php
 * Bloqueado via .htaccess (nao acessivel via web)
 */
require_once __DIR__ . '/../config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS rate_limit_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    limit_key VARCHAR(255) NOT NULL,
    attempt_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_time (limit_key, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$pdo->exec($sql);
echo "Tabela rate_limit_attempts criada com sucesso.\n";
