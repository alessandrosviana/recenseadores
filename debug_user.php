<?php
require_once 'config/database.php';
$name = 'ALESSANDRO S VIANA';
echo "Searching for user: $name\n";
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE name LIKE ?");
$stmt->execute(['%' . $name . '%']);
while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "USER ID: " . $u['id'] . " NAME: " . $u['name'] . "\n";
    $r_stmt = $pdo->prepare("SELECT id, title, status, wizard_step, microregion FROM routes WHERE user_id = ?");
    $r_stmt->execute([$u['id']]);
    while ($r = $r_stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - ROUTE: " . $r['title'] . " STATUS: [" . $r['status'] . "] STEP: [" . $r['wizard_step'] . "] MICRO: [" . $r['microregion'] . "]\n";
    }
}
