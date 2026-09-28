<?php
require_once 'c:\xampp\htdocs\recenseadores\config\database.php';

$mapping = [
    'Macrorregião 1' => ['Sobradinho', 'Sobradinho II', 'Planaltina', 'Fercal', 'Arapoanga'],
    'Macrorregião 2' => ['Lago Norte', 'Varjão', 'Paranoá', 'Itapoã'],
    'Macrorregião 3' => ['Lago Sul', 'Jardim Botânico', 'São Sebastião'],
    'Macrorregião 4' => ['Plano Piloto', 'Cruzeiro', 'Sudoeste', 'Octogonal', 'SIA', 'SCIA', 'Noroeste', 'Estrutural'],
    'Macrorregião 5' => ['Gama', 'Santa Maria', 'Água Quente'],
    'Macrorregião 6' => ['Riacho Fundo', 'Riacho Fundo II', 'Park Way', 'Candangolândia', 'Núcleo Bandeirante', 'Bandeirante', 'Recanto das Emas'],
    'Macrorregião 7' => ['Ceilândia', 'Sol Nascente', 'Taguatinga', 'Samambaia', 'Brazlândia'],
    'Macrorregião 8' => ['Guará', 'Águas Claras', 'Vicente Pires', 'Arniqueiras']
];

try {
    $users = $pdo->query("SELECT id, microregion FROM users")->fetchAll();
    $updated = 0;

    foreach ($users as $u) {
        $current = $u['microregion'];
        $newMacro = '';

        // Se já começa com Macrorregião, apenas limpa o excesso (se houver parênteses)
        if (strpos($current, 'Macrorregião') === 0) {
            $parts = explode(' (', $current);
            $newMacro = trim($parts[0]);
        } else {
            // Tenta mapear a cidade para a Macro
            foreach ($mapping as $macro => $cities) {
                foreach ($cities as $city) {
                    if (strpos($current, $city) !== false) {
                        $newMacro = $macro;
                        break 2;
                    }
                }
            }
        }

        if ($newMacro && $newMacro !== $current) {
            $stmt = $pdo->prepare("UPDATE users SET microregion = ? WHERE id = ?");
            $stmt->execute([$newMacro, $u['id']]);
            $updated++;
        }
    }

    echo "Padronização concluída! $updated registros foram atualizados para o formato 'Macrorregião X'.\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
