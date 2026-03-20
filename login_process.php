<?php
ob_start(); // Mulai buffering output segera untuk menangkap error/warning
require_once __DIR__ . '/../src/rate_limiter.php';

// Fungsi helper untuk mengirim respons JSON bersih
function sendJson($data) {
    ob_clean(); // Bersihkan buffer sebelum kirim output
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

try {
    require_once __DIR__ . '/../src/conn.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJson(['success' => false, 'message' => 'Invalid request method']);
    }

    // Rate Limit: 5 attempts per 5 minutes (300 seconds)
    if (!checkRateLimit('login', 5, 300)) {
        sendJson(['success' => false, 'message' => 'Too many login attempts. Please try again in 5 minutes.']);
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');

    if (empty($identifier) || empty($password) || empty($captcha)) {
        sendJson(['success' => false, 'message' => 'All fields are required']);
    }

    // Verify Captcha
    if (!isset($_SESSION['captcha']) || strtolower($captcha) !== strtolower($_SESSION['captcha'])) {
        sendJson(['success' => false, 'message' => 'Invalid Captcha Code']);
    }

    $stmt = $conn->prepare("SELECT id_pendaftar, name, email, password, role, is_verified, is_banned FROM users WHERE email = ? OR name = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        sendJson(['success' => false, 'message' => 'Invalid email or password']);
    }

    // --- Login Langsung (Tanpa 2FA) ---
    
    // 1. Cek Status Verifikasi
    if ($user['is_verified'] == 0) {
        sendJson(['success' => false, 'message' => 'Akun belum diverifikasi. Silakan cek email Anda untuk verifikasi.']);
    }

    // 1.5 Cek Status Ban
    if (isset($user['is_banned']) && $user['is_banned'] == 1) {
        sendJson(['success' => false, 'message' => 'Akun Anda telah diblokir sementara. Silakan hubungi admin.']);
    }

    // 2. Set Session
    $_SESSION['user_id'] = $user['id_pendaftar'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_email'] = $user['email'];

    // --- Log Activity ---
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS user_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $log_stmt = $conn->prepare("INSERT INTO user_activity_logs (user_id, action, ip_address, user_agent) VALUES (?, 'login', ?, ?)");
        $log_stmt->execute([$user['id_pendaftar'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
    } catch (Exception $e) { /* Ignore log error to allow login */ }
    // --------------------

    // 3. Handle Remember Me
    if (isset($_POST['remember_me'])) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (86400 * 30));
        $token_hash = hash('sha256', $token);
        $stmt = $conn->prepare("UPDATE users SET remember_token = ?, remember_expires = ? WHERE id_pendaftar = ?");
        $stmt->execute([$token_hash, $expires, $user['id_pendaftar']]);
        setcookie('remember_me', base64_encode($user['id_pendaftar'] . ':' . $token), time() + (86400 * 30), '/', '', false, true);
    }

    // 4. Redirect sesuai role
    $redirect = ($user['role'] === 'admin') ? 'admin_dashboard.php' : 'dashboard.php';
    sendJson(['success' => true, 'message' => 'Login successful', 'redirect' => $redirect]);

} catch (\Exception $e) {
    sendJson(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}
?>