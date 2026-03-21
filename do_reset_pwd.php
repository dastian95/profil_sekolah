<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'profil_sekolah';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate new password hashes
$adminPassword = "admin123";
$siswaPassword = "siswa123";

$adminHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
$siswaHash = password_hash($siswaPassword, PASSWORD_BCRYPT, ['cost' => 12]);

// Update admin password (ID 3)
$sql1 = "UPDATE users SET password = '" . $conn->real_escape_string($adminHash) . "' WHERE id_pendaftar = 3 AND role = 'admin'";
$result1 = $conn->query($sql1);

// Update siswa password (ID 6)
$sql2 = "UPDATE users SET password = '" . $conn->real_escape_string($siswaHash) . "' WHERE id_pendaftar = 6 AND role = 'user'";
$result2 = $conn->query($sql2);

if ($result1 && $result2) {
    echo "✓ Admin password updated to: admin123\n";
    echo "✓ Siswa password updated to: siswa123\n";
    echo "\nBoth passwords have been reset successfully!";
} else {
    echo "Error updating passwords: " . $conn->error;
}

$conn->close();
?>
