<?php
require_once __DIR__ . '/../src/conn.php';

try {
    // Update semua user menjadi terverifikasi
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE is_verified = 0");
    $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "<div style='font-family: sans-serif; padding: 20px; text-align: center;'>";
    echo "<h2 style='color: green;'>Berhasil!</h2>";
    echo "<p>$count akun pengguna berhasil diverifikasi.</p>";
    echo "<p>Silakan hapus file ini (fix_verification.php) jika sudah tidak digunakan.</p>";
    echo "<a href='index.php' style='background: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Kembali ke Home</a>";
    echo "</div>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>