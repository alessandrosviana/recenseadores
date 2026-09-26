<?php
/**
 * migrations/add_archive_columns.php — Adiciona colunas is_archived e archive_reason
 * Extraido do auto-migration que estava em admin/dashboard.php (REQ-012)
 * Executar uma unica vez: php migrations/add_archive_columns.php
 */
require_once __DIR__ . '/../config/database.php';

$checkColumn = $pdo->query("SHOW COLUMNS FROM routes LIKE 'is_archived'")->fetch();
if (!$checkColumn) {
    $pdo->exec("ALTER TABLE routes ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0");
    echo "Coluna is_archived adicionada.\n";
} else {
    echo "Coluna is_archived ja existe.\n";
}

$checkReasonColumn = $pdo->query("SHOW COLUMNS FROM routes LIKE 'archive_reason'")->fetch();
if (!$checkReasonColumn) {
    $pdo->exec("ALTER TABLE routes ADD COLUMN archive_reason TEXT NULL DEFAULT NULL");
    echo "Coluna archive_reason adicionada.\n";
} else {
    echo "Coluna archive_reason ja existe.\n";
}

echo "Migration completa.\n";
