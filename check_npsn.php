<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$npsn = trim($_POST['npsn'] ?? '');

if (empty($npsn)) {
    echo json_encode(['success' => false, 'message' => 'NPSN is required']);
    exit;
}

// Simulasi Data Sekolah (Bisa diganti dengan API Kemdikbud jika ada)
$schools = [
    '20103333' => 'SMP Negeri 1 Jakarta',
    '20104444' => 'SMP Negeri 2 Jakarta',
    '20105555' => 'MTS Negeri 1 Jakarta',
    '12345678' => 'SMP Contoh Indonesia'
];

if (array_key_exists($npsn, $schools)) {
    echo json_encode([
        'success' => true,
        'data' => ['nama_sekolah' => $schools[$npsn]]
    ]);
} else {
    // Fallback jika tidak ditemukan di dummy data, kembalikan nama generik agar user tidak bingung
    if (strlen($npsn) >= 8 && ctype_digit($npsn)) {
         echo json_encode([
            'success' => true,
            'data' => ['nama_sekolah' => 'Sekolah NPSN ' . $npsn]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'NPSN tidak ditemukan.']);
    }
}
?>