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
