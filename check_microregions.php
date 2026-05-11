<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT DISTINCT microregion FROM routes WHERE status = 'completed'");
$regs = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Unique microregions in completed routes:\n";
print_r($regs);

$macro_mapping = [
    'Macrorregião 1' => ['Sobradinho (RA V)', 'Sobradinho II (RA XXVI)', 'Planaltina (RA VI)', 'Fercal (RA XXXI)', 'Arapoanga (RA XXXIV)'],
    'Macrorregião 2' => ['Lago Norte (RA XVIII)', 'Varjão (RA XXIII)', 'Paranoá (RA VII)', 'Itapoã (RA XXVIII)'],
    'Macrorregião 3' => ['Lago Sul (RA XVI)', 'Jardim Botânico (RA XXVII)', 'São Sebastião (RA XIV)'],
    'Macrorregião 4' => ['Plano Piloto (RA I)', 'Cruzeiro (RA XI)', 'Sudoeste/Octogonal (RA XXII)', 'SIA (RA XXIX)', 'SCIA (RA XXV)', 'Noroeste (RA XXXVI)'],
    'Macrorregião 5' => ['Gama (RA II)', 'Santa Maria (RA XIII)', 'Água Quente (RA XXXV)'],
    'Macrorregião 6' => ['Riacho Fundo (RA XVII)', 'Riacho Fundo II (RA XXI)', 'Park Way (RA XXIV)', 'Candangolândia (RA XIX)', 'Núcleo Bandeirante (RA VIII)', 'Recanto das Emas (RA XV)'],
    'Macrorregião 7' => ['Ceilândia (RA IX)', 'Sol Nascente/Pôr do Sol (RA XXXII)', 'Taguatinga (RA III)', 'Samambaia (RA XII)', 'Brazlândia (RA IV)'],
    'Macrorregião 8' => ['Guará (RA X)', 'Águas Claras (RA XX)', 'Vicente Pires (RA XXX)', 'Arniqueiras (RA XXXIII)']
];

foreach ($regs as $r) {
    $found = false;
    foreach ($macro_mapping as $macro => $ras) {
        if (in_array(trim($r), $ras)) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "NOT FOUND IN MAPPING: '$r'\n";
    }
}
