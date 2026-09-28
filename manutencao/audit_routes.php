<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT microregion, wizard_step, count(*) as c FROM routes WHERE status='completed' GROUP BY microregion, wizard_step");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "VAL: [" . $row['microregion'] . "] STEP: " . $row['wizard_step'] . " COUNT: " . $row['c'] . "\n";
}
