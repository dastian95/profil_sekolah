<?php
$adminPassword = "admin123";
$siswaPassword = "siswa123";

$adminHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);
$siswaHash = password_hash($siswaPassword, PASSWORD_BCRYPT, ['cost' => 12]);

file_put_contents(__DIR__ . '/hashes.txt', "admin:" . $adminHash . "\n" . "siswa:" . $siswaHash);
echo "Hashes saved!";
?>
