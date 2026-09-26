<?php
$session_timeout = 1800;

$session_dir = __DIR__ . '/../sessions';
if (!is_dir($session_dir)) {
    @mkdir($session_dir, 0700, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_save_path($session_dir);
    ini_set('session.gc_maxlifetime', $session_timeout);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header("Content-Security-Policy: default-src 'self'; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.quilljs.com https://cdn.jsdelivr.net; "
        . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.quilljs.com; "
        . "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; "
        . "img-src 'self' data:; "
        . "connect-src 'self' https://viacep.com.br; "
        . "frame-src 'self'");
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/security.php';

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
    session_unset();
    session_destroy();

    $loginData = BASE_URL . 'pages/login.php?timeout=true';
    if (!headers_sent()) {
        header("Location: $loginData");
        exit();
    } else {
        echo "<script>window.location.href='$loginData';</script>";
        exit();
    }
}

$_SESSION['LAST_ACTIVITY'] = time();
?>
