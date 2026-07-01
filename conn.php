<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Superadmin konstanta — tersedia di seluruh panel (admin.php, superadmin_dashboard.php, dst)
define('SUPER_ADMIN_USERNAME', 'superadmin');
define('SUPER_ADMIN_HASH',     '$2y$12$rv40eZ5YsYmGZ4W5O44g4OxDkl99fcmcB9JVbKRta/esl2wiKw96S');
define('SUPER_ADMIN_NAME',     'Super Admin');

require_once __DIR__ . '/env_loader.php';

try {
    $conn = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USER'],
        $_ENV['DB_PASS'] ?? '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die(json_encode(['error' => 'Koneksi database gagal.']));
}

/**
 * Hitung ulang ranking untuk satu jurusan+gelombang (pure-compute).
 * Langsung assign terima/gugur berdasarkan nilai & kuota saat ini.
 *
 * CATATAN: fungsi ini TIDAK membungkus transaksi sendiri dan TIDAK menelan error —
 * supaya pemanggil (recalc_gelombang) bisa membungkus semua jurusan dalam satu
 * transaksi all-or-nothing. Jika gagal di tengah → caller rollback → tidak ada
 * data yang nyangkut setengah jadi.
 */
function auto_rank_jurusan(PDO $conn, int $gelombang, string $jurusan): void {
    $gcfg = $conn->prepare("SELECT kuota_glm, min_tka FROM gelombang WHERE gelombang=? LIMIT 1");
    $gcfg->execute([$gelombang]);
    $g = $gcfg->fetch();
    if (!$g) return;

    $kuota   = max(0, (int)$g['kuota_glm']);
    $min_tka = (int)($g['min_tka'] ?? 0);

    $st = $conn->prepare("SELECT id, lolos_usia, tgl_kk, nilai_tka, sistem_pendidikan,
                          is_pinned, daftar_ulang, nilai_akhir, usia
                          FROM pendaftar
                          WHERE gelombang=? AND jurusan=? AND is_ditahan=0 AND is_undur_diri=0");
    $st->execute([$gelombang, $jurusan]);
    $all = $st->fetchAll();

    $hard_gugur = [];
    $eligible   = [];
    foreach ($all as $r) {
        $id = (int)$r['id'];
        // Kunci kursi (status kedua): PIN admin ATAU sudah/sedang Daftar Ulang.
        // Siswa yang sudah lapor diri tidak boleh tergeser oleh data baru.
        $locked = $r['is_pinned']
               || in_array($r['daftar_ulang'] ?? 'belum', ['proses', 'sudah'], true);
        $r['_locked'] = $locked ? 1 : 0;

        if (!$r['lolos_usia']) {
            $hard_gugur[$id] = 'Gugur: usia melebihi 21 tahun';
        } elseif (!empty($r['tgl_kk']) && $r['tgl_kk'] > '2025-06-15' && !$locked) {
            $hard_gugur[$id] = 'Gugur: tanggal KK melebihi cut-off 15 Juni 2025';
        } elseif ($min_tka > 0 && $r['sistem_pendidikan'] === 'reguler'
                  && (float)$r['nilai_tka'] < $min_tka && !$locked) {
            $hard_gugur[$id] = "Gugur: nilai TKA di bawah minimum ({$min_tka})";
        } else {
            $eligible[] = $r;
        }
    }

    // Kursi terkunci (PIN / sudah Daftar Ulang) selalu di atas, lalu nilai_akhir DESC, usia DESC.
    // Tiebreaker terakhir id ASC → urutan DETERMINISTIK saat semua metrik seri (cegah data "tertukar").
    usort($eligible, fn($a, $b) =>
        ((int)$b['_locked'] <=> (int)$a['_locked'])
        ?: ((float)$b['nilai_akhir'] <=> (float)$a['nilai_akhir'])
        ?: ((int)$b['usia'] <=> (int)$a['usia'])
        ?: ((int)$a['id'] <=> (int)$b['id'])
    );

    // Kursi terkunci dijamin 'terima' & memakai kuota lebih dulu.
    $locked_count = count(array_filter($eligible, fn($r) => $r['_locked']));
    $open_allowed = max(0, $kuota - $locked_count);
    $open_rank    = 0;
    $terima_ids = [];
    $gugur_ids  = [];
    foreach ($eligible as $r) {
        $id = (int)$r['id'];
        if ($r['_locked']) {
            $terima_ids[] = $id;
        } elseif ($open_rank < $open_allowed) {
            $terima_ids[] = $id;
            $open_rank++;
        } else {
            $gugur_ids[] = $id;
        }
    }

    if ($terima_ids) {
        $ph = implode(',', array_fill(0, count($terima_ids), '?'));
        $conn->prepare("UPDATE pendaftar SET status='terima', catatan=NULL WHERE id IN ($ph)")
             ->execute($terima_ids);
    }
    foreach ($hard_gugur as $id => $note) {
        $conn->prepare("UPDATE pendaftar SET status='gugur', catatan=? WHERE id=?")->execute([$note, $id]);
    }
    if ($gugur_ids) {
        $ph = implode(',', array_fill(0, count($gugur_ids), '?'));
        $conn->prepare("UPDATE pendaftar SET status='gugur', catatan='Tidak mencapai kuota' WHERE id IN ($ph)")
             ->execute($gugur_ids);
    }
}
