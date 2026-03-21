<?php
session_start();
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/rate_limiter.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$code = trim($_POST['code'] ?? '');

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Code is required']);
    exit;
}

// Konfigurasi Rate Limiter (Denda Waktu)
$max_attempts = 5;   // Batas percobaan (5 kali)
$penalty_time = 300; // Durasi denda dalam detik (Ubah ke 60 untuk 1 menit, atau 300 untuk 5 menit)

if (!checkRateLimit('verify', $max_attempts, $penalty_time)) {
    $minutes = ceil($penalty_time / 60);
    echo json_encode(['success' => false, 'message' => "Terlalu banyak percobaan. Silakan tunggu $minutes menit."]);
    exit;
}

if (!isset($_SESSION['login_code']) || !isset($_SESSION['login_email']) || !isset($_SESSION['login_user'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

if ($_SESSION['login_code'] != $code) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    exit;
}

$user = $_SESSION['login_user'];

// Handle Remember Me (Moved from login_process.php)
if (isset($_SESSION['login_remember_me']) && $_SESSION['login_remember_me'] === true) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days
    $token_hash = hash('sha256', $token);

    $stmt = $conn->prepare("UPDATE users SET remember_token = ?, remember_expires = ? WHERE id_pendaftar = ?");
    $stmt->execute([$token_hash, $expires, $user['id_pendaftar']]);

    setcookie('remember_me', base64_encode($user['id_pendaftar'] . ':' . $token), time() + (86400 * 30), '/', '', false, true);
}

// Clear session
unset($_SESSION['login_code']);
unset($_SESSION['login_email']);
unset($_SESSION['login_user']);
unset($_SESSION['login_remember_me']);

$_SESSION['user_id'] = $user['id_pendaftar'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

if ($user['role'] === 'admin') {
    echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => 'admin_dashboard.php']);
} else {
    echo json_encode(['success' => true, 'message' => 'Login successful', 'redirect' => 'dashboard.php']);
}
?>