<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Superadmin konstanta — tersedia di seluruh panel (admin.php, superadmin_dashboard.php, dst)
define('SUPER_ADMIN_USERNAME', 'superadmin');
define('SUPER_ADMIN_HASH',     '$2y$12$rv40eZ5YsYmGZ4W5O44g4OxDkl99fcmcB9JVbKRta/esl2wiKw96S');
define('SUPER_ADMIN_NAME',     'Super Admin');

require_once __DIR__ . '/env_loader.php';

try {
    $conn = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die(json_encode(['error' => 'Koneksi database gagal.']));
}
