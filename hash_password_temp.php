<?php
// Temporary script to generate password hashes
$adminPassword = "admin123";
$siswaPassword = "siswa123";

$adminHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
$siswaHash = password_hash($siswaPassword, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Admin Hash: " . $adminHash . "\n";
echo "Siswa Hash: " . $siswaHash . "\n";

// Delete file after execution
unlink(__FILE__);
