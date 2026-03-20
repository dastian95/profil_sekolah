<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../src/rate_limiter.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Check if user is in the verification stage
if (!isset($_SESSION['login_user']) || !isset($_SESSION['login_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

// Rate Limit: 3 attempts per 24 hours (86400 seconds)
if (!checkRateLimit('resend_code', 3, 86400)) {
    echo json_encode(['success' => false, 'message' => 'Batas kirim ulang kode tercapai (maksimal 3 kali sehari). Silakan coba lagi besok.']);
    exit;
}

// Ambil sisa kuota untuk dikirim ke frontend
$remaining = getRemainingAttempts('resend_code', 3, 86400);

try {
    $email = $_SESSION['login_email'];
    $name = $_SESSION['login_user']['name'];

    // Generate new 6-digit verification code
    $code = rand(100000, 999999);
    $_SESSION['login_code'] = $code;

    // Send Email using PHPMailer
    $mail = new PHPMailer(true);

    // Server settings (Configured for Laragon default)
    $mail->isSMTP();
    $mail->Host       = 'localhost';
    $mail->SMTPAuth   = false;
    $mail->Port       = 1025;

    // Recipients
    $mail->setFrom('admin@smklab.sch.id', 'SMK Lab Jakarta');
    $mail->addAddress($email, $name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'New Verification Code - SMK Lab Jakarta';
    $mail->Body    = "<h3>Your New Verification Code</h3><p>You requested a new code. Please use the following code to complete your login:</p><h1>$code</h1><p>This code is valid for this session.</p>";
    $mail->AltBody = "Your new verification code is: $code";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'New verification code sent to your email.', 'remaining' => $remaining]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $e->getMessage()]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>