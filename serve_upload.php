<?php
/**
 * serve_upload.php — Intercepta todo acesso a uploads/ com autenticacao (VULN-01)
 * Admin pode ver qualquer arquivo. Recenseador so pode ver os proprios documentos
 * e anexos de rotas atribuidas a ele.
 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

header('X-Frame-Options: SAMEORIGIN');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Acesso negado.');
}

$requested = $_GET['path'] ?? '';
$requested = ltrim($requested, '/');

$realPath = realpath(__DIR__ . '/uploads/' . $requested);
$uploadsRoot = realpath(__DIR__ . '/uploads');

if ($realPath === false || $uploadsRoot === false || strpos($realPath, $uploadsRoot) !== 0 || !file_exists($realPath)) {
    http_response_code(404);
    die('Arquivo nao encontrado.');
}

if (!is_file($realPath)) {
    http_response_code(404);
    die('Arquivo nao encontrado.');
}

$isAdmin = ($_SESSION['role'] === 'admin');
$userId = $_SESSION['user_id'];

if (!$isAdmin) {
    $basename = basename($realPath);
    $allowed = false;

    $docStmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE file_path LIKE ? AND user_id = ?");
    $docStmt->execute(['%' . $basename, $userId]);
    if ((int)$docStmt->fetchColumn() > 0) {
        $allowed = true;
    }

    if (!$allowed) {
        $routeStmt = $pdo->prepare("SELECT COUNT(*) FROM routes WHERE user_id = ? AND (
            ref_image LIKE ? OR ref_pdf_1 LIKE ? OR ref_pdf_2 LIKE ? OR
            admin_file_1 LIKE ? OR admin_file_2 LIKE ? OR admin_file_3 LIKE ? OR
            report_file_1 LIKE ? OR report_file_2 LIKE ? OR report_file_3 LIKE ?)");
        $like = '%' . $basename;
        $routeStmt->execute([$userId, $like, $like, $like, $like, $like, $like, $like, $like, $like]);
        if ((int)$routeStmt->fetchColumn() > 0) {
            $allowed = true;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        die('Acesso negado.');
    }
}

$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
$mime = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . addslashes(basename($realPath)) . '"');
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

readfile($realPath);
exit();
?>
