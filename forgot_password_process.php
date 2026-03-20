<?php
// Start output buffering to prevent unwanted output
ob_start();

header('Content-Type: application/json');

// Load Composer's autoloader
require_once __DIR__ . '/../src/conn.php';

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

        $resetLink = rtrim($_ENV['APP_URL'], '/') . "/reset_password.php?token=" . $token;
        
        // Send Email using PHPMailer
        $mail = new PHPMailer(true);

        // Server settings (Configured for Laragon default)
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth   = filter_var($_ENV['SMTP_AUTH'], FILTER_VALIDATE_BOOLEAN);
        $mail->Port       = $_ENV['SMTP_PORT'];

        // Recipients
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($email, $user['name']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Request - SMK Lab Jakarta';
        $mail->Body    = "<h3>Password Reset</h3><p>Hi " . htmlspecialchars($user['name']) . ",</p><p>We received a request to reset your password. Click the link below to create a new password:</p><p><a href='$resetLink' style='background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p><p>Or copy this link: $resetLink</p><p>This link will expire in 1 hour.</p>";
        $mail->AltBody = "Hi " . $user['name'] . ",\n\nWe received a request to reset your password. Visit this link to create a new password:\n$resetLink\n\nThis link expires in 1 hour.";

        $mail->send();
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
?>