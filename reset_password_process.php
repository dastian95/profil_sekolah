<?php
ob_start();
session_start();
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/AuditLogger.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request');
    }

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($password)) {
        throw new Exception('All fields required');
    }

    if ($password !== $confirm) {
        throw new Exception('Passwords do not match');
    }

    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid or expired token');
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id_pendaftar = ?");
    if ($stmt->execute([$hashed, $user['id_pendaftar']])) {
        // Log password reset
        try {
            AuditLogger::init($conn, $user['id_pendaftar']);
            AuditLogger::log(AuditLogger::ACTION_PASSWORD_RESET, 'users', $user['id_pendaftar'], [
                'action_type' => 'forgot_password_reset'
            ]);
        } catch (Exception $e) { /* Ignore log error */
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        throw new Exception('Database error');
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
