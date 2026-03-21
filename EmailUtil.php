<?php

/**
 * Email Utility for Forgot Password
 * Handles both SMTP and file-based email logging for development
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailUtil
{
    public static function sendResetPasswordEmail($email, $userName, $resetLink)
    {

        $mail = new PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'] ?? 'localhost';
            $mail->SMTPAuth   = filter_var($_ENV['SMTP_AUTH'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
            $mail->Port       = $_ENV['SMTP_PORT'] ?? 1025;
            $mail->Username   = $_ENV['SMTP_USER'] ?? '';
            $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
            $mail->SMTPSecure = '';

            // Disable SSL/TLS for local development
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $mail->addAddress($email, $userName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Request - SMK Lab Jakarta';
            $mail->Body    = "<h3>Password Reset Request</h3><p>Hi " . htmlspecialchars($userName) . ",</p><p>You requested to reset your password. Click the link below:</p><p><a href='" . htmlspecialchars($resetLink) . "' style='background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p><p>Or copy this link:<br>" . htmlspecialchars($resetLink) . "</p><p>This link will expire in 1 hour.</p><p>If you didn't request this, please ignore this email.</p>";
            $mail->AltBody = "Hi " . $userName . ",\n\nYou requested to reset your password. Visit this link:\n" . $resetLink . "\n\nThis link expires in 1 hour.\n\nIf you didn't request this, please ignore this email.";

            $mail->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (Exception $e) {
            // Log error and fallback to file-based system
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return self::saveCashedEmail($email, $userName, $resetLink);
        }
    }

    /**
     * Alternative: Save reset link to a local file for testing
     * File will be stored in a logs directory
     */
    private static function saveCashedEmail($email, $userName, $resetLink)
    {
        $logDir = __DIR__ . '/email_logs';

        // Create directory if not exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $emailData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $email,
            'to_name' => $userName,
            'subject' => 'Reset Password Request - SMK Lab Jakarta',
            'reset_link' => $resetLink,
            'expires_in' => '1 hour'
        ];

        $filename = $logDir . '/reset_' . date('Y-m-d_H-i-s') . '_' . md5($email) . '.json';
        file_put_contents($filename, json_encode($emailData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return [
            'success' => true,
            'message' => 'Reset link has been generated. For development, <a href="email_logs/" target="_blank">view sent emails</a>',
            'reset_link' => $resetLink // Include for display if needed
        ];
    }
}
