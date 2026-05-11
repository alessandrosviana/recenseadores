<?php
// Set session timeout to 30 minutes (1800 seconds)
$session_timeout = 1800;

// Set a custom isolated directory for sessions so other XAMPP scripts don't randomly delete them
$session_dir = __DIR__ . '/../sessions';
if (!is_dir($session_dir)) {
    @mkdir($session_dir, 0777, true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_save_path($session_dir);
    // Ensure PHP garbage collection doesn't destroy sessions prematurely
    ini_set('session.gc_maxlifetime', $session_timeout);
    // We do NOT use session_set_cookie_params($session_timeout) here because it forces an absolute 
    // expiration based on the first login instead of inactivity. Leaving it default makes it "Session" cookie.
    session_start();
}

require_once __DIR__ . '/database.php';

// Check for session timeout (30 minutes of inactivity)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
    // Session expired
    session_unset();
    session_destroy();

    // Redirect to login
    $loginData = BASE_URL . 'pages/login.php?timeout=true';
    if (!headers_sent()) {
        header("Location: $loginData");
        exit();
    } else {
        echo "<script>window.location.href='$loginData';</script>";
        exit();
    }
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
?>