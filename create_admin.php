<?php
/**
 * This is a one-time use script to create an admin account.
 *
 * How to use:
 * 1. Place this file in your web root.
 * 2. Navigate to this file in your browser (e.g., http://localhost/profil_sekolah/create_admin.php).
 * 3. After running it once, DELETE THIS FILE for security reasons.
 */

require_once __DIR__ . '/conn.php';

echo "<pre>";

// --- Admin Account Details ---
$name = 'admin';
$email = 'admin@example.com';
$password = 'password123'; // You should change this to a more secure password
$role = 'admin';
// -----------------------------

try {
    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE name = ? OR email = ?");
    $stmt->execute([$name, $email]);
    if ($stmt->fetch()) {
        die("Admin user '$name' or email '$email' already exists. No action taken.");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert the new admin user (and mark as verified)
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, ?, 1)");
    if ($stmt->execute([$name, $email, $hashed_password, $role])) {
        echo "Admin user '$name' created successfully!\n";
        echo "You can now log in with username '$name' and the password you set in this script.\n";
        echo "IMPORTANT: Please delete this file (create_admin.php) immediately.";
    } else {
        echo "Failed to create admin user.";
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

echo "</pre>";
?>