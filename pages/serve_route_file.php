<?php
/**
 * pages/serve_route_file.php — Serve anexos de rotas com autenticacao (VULN-01)
 * Servidor admin (ref_image, ref_pdf_1, ref_pdf_2) e relatorios (report_file_1..3)
 */
require_once '../config/session.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acesso negado.');
}

$route_id = isset($_GET['route_id']) ? (int) $_GET['route_id'] : 0;
$field = $_GET['field'] ?? '';

$allowed_fields = ['ref_image', 'ref_pdf_1', 'ref_pdf_2',
                   'admin_file_1', 'admin_file_2', 'admin_file_3',
                   'report_file_1', 'report_file_2', 'report_file_3'];

if ($route_id <= 0 || !in_array($field, $allowed_fields)) {
    http_response_code(400);
    die('Parametros invalidos.');
}

$stmt = $pdo->prepare("SELECT id, user_id, $field AS file_path FROM routes WHERE id = ?");
$stmt->execute([$route_id]);
$route = $stmt->fetch();

if (!$route || empty($route['file_path'])) {
    http_response_code(404);
    die('Arquivo nao encontrado.');
}

if ($_SESSION['role'] !== 'admin' && $route['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('Acesso negado.');
}

$filepath = $route['file_path'];

$baseDir = realpath(__DIR__ . '/../uploads');
$resolved = realpath(__DIR__ . '/../pages/admin/' . $filepath);
if ($resolved === false) {
    $resolved = realpath(__DIR__ . '/admin/' . $filepath);
}
if ($resolved === false) {
    $cleanPath = preg_replace('/\.\.\//', '', $filepath);
    $resolved = realpath($baseDir . '/' . $cleanPath);
}

if ($resolved === false || !file_exists($resolved)) {
    http_response_code(404);
    die('Arquivo nao encontrado no servidor.');
}

if ($baseDir === false || strpos($resolved, $baseDir) !== 0) {
    http_response_code(403);
    die('Acesso negado.');
}

$ext = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . addslashes(basename($resolved)) . '"');
header('Content-Length: ' . filesize($resolved));
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

readfile($resolved);
exit();
?>
