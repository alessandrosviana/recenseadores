<?php
/**
 * pages/serve_document.php — Serve documentos com autenticacao (VULN-01)
 * Admin pode ver qualquer documento. Recenseador so pode ver os proprios.
 */
require_once '../config/session.php';
require_once '../config/database.php';

header('X-Frame-Options: SAMEORIGIN');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acesso negado.');
}

$doc_id = isset($_GET['doc_id']) ? (int) $_GET['doc_id'] : 0;
if ($doc_id <= 0) {
    http_response_code(400);
    die('Documento nao especificado.');
}

$stmt = $pdo->prepare("SELECT id, user_id, file_path, document_type, original_name FROM documents WHERE id = ?");
$stmt->execute([$doc_id]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    die('Documento nao encontrado.');
}

if ($_SESSION['role'] !== 'admin' && $doc['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    die('Acesso negado.');
}

$filepath = $doc['file_path'];

$realPath = realpath(__DIR__ . '/' . $filepath);
if ($realPath === false) {
    $realPath = realpath(dirname(__DIR__) . '/' . preg_replace('/^\.\.\//', '', $filepath));
}
if ($realPath === false || !file_exists($realPath)) {
    http_response_code(404);
    die('Arquivo nao encontrado no servidor.');
}

$uploadsRoot = realpath(__DIR__ . '/../uploads');
if ($uploadsRoot === false || strpos($realPath, $uploadsRoot) !== 0) {
    http_response_code(403);
    die('Acesso negado.');
}

$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

$filename = $doc['original_name'] ?? basename($realPath);

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . addslashes($filename) . '"');
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

readfile($realPath);
exit();
?>
