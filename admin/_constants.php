<?php
// Konstanta global panel admin — di-include di tiap halaman admin/ yang butuh
// Konsistensi data jurusan supaya tidak duplikat dan typo-free

const JURUSAN_LIST = [
    'Rekayasa Perangkat Lunak (RPL)',
    'Teknik Komputer dan Jaringan (TKJ)',
    'Asisten Keperawatan (AP)',
    'Tata Kecantikan Kulit dan Rambut (TKKR)',
];

const JURUSAN_SHORT = [
    'Rekayasa Perangkat Lunak (RPL)'          => 'RPL',
    'Teknik Komputer dan Jaringan (TKJ)'      => 'TKJ',
    'Asisten Keperawatan (AP)'                => 'AP',
    'Tata Kecantikan Kulit dan Rambut (TKKR)' => 'TKKR',
];

const STATUS_BADGE = [
    'terima'   => 'bg-success',
    'lengkap'  => 'bg-info text-dark',
    'gugur'    => 'bg-danger',
    'diproses' => 'bg-warning text-dark',
];

const STATUS_LABEL = [
    'terima'   => 'Terima',
    'lengkap'  => 'Lengkap',
    'gugur'    => 'Gugur',
    'diproses' => 'Diproses',
];

// Auto-detect gelombang aktif berdasarkan tanggal hari ini
function getActiveGelombang(PDO $conn): ?array {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT * FROM gelombang WHERE ? BETWEEN tanggal_buka AND tanggal_tutup ORDER BY gelombang LIMIT 1");
    $stmt->execute([$today]);
    $row = $stmt->fetch();
    if ($row) return $row;
    // Fallback: gelombang berikutnya yang akan dibuka, atau yang terakhir lewat
    $stmt = $conn->prepare("SELECT * FROM gelombang WHERE tanggal_buka >= ? ORDER BY tanggal_buka LIMIT 1");
    $stmt->execute([$today]);
    $row = $stmt->fetch();
    if ($row) return $row;
    return $conn->query("SELECT * FROM gelombang ORDER BY gelombang DESC LIMIT 1")->fetch() ?: null;
}

// Mata pelajaran SMP — dipakai di matrix detail raport
const MATA_PELAJARAN = [
    'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
    'Bahasa Indonesia',
    'Matematika',
    'Ilmu Pengetahuan Alam (IPA)',
    'Ilmu Pengetahuan Sosial (IPS)',
    'Bahasa Inggris',
];

const SEMESTER_LIST = [1, 2, 3, 4, 5];

// Batas usia otomatis masuk Daftar Khusus (85% nilai raport, tanpa TKA)
const KHUSUS_MIN_USIA = 17;

// Kurikulum PKBM Paket B Setara SMP
const PKBM_MAPEL_UMUM = [
    'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
    'Bahasa Indonesia',
    'Bahasa Inggris',
    'Matematika',
    'Ilmu Pengetahuan Alam (IPA)',
    'Ilmu Pengetahuan Sosial (IPS)',
];
const PKBM_MAPEL_KHUSUS = []; // dihapus — hanya 6 mapel umum yang dipakai
const PKBM_MAPEL = [
    'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
    'Bahasa Indonesia',
    'Bahasa Inggris',
    'Matematika',
    'Ilmu Pengetahuan Alam (IPA)',
    'Ilmu Pengetahuan Sosial (IPS)',
];
const PKBM_TINGKAT = [1 => 'Tingkat 3 (Kls VII–VIII)', 2 => 'Tingkat 4 (Kls IX)'];

// Helper: log aksi admin (handle superadmin = NULL otomatis)
function log_admin_action(PDO $conn, string $action, string $details): void {
    $admin_id = empty($_SESSION['is_super']) ? ($_SESSION['admin_id'] ?? null) : null;
    try {
        $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Throwable) { /* abaikan jika error log */ }
}

// Helper: superadmin utama (akun id=1, username 'superadmin') — level tertinggi.
// Hanya dia yang boleh kelola akun superadmin lain & akses Database Manager.
function is_primary_super(): bool {
    return !empty($_SESSION['is_super']) && (int)($_SESSION['super_acc_id'] ?? 0) === 1;
}

// ════ ZONASI GELOMBANG 2 ═════════════════════════════════════════════════════
// Koordinat sekolah (SMKS Lab Jakarta — Jl. Rawa Jaya, Duren Sawit)
const SCHOOL_LAT = -6.2350331;
const SCHOOL_LNG = 106.9439031;

