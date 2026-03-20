<?php
/**
 * Database Connection Handler
 * Uses PDO for secure database communication
 * Environment variables loaded from .env file
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Get database credentials from environment
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_name = $_ENV['DB_NAME'] ?? 'profil_sekolah';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_pass = $_ENV['DB_PASS'] ?? '';

try {
    // Create PDO connection
    $conn = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Connection successful - optionally set timezone
    $conn->exec("SET time_zone = '+00:00'");
    
} catch (PDOException $e) {
    // Log error or display user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    
    // Display error to user (development only - remove in production)
    if (php_sapi_name() === 'cli') {
        echo "Database Connection Error: " . $e->getMessage() . "\n";
    } else {
        die("Connection to database failed. Please contact administrator.");
    }
    exit;
}

?>
