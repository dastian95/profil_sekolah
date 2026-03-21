<?php
$adminPassword = "admin123";
$siswaPassword = "siswa123";

$adminHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
$siswaHash = password_hash($siswaPassword, PASSWORD_BCRYPT, ['cost' => 12]);

echo $adminHash . "\n";
echo $siswaHash . "\n";
?>