// Kelurahan Jakarta Timur per kecamatan dengan titik tengah (perkiraan)
// untuk kalkulasi jarak garis lurus ke sekolah
const KELURAHAN_ZONASI = [
    'Duren Sawit' => [
        'Pondok Bambu'  => [-6.2390, 106.9070],
        'Duren Sawit'   => [-6.2330, 106.9190],
        'Klender'       => [-6.2180, 106.8990],
        'Malaka Sari'   => [-6.2230, 106.9270],
        'Malaka Jaya'   => [-6.2230, 106.9360],
        'Pondok Kelapa' => [-6.2480, 106.9330],
        'Pondok Kopi'   => [-6.2270, 106.9440],
    ],
    'Cakung' => [
        'Jatinegara (Cakung)' => [-6.2080, 106.9100],
        'Penggilingan'        => [-6.2080, 106.9300],
        'Pulogebang'          => [-6.2080, 106.9510],
        'Ujung Menteng'       => [-6.1950, 106.9650],
        'Cakung Barat'        => [-6.1830, 106.9300],
        'Cakung Timur'        => [-6.1830, 106.9500],
        'Rawa Terate'         => [-6.1950, 106.9100],
    ],
    'Jatinegara' => [
        'Kampung Melayu'          => [-6.2240, 106.8600],
        'Bali Mester'             => [-6.2260, 106.8680],
        'Bidara Cina'             => [-6.2300, 106.8650],
        'Cipinang Cempedak'       => [-6.2380, 106.8680],
        'Rawa Bunga'              => [-6.2230, 106.8730],
        'Cipinang Besar Utara'    => [-6.2240, 106.8820],
        'Cipinang Besar Selatan'  => [-6.2350, 106.8800],
        'Cipinang Muara'          => [-6.2330, 106.8900],
    ],
    'Pulogadung' => [
        'Rawamangun'      => [-6.1950, 106.8830],
        'Pisangan Timur'  => [-6.2130, 106.8780],
        'Cipinang'        => [-6.2200, 106.8850],
        'Jatinegara Kaum' => [-6.2120, 106.8930],
        'Jati'            => [-6.1900, 106.8900],
        'Kayu Putih'      => [-6.1830, 106.8920],
        'Pulo Gadung'     => [-6.1860, 106.9030],
    ],
    'Matraman' => [
        'Kebon Manggis'     => [-6.2080, 106.8580],
        'Palmeriam'         => [-6.2050, 106.8650],
        'Pisangan Baru'     => [-6.2150, 106.8680],
        'Utan Kayu Utara'   => [-6.1950, 106.8700],
        'Utan Kayu Selatan' => [-6.2020, 106.8730],
        'Kayu Manis'        => [-6.2060, 106.8620],
    ],
    'Kramat Jati' => [
        'Cawang'      => [-6.2480, 106.8650],
        'Cililitan'   => [-6.2600, 106.8650],
        'Kramat Jati' => [-6.2730, 106.8660],
        'Batu Ampar'  => [-6.2680, 106.8560],
        'Balekambang' => [-6.2780, 106.8530],
        'Tengah'      => [-6.2880, 106.8600],
        'Dukuh'       => [-6.2900, 106.8700],
    ],
    'Makasar' => [
        'Kebon Pala'           => [-6.2540, 106.8730],
        'Makasar'              => [-6.2680, 106.8780],
        'Halim Perdanakusuma'  => [-6.2660, 106.8900],
        'Cipinang Melayu'      => [-6.2520, 106.9050],
        'Pinang Ranti'         => [-6.2840, 106.8830],
    ],
    'Pasar Rebo' => [
        'Gedong'    => [-6.3000, 106.8580],
        'Cijantung' => [-6.3170, 106.8580],
        'Baru'      => [-6.3070, 106.8530],
        'Kalisari'  => [-6.3330, 106.8560],
        'Pekayon'   => [-6.3300, 106.8650],
    ],
    'Ciracas' => [
        'Rambutan'         => [-6.3050, 106.8740],
        'Susukan'          => [-6.3170, 106.8700],
        'Ciracas'          => [-6.3270, 106.8750],
        'Kelapa Dua Wetan' => [-6.3380, 106.8830],
        'Cibubur'          => [-6.3500, 106.8750],
    ],
    'Cipayung' => [
        'Lubang Buaya'   => [-6.2900, 106.9050],
        'Ceger'          => [-6.3050, 106.8950],
        'Cipayung'       => [-6.3180, 106.8950],
        'Bambu Apus'     => [-6.3050, 106.9050],
        'Setu'           => [-6.3170, 106.9150],
        'Cilangkap'      => [-6.3320, 106.9050],
        'Munjul'         => [-6.3400, 106.8950],
        'Pondok Ranggon' => [-6.3500, 106.9150],
    ],
];

