<?php
session_start();

// Store user info before clearing session
$userId = $_SESSION['user_id'] ?? null;

// --- Log Logout (Enhanced Audit Trail) ---
if ($userId) {
    try {
        require_once __DIR__ . '/conn.php';
        require_once __DIR__ . '/AuditLogger.php';
        AuditLogger::init($conn, $userId);
        AuditLogger::log(AuditLogger::ACTION_LOGOUT, 'users', $userId, ['method' => 'standard']);
    } catch (Exception $e) { /* Ignore log error to allow logout */ }
}

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Clear the remember_me cookie
setcookie('remember_me', '', time() - 3600, '/');

// Redirect to login page
header("Location: index.php");
exit;
?>