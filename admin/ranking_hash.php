<?php
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');
try {
    $gel = (int)($_GET['gelombang'] ?? 1) ?: 1;
    $s   = $conn->prepare("SELECT COUNT(*) as n, MAX(updated_at) as ts FROM pendaftar WHERE gelombang=?");
    $s->execute([$gel]);
    $h   = $s->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['hash' => md5($h['n'] . $h['ts'])]);
} catch (Throwable $e) {
    echo json_encode(['hash' => '']);
}