// Jarak garis lurus (haversine) dari koordinat ke sekolah, dalam km
function zonasi_jarak_km(float $lat, float $lng): float {
    $dLat = deg2rad($lat - SCHOOL_LAT);
    $dLng = deg2rad($lng - SCHOOL_LNG);
    $a = sin($dLat / 2) ** 2
       + cos(deg2rad(SCHOOL_LAT)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;
    return round(6371.0 * 2 * asin(sqrt($a)), 2);
}

// Cari kecamatan + jarak dari nama kelurahan; null jika tidak terdaftar
function zonasi_lookup(string $kelurahan): ?array {
    foreach (KELURAHAN_ZONASI as $kec => $list) {
        if (isset($list[$kelurahan])) {
            [$lat, $lng] = $list[$kelurahan];
            return ['kecamatan' => $kec, 'jarak' => zonasi_jarak_km($lat, $lng)];
        }
    }
    return null;
}

// Status orang tua (Gelombang 2)
const STATUS_ORTU_LABEL = [
    'tidak'       => 'Lengkap (bukan Yatim/Piatu)',
    'yatim'       => 'Yatim — Ayah meninggal',
    'piatu'       => 'Piatu — Ibu meninggal',
    'yatim_piatu' => 'Yatim Piatu — keduanya meninggal',
];

// ── Daftar Sekolah Asal (SMP) ───────────────────────────────────────────────
// Tabel sekolah_asal dikelola lewat menu "Kelola Sekolah".
// Daftar awal di bawah = SMP Negeri sekitar Jakarta Timur (alamat awal/ancar-ancar,
// silakan dilengkapi/dikoreksi lewat menu Kelola Sekolah).
const SEKOLAH_SEED = [
    ['SMP Negeri 99 Jakarta',  'Jl. Pahlawan Revolusi No.5, Pondok Bambu, Kec. Duren Sawit, Jakarta Timur'],
    ['SMP Negeri 138 Jakarta', 'Jl. Swadaya PLN, Klender, Kec. Duren Sawit, Jakarta Timur'],
    ['SMP Negeri 195 Jakarta', 'Jl. Mawar Merah VI, Malaka Jaya, Kec. Duren Sawit, Jakarta Timur'],
    ['SMP Negeri 198 Jakarta', 'Jl. Bunga Rampai X, Malaka Jaya, Kec. Duren Sawit, Jakarta Timur'],
    ['SMP Negeri 233 Jakarta', 'Jl. Pondok Kopi Raya, Pondok Kopi, Kec. Duren Sawit, Jakarta Timur'],
    ['SMP Negeri 252 Jakarta', 'Jl. Kampung Baru, Pondok Kopi, Kec. Duren Sawit, Jakarta Timur'],
    ['SMP Negeri 27 Jakarta',  'Jl. Kayu Tinggi, Cakung Timur, Kec. Cakung, Jakarta Timur'],
    ['SMP Negeri 172 Jakarta', 'Jl. Stasiun Cakung, Pulo Gebang, Kec. Cakung, Jakarta Timur'],
    ['SMP Negeri 168 Jakarta', 'Jl. Penggilingan, Kec. Cakung, Jakarta Timur'],
    ['SMP Negeri 150 Jakarta', 'Jl. Cipinang Muara, Kec. Jatinegara, Jakarta Timur'],
    ['SMP Negeri 51 Jakarta',  'Jl. Pisangan Lama, Kec. Pulo Gadung, Jakarta Timur'],
    ['SMP Negeri 74 Jakarta',  'Jl. Pulo Asem Utara, Jati, Kec. Pulo Gadung, Jakarta Timur'],
    ['SMP Negeri 117 Jakarta', 'Jl. Jambore, Cibubur, Kec. Ciracas, Jakarta Timur'],
    ['SMP Negeri 9 Jakarta',   'Jl. Salemba Raya, Kec. Senen, Jakarta Pusat'],
    ['MTs Negeri 9 Jakarta',   'Jl. Pahlawan Komarudin, Penggilingan, Kec. Cakung, Jakarta Timur'],
];

// Buat tabel sekolah_asal bila belum ada, seed daftar awal sekali saja.
function ensure_sekolah_table(PDO $conn): void {
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS sekolah_asal (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(150) NOT NULL,
            alamat VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $cnt = (int)$conn->query("SELECT COUNT(*) FROM sekolah_asal")->fetchColumn();
        if ($cnt === 0) {
            $ins = $conn->prepare("INSERT INTO sekolah_asal (nama, alamat) VALUES (?, ?)");
            foreach (SEKOLAH_SEED as [$snama, $salamat]) {
                $ins->execute([$snama, $salamat]);
            }
        }
    } catch (PDOException $e) { /* abaikan bila gagal (mis. permission) */ }
}
