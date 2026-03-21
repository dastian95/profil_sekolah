<?php
header('Content-Type: application/json');

// Load Environment Variables
require_once __DIR__ . '/conn.php';

// 1. Validasi Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$nisn = trim($_POST['nisn'] ?? '');

if (empty($nisn)) {
    echo json_encode(['success' => false, 'message' => 'NISN diperlukan']);
    exit;
}

// 2. Konfigurasi API Eksternal (Opsional)
// Masukkan URL API Dapodik/Kemdikbud di sini jika Anda sudah memiliki akses.
// Biarkan kosong "" jika ingin menggunakan mode simulasi (Dummy Data).
$api_base_url = $_ENV['API_NISN_URL'] ?? "";
$api_url_endpoint = !empty($api_base_url) ? $api_base_url . $nisn : "";

// 3. Proses Pengambilan Data
$found_data = null;
$source = "Simulasi";

// A. Coba ambil dari API jika URL diisi
if (!empty($api_url_endpoint)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout 10 detik
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Abaikan SSL untuk dev local

    // Jika API butuh Header/Token (Uncomment jika perlu):
    // curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //    'Authorization: Bearer TOKEN_ANDA',
    //    'Content-Type: application/json'
    // ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200 && !empty($response)) {
        $json_response = json_decode($response, true);

        // Sesuaikan mapping data di sini sesuai respon API asli Anda
        // Contoh: API mengembalikan { "data": { "nama": "Budi" } }
        if (isset($json_response['data'])) {
            $found_data = [
                'nama' => $json_response['data']['nama'] ?? '',
                'asal_sekolah' => $json_response['data']['sekolah'] ?? '',
                'tanggal_lahir' => $json_response['data']['tgl_lahir'] ?? ''
            ];
            $source = "API Eksternal";
        }
    }
}

// B. Fallback ke Data Simulasi jika API gagal, kosong, atau tidak diset
if ($found_data === null) {
    $data_simulasi = [
        '3082389086' => [
            'nama' => 'Aufa Dzaky Zuhdi Wicaksono',
            'asal_sekolah' => 'SMPN 6 Kota Bekasi',
            'tanggal_lahir' => '2008-02-01'
        ],
        '1234567890' => [
            'nama' => 'Siswa Contoh Integrasi',
            'asal_sekolah' => 'SMPN 1 Jakarta',
            'tanggal_lahir' => '2008-01-01'
        ],
        '0011223344' => [
            'nama' => 'Citra Dewi',
            'asal_sekolah' => 'MTS Negeri 1',
            'tanggal_lahir' => '2008-05-15'
        ]
    ];

    if (array_key_exists($nisn, $data_simulasi)) {
        $found_data = $data_simulasi[$nisn];
        $source = "Database Simulasi";
    }
}

// 4. Kirim Respon ke Frontend
if ($found_data) {
    echo json_encode([
        'success' => true,
        'message' => "Data ditemukan ($source)",
        'data' => $found_data
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'NISN tidak ditemukan. Coba gunakan 3082389086 untuk tes.'
    ]);
}
