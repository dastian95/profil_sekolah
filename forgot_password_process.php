<?php
// Start output buffering to prevent unwanted output
ob_start();

session_start();
header('Content-Type: application/json');

// Load Composer's autoloader and utilities
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/EmailUtil.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if email exists
    $stmt = $conn->prepare("SELECT id_pendaftar, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        // Token expires in 1 hour
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id_pendaftar = ?");
        if (!$stmt->execute([$tokenHash, $user['id_pendaftar']])) {
            throw new Exception('Database error: Could not update reset token.');
        }

        // Generate reset link using current server domain or APP_URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $domain = $_SERVER['HTTP_HOST'] ?? $_ENV['APP_URL'];
        $baseUrl = $protocol . "://" . $domain;
        
        // Get project folder path from REQUEST_URI
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']); // e.g., '/profil_sekolah'
        $resetLink = $baseUrl . $scriptDir . '/reset_password.php?token=' . urlencode($token);

        // Send Email using EmailUtil (handles both SMTP and file-based)
        $emailResult = EmailUtil::sendResetPasswordEmail($email, $user['name'], $resetLink);

        ob_end_clean();
        echo json_encode($emailResult);
        exit;
    }

    ob_end_clean();
    // Always return success to prevent email enumeration
    echo json_encode(['success' => true, 'message' => 'If that email exists, we have sent a reset link.']);
} catch (Exception $e) {
    ob_end_clean();
    error_log("Forgot Password Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} catch (\Throwable $e) {
    ob_end_clean();
    error_log("Forgot Password Critical Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
}
