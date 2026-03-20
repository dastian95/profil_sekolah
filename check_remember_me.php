<?php
// This script should be included at the very top of authenticated pages (like dashboards)
// It checks for a "remember me" cookie if a session is not already active.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    // Ensure conn.php is included only once
    if (!isset($conn)) {
        require_once __DIR__ . '/conn.php';
    }
    
    $cookie_data = explode(':', base64_decode($_COOKIE['remember_me']), 2);
    
    if (count($cookie_data) === 2) {
        list($user_id, $token) = $cookie_data;

        $stmt = $conn->prepare("SELECT * FROM users WHERE id_pendaftar = ? AND remember_token IS NOT NULL AND remember_expires > NOW()");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && hash_equals($user['remember_token'], hash('sha256', $token))) {
            // Token is valid, log the user in by setting session variables
            $_SESSION['user_id'] = $user['id_pendaftar'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];

            // We don't need to refresh the token here, the existing one is still valid until it expires.
            // Refreshing on every auto-login is more secure but adds DB writes.
        } else {
            // Invalid token, clear the cookie
            setcookie('remember_me', '', time() - 3600, '/');
        }
    } else {
        setcookie('remember_me', '', time() - 3600, '/');
    }
}
?>