<?php
// ═══════════════════════════════════════════════════════════════════════════
//  Endpoint pemrosesan ranking di LATAR (Lapisan 2)
//  Dipanggil via AJAX dari halaman admin (pendaftar list & ranking).
//  - Throttle: per gelombang, default 8 detik (kecuali ?force=1).
//  - Transaksional & idempoten (lihat recalc_gelombang di _constants.php).
//  - Gelombang terkunci dilewati (Kunci Kompetisi).
//  Tidak pernah menyentuh request "Simpan" — itu sebabnya simpan tidak akan
//  pernah bertabrakan / menghilangkan data.
// ═══════════════════════════════════════════════════════════════════════════
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

// Hanya untuk sesi admin yang login (cookie session ikut terkirim dari dashboard)
if (empty($_SESSION['admin_id']) && empty($_SESSION['is_super'])) {
    http_response_code(403);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$gel      = (int)($_GET['gelombang'] ?? 0);
$force    = !empty($_GET['force']);
$throttle = 8; // detik — "dijauhkan waktunya" supaya fokus proses, bukan render

// gelombang 0/kosong → semua gelombang yang ada di tabel
try {
    if ($gel > 0) {
        $gels = [$gel];
    } else {
        $gels = array_map('intval', $conn->query("SELECT DISTINCT gelombang FROM gelombang ORDER BY gelombang")->fetchAll(PDO::FETCH_COLUMN));
    }
} catch (Throwable) {
    $gels = [];
}

$ran     = [];
$skipped = [];
foreach ($gels as $g) {
    // Lewati gelombang terkunci (peringkat dibekukan)
    try {
        $lk = $conn->prepare("SELECT is_locked FROM gelombang WHERE gelombang=? LIMIT 1");
        $lk->execute([$g]);
        if ((int)$lk->fetchColumn() === 1) { $skipped[] = ['gelombang' => $g, 'reason' => 'locked']; continue; }
    } catch (Throwable) {}

    // Throttle: jangan hitung lagi bila baru saja dihitung (kecuali force)
    if (!$force && (time() - rank_last_run($conn, $g)) < $throttle) {
        $skipped[] = ['gelombang' => $g, 'reason' => 'throttled'];
        continue;
    }

    try {
        $total = recalc_gelombang($conn, $g);   // transaksional, all-or-nothing
        rank_mark_run($conn, $g);
        $ran[] = ['gelombang' => $g, 'terima' => $total];
    } catch (Throwable $e) {
        // Gagal → tidak fatal. Data lama tetap utuh (sudah di-rollback).
        // Poke berikutnya akan mencoba lagi.
        $skipped[] = ['gelombang' => $g, 'reason' => 'error'];
    }
}

echo json_encode(['ran' => $ran, 'skipped' => $skipped, 'time' => date('H:i:s')]);
