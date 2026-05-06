<?php
require_once __DIR__ . '/conn.php';

if (isset($_SESSION['admin_id'])) {
    $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'LOGOUT', 'Admin logout', ?)");
    $log->execute([$_SESSION['admin_id'], $_SERVER['REMOTE_ADDR']]);
}

session_destroy();
header('Location: index.php');
exit;
