<?php
require_once __DIR__ . '/conn.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
$gel = (int)($_GET['gelombang'] ?? 1) ?: 1;
try {
    $row = $conn->prepare("SELECT COUNT(*) c, COALESCE(MAX(id),0) m, COALESCE(SUM(CRC32(CONCAT(id,status,nilai_akhir))),0) h FROM pendaftar WHERE gelombang=?");
    $row->execute([$gel]);
    $h = $row->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['hash' => md5($h['c'] . '-' . $h['m'] . '-' . $h['h'])]);
} catch (Throwable $e) {
    echo json_encode(['hash' => '']);
}
