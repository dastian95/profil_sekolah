<?php
require_once __DIR__ . '/conn.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('Invalid token');
}

$stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE verification_token = ? AND is_verified = 0");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die('Invalid or expired token');
}

$stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id_pendaftar = ?");
$stmt->execute([$user['id_pendaftar']]);

echo 'Email verified successfully! You can now <a href="login.php">log in</a>.';
?>