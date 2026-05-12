<?php
require_once __DIR__ . '/conn.php';

if (isset($_SESSION['admin_id'])) {
    // Superadmin (id=0, hardcoded) → simpan log dengan admin_id NULL
    $is_super = !empty($_SESSION['is_super']);
    $admin_id = $is_super ? null : (int)$_SESSION['admin_id'];
    $action   = $is_super ? 'LOGOUT_SUPER' : 'LOGOUT';
    $details  = $is_super ? 'Superadmin logout' : 'Admin logout';

    try {
        $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $log->execute([$admin_id, $action, $details, $_SERVER['REMOTE_ADDR']]);
    } catch (Throwable $e) { /* abaikan jika ada error log */ }
}

session_destroy();

// Redirect kembali ke form login admin (bukan ke index publik)
$key = $_ENV['ADMIN_KEY'] ?? '';
header('Location: admin.php?k=' . urlencode($key));
exit;
