<?php
// Sesi Daftar Ulang — alur sama seperti antrian SPMB, tapi data yang diisi berbeda
// (data orang tua, alamat lengkap, dll). Hanya superadmin.

$today = date('Y-m-d');

// Setting sekolah untuk kop surat / SPTJM
$sch_alamat = 'Jl. Rawa Jaya No.37, Duren Sawit, Jakarta Timur 13460';
try {
    $rs = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('sekolah_alamat') LIMIT 5");
    if ($rs) foreach ($rs->fetchAll(PDO::FETCH_ASSOC) as $row)
        if ($row['setting_key'] === 'sekolah_alamat') $sch_alamat = $row['setting_value'];
} catch (PDOException $e) {}

// URL absolut aset — wajib utk gambar di popup cetak (about:blank tak punya base relatif)
$_asset_scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$_asset_dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$asset_base    = $_asset_scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_asset_dir;
// Versi gambar (anti-cache): ikut waktu modifikasi file gambar
$asset_ver     = (string)(@filemtime(__DIR__ . '/../assets/img/formulir-1.png')
                 ?: @filemtime(__DIR__ . '/../assets/img/kop-surat.png') ?: time());

// ── Auto-migrate ─────────────────────────────────────────────────────────────
// Tandai antrian DU supaya tidak campur dengan antrian SPMB
try { $conn->exec("ALTER TABLE antrian ADD COLUMN jenis ENUM('ppdb','daftar_ulang') NOT NULL DEFAULT 'ppdb' AFTER tanggal"); } catch (PDOException $e) {}

// Nomor DU per-jurusan: snapshot jurusan di tiap tiket antrian, lalu ikutkan di
// UNIQUE KEY. Kolom NOT NULL DEFAULT '' (bukan NULL) — baris 'ppdb' selalu ''
// sehingga constraint lama untuk antrian SPMB TIDAK berubah sama sekali
// (MySQL menganggap NULL selalu "beda", tapi '' tetap dibandingkan biasa).
try { $conn->exec("ALTER TABLE antrian ADD COLUMN jurusan_du VARCHAR(100) NOT NULL DEFAULT '' AFTER jenis"); } catch (PDOException $e) {}
try { $conn->exec("ALTER TABLE antrian DROP INDEX uk_tanggal_jenis_nomor_fase"); } catch (PDOException $e) {}
try { $conn->exec("ALTER TABLE antrian ADD UNIQUE KEY uk_tanggal_jenis_nomor_fase_jur (tanggal, jenis, nomor, fase, jurusan_du)"); } catch (PDOException $e) {}
// Backfill SEKALI: tiket DU lama (dibuat sebelum kolom jurusan_du ada, isinya '')
// diisi ulang dari meja/pendaftar-nya — supaya hitungan per-jurusan tidak
// menganggap tiket lama "tidak pernah ada" lalu mengulang dari 1 lagi.
// Idempoten — WHERE jurusan_du='' otomatis tak menemukan apa-apa di run berikutnya.
// UPDATE IGNORE: kalau ada baris yang backfill-nya bentrok dgn UNIQUE KEY (nomor
// sama sudah dipakai di jurusan itu), baris itu DILEWATI — bukan menggagalkan
// seluruh backfill. Kolom 'nomor' tidak pernah disentuh (nomor aktif TIDAK berubah).
try { $conn->exec("UPDATE IGNORE antrian a JOIN meja m ON m.id=a.meja_id
                    SET a.jurusan_du=m.jurusan_du
                    WHERE a.jenis='daftar_ulang' AND a.jurusan_du='' AND m.jurusan_du IS NOT NULL AND m.jurusan_du<>''"); } catch (Throwable $e) {}
try { $conn->exec("UPDATE IGNORE antrian a JOIN pendaftar p ON p.id=a.pendaftar_id
                    SET a.jurusan_du=p.jurusan
                    WHERE a.jenis='daftar_ulang' AND a.jurusan_du='' AND a.meja_id IS NULL"); } catch (Throwable $e) {}

// Kolom tambahan pendaftar untuk data lengkap siswa (sesuai Formulir Pendaftaran)
$new_cols = [
    "ALTER TABLE pendaftar ADD COLUMN daftar_ulang ENUM('belum','proses','sudah') NOT NULL DEFAULT 'belum' AFTER catatan",
    "ALTER TABLE pendaftar MODIFY COLUMN daftar_ulang ENUM('belum','proses','sudah') NOT NULL DEFAULT 'belum'",
    "ALTER TABLE pendaftar ADD COLUMN daftar_ulang_at DATETIME NULL AFTER daftar_ulang",
    "ALTER TABLE pendaftar ADD COLUMN nis VARCHAR(20) NULL AFTER nisn",
    "ALTER TABLE pendaftar ADD COLUMN nik VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN no_kk VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN kewarganegaraan VARCHAR(30) NOT NULL DEFAULT 'WNI'",
    "ALTER TABLE pendaftar ADD COLUMN tahun_lulus SMALLINT UNSIGNED NULL",
    "ALTER TABLE pendaftar ADD COLUMN kip_kjp_kps VARCHAR(50) NULL",
    "ALTER TABLE pendaftar ADD COLUMN tempat_lahir VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN agama ENUM('Islam','Kristen','Katolik','Hindu','Buddha','Konghucu','Lainnya') NULL",
    "ALTER TABLE pendaftar ADD COLUMN email VARCHAR(150) NULL",
    "ALTER TABLE pendaftar ADD COLUMN anak_ke TINYINT UNSIGNED NULL",
    "ALTER TABLE pendaftar ADD COLUMN alamat_lengkap TEXT NULL",
    "ALTER TABLE pendaftar ADD COLUMN rt VARCHAR(5) NULL",
    "ALTER TABLE pendaftar ADD COLUMN rw VARCHAR(5) NULL",
    "ALTER TABLE pendaftar ADD COLUMN kecamatan VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN kabupaten VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN provinsi VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN kode_pos VARCHAR(10) NULL",
    "ALTER TABLE pendaftar ADD COLUMN nama_ayah VARCHAR(150) NULL",
    "ALTER TABLE pendaftar ADD COLUMN nik_ayah VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN pendidikan_ayah VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN pekerjaan_ayah VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN penghasilan_ayah VARCHAR(50) NULL",
    "ALTER TABLE pendaftar ADD COLUMN telp_ayah VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN alamat_ayah TEXT NULL",
    "ALTER TABLE pendaftar ADD COLUMN nama_ibu VARCHAR(150) NULL",
    "ALTER TABLE pendaftar ADD COLUMN nik_ibu VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN pendidikan_ibu VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN pekerjaan_ibu VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN penghasilan_ibu VARCHAR(50) NULL",
    "ALTER TABLE pendaftar ADD COLUMN telp_ibu VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN alamat_ibu TEXT NULL",
    "ALTER TABLE pendaftar ADD COLUMN nama_wali VARCHAR(150) NULL",
    "ALTER TABLE pendaftar ADD COLUMN hubungan_wali VARCHAR(50) NULL",
    "ALTER TABLE pendaftar ADD COLUMN nik_wali VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN pendidikan_wali VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN pekerjaan_wali VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN penghasilan_wali VARCHAR(50) NULL",
    "ALTER TABLE pendaftar ADD COLUMN telp_wali VARCHAR(20) NULL",
    "ALTER TABLE pendaftar ADD COLUMN alamat_wali TEXT NULL",
    "ALTER TABLE pendaftar ADD COLUMN username_siswa VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN password_siswa VARCHAR(100) NULL",
    "ALTER TABLE pendaftar ADD COLUMN kelas_awal TINYINT UNSIGNED NOT NULL DEFAULT 10",
    "ALTER TABLE pendaftar ADD COLUMN status_keluarga TINYINT UNSIGNED NOT NULL DEFAULT 1",
];
foreach ($new_cols as $sql) { try { $conn->exec($sql); } catch (PDOException $e) {} }

// Hanya meja yang ditandai sebagai meja DU (jenis_du=1)
$mejas_aktif = [];
try { $mejas_aktif = $conn->query("SELECT * FROM meja WHERE is_active=1 AND jenis_du=1 ORDER BY nomor_meja")->fetchAll(); } catch(Throwable) {}
if (empty($mejas_aktif)) {
    // Fallback: coba semua meja aktif (sebelum kolom jenis_du ada)
    try { $mejas_aktif = $conn->query("SELECT * FROM meja WHERE is_active=1 ORDER BY nomor_meja")->fetchAll(); } catch(Throwable) {}
}

// ── Export CSV (GET) ──────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'export_csv') {
    $fJur = $_GET['jurusan'] ?? ''; $fGlm = (int)($_GET['glm'] ?? 0);
    $where = ["status='terima'"]; $params = [];
    if ($fJur) { $where[] = 'jurusan=?'; $params[] = $fJur; }
    if ($fGlm) { $where[] = 'gelombang=?'; $params[] = $fGlm; }
    $st = $conn->prepare("SELECT * FROM pendaftar WHERE " . implode(' AND ', $where) . " ORDER BY jurusan, nama");
    $st->execute($params);
    $rows = $st->fetchAll();
    $fname = 'data_siswa' . ($fJur ? '_'.JURUSAN_SHORT[$fJur] : '') . ($fGlm ? '_G'.$fGlm : '') . '_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$fname.'"');
    $out = fopen('php://output','w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['NO','NISN*','NIS*','NAMA SISWA*','JENIS KELAMIN (L/P) *','USERNAME*','PASSWORD*',
        'KELAS AWAL * (gunakan nomor 1-12)','TANGGAL DI TERIMA FORMAT (YYYY-MM-DD) CONTOH (2018-07-20)',
        'SEKOLAH ASAL','TEMPAT LAHIR','TANGGAL LAHIR FORMAT (DD-MM-YYY) CONTOH (05-06-1990)',
        'AGAMA','NOMOR TELEPON','EMAIL','ANAK KE',
        'STATUS DALAM KELUARGA 1 = Anak Kandung 2 = Anak Tiri 3 = Anak Angkat',
        'ALAMAT','RT','RW','DESA/KELURAHAN','KECAMATAN','KABUPATEN/KOTA','PROVINSI','KODE POS',
        'NAMA AYAH','TANGGAL LAHIR AYAH','PENDIDIKAN AYAH','PEKERJAAN AYAH','NOMOR TELEPON AYAH','ALAMAT AYAH',
        'NAMA IBU','TANGGAL LAHIR IBU','PENDIDIKAN IBU','PEKERJAAN IBU','NOMOR TELEPON IBU','ALAMAT IBU',
        'NAMA WALI','TANGGAL LAHIR WALI','PENDIDIKAN WALI','PEKERJAAN WALI','NOMOR TELEPON WALI','ALAMAT WALI',
    ]);
    $df = fn($d) => $d ? date('d-m-Y', strtotime($d)) : '';
    $tf = fn($d) => $d ? date('Y-m-d', strtotime($d)) : date('Y-m-d');
    foreach ($rows as $i => $r) {
        fputcsv($out, [$i+1, $r['nisn'], $r['nis'], $r['nama'], $r['jenis_kelamin'],
            $r['username_siswa'] ?: $r['nisn'], $r['password_siswa'] ?: $r['nisn'],
            $r['kelas_awal'] ?: 10, $tf($r['daftar_ulang_at']),
            $r['asal_sekolah'], $r['tempat_lahir'], $df($r['tanggal_lahir']),
            $r['agama'], $r['no_telp'], $r['email'], $r['anak_ke'], $r['status_keluarga'] ?: 1,
            $r['alamat_lengkap'], $r['rt'], $r['rw'], $r['kelurahan'],
            $r['kecamatan'], $r['kabupaten'], $r['provinsi'], $r['kode_pos'],
            $r['nama_ayah'], $df($r['tgl_lahir_ayah']), $r['pendidikan_ayah'], $r['pekerjaan_ayah'], $r['telp_ayah'], $r['alamat_ayah'],
            $r['nama_ibu'],  $df($r['tgl_lahir_ibu']),  $r['pendidikan_ibu'],  $r['pekerjaan_ibu'],  $r['telp_ibu'],  $r['alamat_ibu'],
            $r['nama_wali'], $df($r['tgl_lahir_wali']), $r['pendidikan_wali'], $r['pekerjaan_wali'], $r['telp_wali'], $r['alamat_wali'],
        ]);
    }
    fclose($out); exit;
}

// ── POST Handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redir = function(string $qs = '') {
        $dash = !empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php';
        while (ob_get_level() > 0) ob_end_clean();
        header('Location: ' . $dash . '?page=daftar_ulang' . ($qs ? '&'.$qs : ''));
        exit;
    };

    if ($action === 'pilih_meja') {
        $_SESSION['du_meja_id'] = (int)$_POST['meja_id'];
        $redir();
    }
    if ($action === 'ganti_meja') {
        unset($_SESSION['du_meja_id']);
        $redir();
    }

    $du_meja_id = (int)($_SESSION['du_meja_id'] ?? 0);

    // Helper: panggil nomor DU berikutnya
    $ambilBerikutnya = function() use ($conn, $du_meja_id, $today) {
        $stmt = $conn->prepare("SELECT id FROM antrian
            WHERE tanggal=? AND jenis='daftar_ulang' AND status='menunggu'
            ORDER BY nomor ASC LIMIT 1 FOR UPDATE");
        $stmt->execute([$today]);
        $next = $stmt->fetch();
        if ($next) {
            $conn->prepare("UPDATE antrian SET meja_id=?, status='dipanggil', dipanggil_at=NOW(3) WHERE id=?")
                 ->execute([$du_meja_id, $next['id']]);
        }
        return $next;
    };

    // Simpan data siswa + selesai
    if ($action === 'selesai_du') {
        $ant_id = (int)$_POST['antrian_id'];
        $pend_id = (int)$_POST['pendaftar_id'];
        $conn->beginTransaction();
        try {
            // Simpan extended data jika ada
            if ($pend_id) {
                $fields_du = [
                    'nama','nisn','jenis_kelamin','tanggal_lahir','asal_sekolah',
                    'nis','nik','no_kk','kewarganegaraan','tahun_lulus','kip_kjp_kps',
                    'tempat_lahir','agama','email','anak_ke','alamat_lengkap',
                    'rt','rw','kelurahan','kecamatan','kabupaten','provinsi','kode_pos','no_telp',
                    'nama_ayah','nik_ayah','pendidikan_ayah','pekerjaan_ayah','penghasilan_ayah','telp_ayah','alamat_ayah',
                    'nama_ibu','nik_ibu','pendidikan_ibu','pekerjaan_ibu','penghasilan_ibu','telp_ibu','alamat_ibu',
                    'nama_wali','hubungan_wali','nik_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali','telp_wali','alamat_wali',
                    'username_siswa','password_siswa','kelas_awal','status_keluarga',
                ];
                $int_fields = ['anak_ke','status_keluarga','kelas_awal','tahun_lulus'];
                $set = ['daftar_ulang=\'proses\'','daftar_ulang_at=NOW()']; $params_du = [];
                foreach ($fields_du as $f) {
                    if (isset($_POST[$f])) {
                        $v = trim($_POST[$f]);
                        if (in_array($f, $int_fields)) {
                            $set[] = "$f=?"; $params_du[] = $v !== '' ? (int)$v : null;
                        } else {
                            $set[] = "$f=?"; $params_du[] = $v !== '' ? $v : null;
                        }
                    }
                }
                // Tanggal lahir diedit di DU → hitung ulang usia & lolos_usia agar ranking tetap konsisten
                if (!empty($_POST['tanggal_lahir'])) {
                    try {
                        $u = (int)(new DateTime($_POST['tanggal_lahir']))->diff(new DateTime())->y;
                        $set[] = 'usia=?';       $params_du[] = $u;
                        $set[] = 'lolos_usia=?'; $params_du[] = $u <= 21 ? 1 : 0;
                    } catch (Throwable $e) {}
                }
                $params_du[] = $pend_id;
                $conn->prepare("UPDATE pendaftar SET ".implode(', ',$set)." WHERE id=? AND status='terima'")
                     ->execute($params_du);
            }
            // Selesaikan antrian (status proses, bukan sudah — sudah nanti manual)
            $conn->prepare("UPDATE antrian SET status='selesai', hasil='lulus', selesai_at=NOW()
                            WHERE id=? AND meja_id=? AND status='dipanggil'")->execute([$ant_id, $du_meja_id]);
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        $redir();
    }

    // Edit data siswa yang sudah selesai daftar ulang
    if ($action === 'simpan_edit_du') {
        $pend_id = (int)$_POST['pendaftar_id'];
        if ($pend_id) {
            $fields_du = [
                'nis','nik','no_kk','kewarganegaraan','tahun_lulus','kip_kjp_kps',
                'tempat_lahir','agama','email','anak_ke','alamat_lengkap',
                'rt','rw','kelurahan','kecamatan','kabupaten','provinsi','kode_pos','no_telp',
                'nama_ayah','nik_ayah','pendidikan_ayah','pekerjaan_ayah','penghasilan_ayah','telp_ayah','alamat_ayah',
                'nama_ibu','nik_ibu','pendidikan_ibu','pekerjaan_ibu','penghasilan_ibu','telp_ibu','alamat_ibu',
                'nama_wali','hubungan_wali','nik_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali','telp_wali','alamat_wali',
            ];
            $int_fields = ['anak_ke','tahun_lulus'];
            $set = []; $params_du = [];
            foreach ($fields_du as $f) {
                if (isset($_POST[$f])) {
                    $v = trim($_POST[$f]);
                    if (in_array($f, $int_fields)) {
                        $set[] = "$f=?"; $params_du[] = $v !== '' ? (int)$v : null;
                    } else {
                        $set[] = "$f=?"; $params_du[] = $v !== '' ? $v : null;
                    }
                }
            }
            if ($set) {
                $params_du[] = $pend_id;
                try {
                    $conn->prepare("UPDATE pendaftar SET ".implode(', ',$set)." WHERE id=? AND status='terima' AND daftar_ulang IN ('proses','sudah')")
                         ->execute($params_du);
                } catch(Throwable $e) {}
            }
        }
        $redir();
    }

    if ($action === 'skip_du') {
        $ant_id = (int)$_POST['antrian_id'];
        $conn->prepare("UPDATE antrian SET status='skip', selesai_at=NOW()
                        WHERE id=? AND meja_id=? AND status='dipanggil'")->execute([$ant_id, $du_meja_id]);
        $redir();
    }

    if ($action === 'mulai_du') {
        // Panggil dulu — buat nomor DU baru tanpa siswa (siswa di-link setelah datang)
        // Nomor dihitung PER JURUSAN meja ini (bukan global) — tiap jurusan mulai dari 1.
        $mulai_jur = '';
        foreach ($mejas_aktif as $m) { if ((int)$m['id'] === $du_meja_id) { $mulai_jur = $m['jurusan_du'] ?? ''; break; } }
        $conn->beginTransaction();
        try {
            $cek = $conn->prepare("SELECT id FROM antrian WHERE tanggal=? AND jenis='daftar_ulang' AND meja_id=? AND status='dipanggil'");
            $cek->execute([$today, $du_meja_id]);
            if (!$cek->fetch()) {
                $maxN = $conn->prepare("SELECT COALESCE(MAX(nomor),0)+1 FROM antrian WHERE tanggal=? AND jenis='daftar_ulang' AND jurusan_du=?");
                $maxN->execute([$today, $mulai_jur]);
                $nomor = (int)$maxN->fetchColumn();
                $conn->prepare("INSERT INTO antrian (tanggal, jenis, jurusan_du, nomor, status, meja_id, dipanggil_at) VALUES (?, 'daftar_ulang', ?, ?, 'dipanggil', ?, NOW(3))")
                     ->execute([$today, $mulai_jur, $nomor, $du_meja_id]);
            }
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        $redir();
    }

    // Panggil dengan nomor pilihan sendiri (manual), tetap per-jurusan & sistem sama.
    // Kalau nomor SUDAH ADA (selesai/skip/menunggu) → PANGGIL ULANG tiket itu (aktifkan
    // kembali) alih-alih menolak — berguna utk memanggil nomor yang tadi terlewat/skip
    // walau antrian sudah jauh. Data siswa yang sudah tertaut tetap.
    if ($action === 'mulai_du_manual') {
        $nomor_manual = (int)($_POST['nomor_manual'] ?? 0);
        if ($nomor_manual < 1 || $nomor_manual > 99) {
            $_SESSION['flash_du'] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>Nomor harus antara 1-99.</div>';
            $redir();
        }
        $mulai_jur = '';
        foreach ($mejas_aktif as $m) { if ((int)$m['id'] === $du_meja_id) { $mulai_jur = $m['jurusan_du'] ?? ''; break; } }
        $conn->beginTransaction();
        try {
            $cek = $conn->prepare("SELECT id FROM antrian WHERE tanggal=? AND jenis='daftar_ulang' AND meja_id=? AND status='dipanggil'");
            $cek->execute([$today, $du_meja_id]);
            if ($cek->fetch()) {
                $conn->rollBack();
                $_SESSION['flash_du'] = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i>Sudah ada nomor aktif di meja ini. Selesaikan / Skip dulu.</div>';
                $redir();
            }
            // Sudah ada tiket dgn nomor ini utk jurusan ini hari ini?
            $ex = $conn->prepare("SELECT id FROM antrian WHERE tanggal=? AND jenis='daftar_ulang' AND jurusan_du=? AND nomor=? LIMIT 1");
            $ex->execute([$today, $mulai_jur, $nomor_manual]);
            $exId = (int)$ex->fetchColumn();
            if ($exId) {
                // Panggil ulang tiket lama — status kembali 'dipanggil' di meja ini
                $conn->prepare("UPDATE antrian SET status='dipanggil', meja_id=?, dipanggil_at=NOW(3) WHERE id=?")
                     ->execute([$du_meja_id, $exId]);
            } else {
                // Nomor baru — buat tiket baru
                $conn->prepare("INSERT INTO antrian (tanggal, jenis, jurusan_du, nomor, status, meja_id, dipanggil_at) VALUES (?, 'daftar_ulang', ?, ?, 'dipanggil', ?, NOW(3))")
                     ->execute([$today, $mulai_jur, $nomor_manual, $du_meja_id]);
            }
            $conn->commit();
        } catch(Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $_SESSION['flash_du'] = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i>Gagal memanggil nomor. Coba lagi.</div>';
        }
        $redir();
    }

    if ($action === 'recall_du') {
        $ant_id = (int)$_POST['antrian_id'];
        $conn->prepare("UPDATE antrian SET dipanggil_at=NOW() WHERE id=? AND meja_id=? AND status='dipanggil'")
             ->execute([$ant_id, $du_meja_id]);
        $redir();
    }

    // Tambah siswa ke antrian DU hari ini
    if ($action === 'tambah_antrian_du') {
        $pend_id = (int)($_POST['pendaftar_id'] ?? 0);
        if ($pend_id) {
            // Cek apakah sudah ada di antrian hari ini
            $cek = $conn->prepare("SELECT id FROM antrian WHERE tanggal=? AND jenis='daftar_ulang' AND pendaftar_id=?");
            $cek->execute([$today, $pend_id]);
            if (!$cek->fetch()) {
                // Nomor per jurusan siswa ini (konsisten dengan alur mulai_du)
                $pj = $conn->prepare("SELECT jurusan FROM pendaftar WHERE id=?");
                $pj->execute([$pend_id]);
                $tambah_jur = $pj->fetchColumn() ?: '';
                $maxN = $conn->prepare("SELECT COALESCE(MAX(nomor),0)+1 FROM antrian WHERE tanggal=? AND jenis='daftar_ulang' AND jurusan_du=?");
                $maxN->execute([$today, $tambah_jur]);
                $nomor = (int)$maxN->fetchColumn();
                $conn->prepare("INSERT INTO antrian (tanggal, jenis, jurusan_du, nomor, status, pendaftar_id) VALUES (?, 'daftar_ulang', ?, ?, 'menunggu', ?)")
                     ->execute([$today, $tambah_jur, $nomor, $pend_id]);
            }
        }
        $redir();
    }

    // Link/ubah pendaftar ke nomor aktif
    if ($action === 'link_du') {
        $ant_id  = (int)$_POST['antrian_id'];
        $pend_id = (int)$_POST['pendaftar_id'];
        $conn->prepare("UPDATE antrian SET pendaftar_id=? WHERE id=?")->execute([$pend_id ?: null, $ant_id]);
        $redir();
    }

    // Tandai langsung daftar_ulang='sudah' tanpa melalui antrian
    if ($action === 'tandai_du_sudah') {
        $pend_id = (int)($_POST['pendaftar_id'] ?? 0);
        if ($pend_id) {
            $conn->prepare("UPDATE pendaftar SET daftar_ulang='sudah', daftar_ulang_at=NOW() WHERE id=? AND status='terima'")
                 ->execute([$pend_id]);
        }
        $redir();
    }

    // Reset daftar_ulang ke belum
    if ($action === 'batal_du') {
        $pend_id = (int)($_POST['pendaftar_id'] ?? 0);
        if ($pend_id) {
            // Kolom daftar_ulang NOT NULL → pakai 'belum', BUKAN NULL (kunci kursi lepas)
            $conn->prepare("UPDATE pendaftar SET daftar_ulang='belum', daftar_ulang_at=NULL WHERE id=? AND status='terima'")
                 ->execute([$pend_id]);
        }
        $redir();
    }

    // Simpan data DU langsung tanpa antrian — alur seperti Data Pendaftar
    if ($action === 'simpan_du') {
        $pend_id = (int)($_POST['pendaftar_id'] ?? 0);
        if ($pend_id) {
            $fields_du = [
                'nis','nik','no_kk','kewarganegaraan','tahun_lulus','kip_kjp_kps',
                'tempat_lahir','agama','email','anak_ke','alamat_lengkap',
                'rt','rw','kelurahan','kecamatan','kabupaten','provinsi','kode_pos','no_telp',
                'nama_ayah','nik_ayah','pendidikan_ayah','pekerjaan_ayah','penghasilan_ayah','telp_ayah','alamat_ayah',
                'nama_ibu','nik_ibu','pendidikan_ibu','pekerjaan_ibu','penghasilan_ibu','telp_ibu','alamat_ibu',
                'nama_wali','hubungan_wali','nik_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali','telp_wali','alamat_wali',
            ];
            $int_fields = ['anak_ke','tahun_lulus'];
            $conn->beginTransaction();
            try {
                $cur = $conn->prepare("SELECT daftar_ulang FROM pendaftar WHERE id=? AND status='terima' LIMIT 1");
                $cur->execute([$pend_id]);
                $curRow = $cur->fetch();
                if ($curRow) {
                    $set = []; $params_du = [];
                    if ($curRow['daftar_ulang'] === 'belum') {
                        $set[] = "daftar_ulang='proses'";
                        $set[] = "daftar_ulang_at=NOW()";
                    }
                    foreach ($fields_du as $f) {
                        if (array_key_exists($f, $_POST)) {
                            $v = trim($_POST[$f]);
                            if (in_array($f, $int_fields)) {
                                $set[] = "$f=?"; $params_du[] = $v !== '' ? (int)$v : null;
                            } else {
                                $set[] = "$f=?"; $params_du[] = $v !== '' ? $v : null;
                            }
                        }
                    }
                    if ($set) {
                        $params_du[] = $pend_id;
                        $conn->prepare("UPDATE pendaftar SET ".implode(', ',$set)." WHERE id=? AND status='terima'")
                             ->execute($params_du);
                    }
                }
                $conn->commit();
            } catch(Throwable $e) { $conn->rollBack(); }
        }
        $redir();
    }
}

// Flash message (PRG) — dipakai action mulai_du_manual utk lapor error/duplikat
$du_flash_msg = '';
if (!empty($_SESSION['flash_du'])) {
    $du_flash_msg = $_SESSION['flash_du'];
    unset($_SESSION['flash_du']);
}

// ── State ─────────────────────────────────────────────────────────────────────
$du_meja_id = (int)($_SESSION['du_meja_id'] ?? 0);
$du_meja    = null;
$current    = null;
$current_p  = null;

if ($du_meja_id) {
    foreach ($mejas_aktif as $m) { if ((int)$m['id'] === $du_meja_id) { $du_meja = $m; break; } }
    if (!$du_meja) { unset($_SESSION['du_meja_id']); $du_meja_id = 0; }
}

// Statistik antrian DU hari ini
$du_total   = 0; $du_menunggu = 0; $du_selesai = 0;
try {
    $s = $conn->prepare("SELECT
        COUNT(*) AS total,
        SUM(status='menunggu') AS menunggu,
        SUM(status='selesai') AS selesai
        FROM antrian WHERE tanggal=? AND jenis='daftar_ulang'");
    $s->execute([$today]);
    $stat = $s->fetch();
    $du_total = (int)$stat['total']; $du_menunggu = (int)$stat['menunggu']; $du_selesai = (int)$stat['selesai'];
} catch(Throwable) {}

if ($du_meja_id) {
    try {
        $cs = $conn->prepare("SELECT * FROM antrian WHERE tanggal=? AND jenis='daftar_ulang' AND meja_id=? AND status='dipanggil' ORDER BY dipanggil_at DESC LIMIT 1");
        $cs->execute([$today, $du_meja_id]);
        $current = $cs->fetch() ?: null;
    } catch(Throwable) {}
    if ($current && !empty($current['pendaftar_id'])) {
        $ps = $conn->prepare("SELECT * FROM pendaftar WHERE id=? AND status='terima'");
        $ps->execute([$current['pendaftar_id']]);
        $current_p = $ps->fetch() ?: null;
    }
}

// Daftar siswa diterima — filter per jurusan meja DU jika meja sudah dipilih
$diterima_list = [];
try {
    $meja_jurusan = $du_meja['jurusan_du'] ?? null;
    if ($meja_jurusan) {
        $dl = $conn->prepare("SELECT * FROM pendaftar WHERE status='terima' AND jurusan=? ORDER BY nama");
        $dl->execute([$meja_jurusan]);
    } else {
        $dl = $conn->query("SELECT * FROM pendaftar WHERE status='terima' ORDER BY jurusan, nama");
    }
    $diterima_list = $dl->fetchAll();
} catch(Throwable) {}

// Summary daftar ulang
$du_summary = ['total'=>0,'sudah'=>0,'proses'=>0,'belum'=>0];
try {
    $su = $conn->query("SELECT COUNT(*) AS total, SUM(daftar_ulang='sudah') AS sudah, SUM(daftar_ulang='proses') AS proses FROM pendaftar WHERE status='terima'");
    $r = $su->fetch();
    $du_summary['total']  = (int)$r['total'];
    $du_summary['sudah']  = (int)$r['sudah'];
    $du_summary['proses'] = (int)$r['proses'];
    $du_summary['belum']  = $du_summary['total'] - $du_summary['sudah'] - $du_summary['proses'];
} catch(Throwable) {}

// Helper: format nomor DU → "DU001TKJ"
$fmtDuNomor = function(int $nomor, ?string $jurusan = null): string {
    $kode = JURUSAN_SHORT[$jurusan ?? ''] ?? ($jurusan ? strtoupper(substr($jurusan,0,3)) : '');
    return 'DU' . str_pad($nomor, 3, '0', STR_PAD_LEFT) . $kode;
};

$pend_opts = ['SD','SMP','SMA/SMK','D1','D2','D3','S1','S2','S3','Tidak Sekolah','Lainnya'];
$agama_opts = ['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu','Lainnya'];
?>

<style>
.du-meja-card {
    border: 2px solid #e5e7eb; border-radius: 16px; padding: 22px 18px;
    text-align: center; cursor: pointer; transition: all .2s; background: #fff; width: 100%;
}
.du-meja-card:hover { border-color: #059669; box-shadow: 0 8px 24px rgba(5,150,105,.12); transform: translateY(-3px); }
.du-meja-card .mn { font-size: 2rem; font-weight: 800; color: #059669; }
.du-nomor-big { font-size: 6rem; font-weight: 900; line-height: 1; letter-spacing: -4px; color: #059669; }
.du-nomor-box { background: #ecfdf5; border: 3px solid #6ee7b7; border-radius: 24px; padding: 24px 40px; display: inline-block; }
.btn-du-main { border: 0; border-radius: 14px; padding: 14px 28px; font-size: 1rem; font-weight: 700; transition: all .2s; color: #fff; box-shadow: 0 6px 20px rgba(0,0,0,.12); }
.btn-du-main:hover { transform: translateY(-2px); color: #fff; }
.btn-du-selesai { background: linear-gradient(135deg,#059669,#10b981); }
.btn-du-skip    { background: linear-gradient(135deg,#d97706,#f59e0b); }
.btn-du-start   { background: linear-gradient(135deg,#059669,#34d399); }
.du-stat-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 40px; font-weight: 600; font-size: .82rem; }
</style>

<?= $du_flash_msg ?>

<!-- Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-check-fill me-2" style="color:#059669;"></i>Sesi Daftar Ulang</h4>
        <div class="d-flex flex-wrap gap-2 mt-1">
            <span class="du-stat-pill" style="background:#dcfce7;color:#166534;">
                <i class="bi bi-people-fill"></i><?= $du_summary['total'] ?> Diterima
            </span>
            <span class="du-stat-pill" style="background:#d1fae5;color:#065f46;">
                <i class="bi bi-check-circle-fill"></i><?= $du_summary['sudah'] ?> Sudah DU
            </span>
            <span class="du-stat-pill" style="background:#fef9c3;color:#854d0e;">
                <i class="bi bi-hourglass-split"></i><?= $du_summary['proses'] ?> Proses
            </span>
            <span class="du-stat-pill" style="background:#fee2e2;color:#991b1b;">
                <i class="bi bi-clock"></i><?= $du_summary['belum'] ?> Belum DU
            </span>
            <span class="du-stat-pill" style="background:#f0fdf4;color:#166534;">
                <i class="bi bi-list-ol"></i>Antrian hari ini: <?= $du_total ?> (<?= $du_menunggu ?> menunggu)
            </span>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="cetakFormulir()">
            <i class="bi bi-printer me-1"></i>Cetak Formulir
        </button>
        <a href="<?= !empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php' ?>?page=daftar_ulang&action=export_csv" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV Semua
        </a>
        <?php if ($du_meja_id): ?>
        <form method="POST" class="d-inline">
            <input type="hidden" name="action" value="ganti_meja">
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Ganti Meja
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$du_meja_id): ?>
<!-- ══ PILIH MEJA ══════════════════════════════════════════════════════════════ -->
<div class="text-center mb-4">
    <h5 class="fw-bold text-muted">Pilih meja Daftar Ulang untuk memulai sesi</h5>
    <p class="text-muted small">Setiap meja melayani satu jurusan. Konfigurasikan meja DU di halaman Kelola Meja.</p>
</div>
<?php if (empty($mejas_aktif)): ?>
<div class="alert alert-warning text-center">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Belum ada meja Daftar Ulang yang dikonfigurasi. Buka <strong>Kelola Meja</strong> dan atur meja DU per jurusan.
</div>
<?php else: ?>
<div class="row g-3 mb-5">
    <?php foreach ($mejas_aktif as $m):
        $kodeJur = JURUSAN_SHORT[$m['jurusan_du'] ?? ''] ?? ($m['jurusan_du'] ? strtoupper(substr($m['jurusan_du'],0,3)) : '');
    ?>
    <div class="col-6 col-md-3">
        <form method="POST">
            <input type="hidden" name="action" value="pilih_meja">
            <input type="hidden" name="meja_id" value="<?= $m['id'] ?>">
            <button class="du-meja-card" type="submit">
                <i class="bi bi-person-workspace d-block mb-2" style="font-size:1.8rem;color:#059669;"></i>
                <div class="mn">Meja <?= $m['nomor_meja'] ?></div>
                <?php if ($m['nama']): ?><div class="text-muted small"><?= htmlspecialchars($m['nama']) ?></div><?php endif; ?>
                <?php if ($kodeJur): ?>
                <div class="mt-2"><span class="badge bg-success"><?= htmlspecialchars($kodeJur) ?></span></div>
                <?php endif; ?>
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ══ LAYOUT LANGSUNG: TABEL + FORM (seperti Data Pendaftar) ════════════════ -->
<?php
// Query ulang tanpa filter meja agar tabel selalu tampil semua siswa
$all_terima = [];
try {
    $atStmt = $conn->query("SELECT id,no_pendaftaran,nama,nisn,jurusan,gelombang,daftar_ulang,daftar_ulang_at FROM pendaftar WHERE status='terima' AND is_undur_diri=0 ORDER BY jurusan,nama");
    $all_terima = $atStmt->fetchAll();
} catch(Throwable) {}
$sudah_count = count(array_filter($all_terima, fn($r) => ($r['daftar_ulang'] ?? '') === 'sudah'));
$belum_count = count($all_terima) - $sudah_count;
?>
<div class="row g-3 align-items-start">

<!-- Kolom kiri: Tabel siswa -->
<div class="col-lg-7">
<div class="card shadow-sm" id="tabelDiterimaDU">
    <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2 py-2">
        <div class="fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-people-fill text-success"></i>
            Daftar Siswa Diterima
            <span class="badge bg-success"><?= count($all_terima) ?></span>
            <span class="badge bg-success-subtle text-success border border-success-subtle"><?= $sudah_count ?> Sudah DU</span>
            <?php if ($belum_count > 0): ?>
            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><?= $belum_count ?> Belum DU</span>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" id="cariTabelDU" class="form-control form-control-sm" placeholder="Cari nama / NISN..." style="width:180px;">
            <div class="btn-group btn-group-sm" id="filterJurTabelDU">
                <button class="btn btn-outline-secondary active" data-jur="">Semua</button>
                <?php foreach (JURUSAN_SHORT as $jFull => $jShort): ?>
                <button class="btn btn-outline-secondary" data-jur="<?= htmlspecialchars($jFull) ?>"><?= $jShort ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle" id="tableDU">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width:36px;">#</th>
                    <th>Nama Siswa</th>
                    <th class="text-center">Jur</th>
                    <th class="text-center" style="width:90px;">Status DU</th>
                    <th class="text-center" style="width:80px;">Tandai DU</th>
                    <th class="text-center" style="width:50px;">Buka</th>
                </tr>
            </thead>
            <tbody id="tbodyDU">
            <?php foreach ($all_terima as $i => $s):
                $duSt = $s['daftar_ulang'] ?? 'belum';
            ?>
            <tr class="du-tabel-row" data-jur="<?= htmlspecialchars($s['jurusan']) ?>"
                data-search="<?= strtolower(htmlspecialchars($s['nama'].' '.$s['nisn'].' '.$s['no_pendaftaran'])) ?>"
                data-id="<?= $s['id'] ?>">
                <td class="text-center text-muted small"><?= $i + 1 ?></td>
                <td>
                    <div class="fw-semibold small"><?= htmlspecialchars($s['nama']) ?></div>
                    <div class="text-muted" style="font-size:.68rem;"><?= htmlspecialchars($s['no_pendaftaran']) ?></div>
                </td>
                <td class="text-center"><span class="badge bg-secondary"><?= JURUSAN_SHORT[$s['jurusan']] ?? '' ?></span></td>
                <td class="text-center">
                    <?php if ($duSt === 'sudah'): ?>
                    <span class="badge bg-success" style="font-size:.62rem;"><i class="bi bi-check-circle-fill"></i> Sudah</span>
                    <?php elseif ($duSt === 'proses'): ?>
                    <span class="badge bg-warning text-dark" style="font-size:.62rem;"><i class="bi bi-hourglass-split"></i> Proses</span>
                    <?php else: ?>
                    <span class="badge bg-light text-secondary border" style="font-size:.62rem;">Belum</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($duSt === 'sudah'): ?>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Reset status DU siswa ini?')">
                        <input type="hidden" name="action" value="batal_du">
                        <input type="hidden" name="pendaftar_id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:.72rem;" title="Reset DU">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="tandai_du_sudah">
                        <input type="hidden" name="pendaftar_id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-xs btn-success py-0 px-1" style="font-size:.72rem;" title="Tandai Sudah DU">
                            <i class="bi bi-check-lg"></i> DU
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size:.72rem;" title="Buka form data DU"
                            onclick="openDuPanel(<?= $s['id'] ?>)">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($all_terima)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox me-2"></i>Belum ada siswa diterima.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div><!-- /col-lg-7 -->

<!-- Kolom kanan: Form DU langsung -->
<div class="col-lg-5" style="position:sticky;top:74px;">

<datalist id="pekerjaan_list2">
    <option value="PNS/ASN"><option value="TNI"><option value="POLRI">
    <option value="Pegawai Swasta"><option value="Wiraswasta"><option value="Pedagang">
    <option value="Buruh/Karyawan"><option value="Petani"><option value="Nelayan">
    <option value="Pensiunan"><option value="Ibu Rumah Tangga"><option value="Tidak Bekerja">
    <option value="Guru/Dosen"><option value="Driver/Ojek Online"><option value="Supir">
    <option value="Karyawan BUMN"><option value="Tenaga Kesehatan"><option value="Lainnya">
</datalist>
<datalist id="penghasilan_list2">
    <option value="Rp 500.000"><option value="Rp 1.000.000"><option value="Rp 1.500.000">
    <option value="Rp 2.000.000"><option value="Rp 2.500.000"><option value="Rp 3.000.000">
    <option value="Rp 4.000.000"><option value="Rp 5.000.000"><option value="Rp 7.500.000">
    <option value="Rp 10.000.000"><option value="Tidak ada penghasilan"><option value="Lainnya">
</datalist>

<div id="duPanelPlaceholder" class="card shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-person-lines-fill d-block mb-2" style="font-size:2.5rem;opacity:.3;"></i>
        <div class="small fw-semibold mb-1">Pilih siswa dari tabel</div>
        <div class="small">Klik <i class="bi bi-pencil-square"></i> untuk isi data daftar ulang</div>
    </div>
</div>

<div id="duPanelForm" class="card shadow-sm" style="display:none;">
    <div class="card-header d-flex align-items-center justify-content-between py-2"
         style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;">
        <div>
            <div class="fw-bold" id="dpf_nama">—</div>
            <div style="font-size:.75rem;opacity:.85;" id="dpf_sub">—</div>
        </div>
        <button type="button" class="btn btn-sm btn-light py-0 px-2 opacity-75" onclick="closeDuPanel()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <form method="POST" id="formDuLangsung" novalidate>
        <input type="hidden" name="action" value="simpan_du">
        <input type="hidden" name="pendaftar_id" id="dpf_id">
        <ul class="nav nav-tabs nav-fill px-3 pt-2 border-bottom-0 bg-light small" id="dpfTabs">
            <li class="nav-item"><a class="nav-link active py-1" href="#dpf_tab1" data-bs-toggle="tab">A. Data Siswa</a></li>
            <li class="nav-item"><a class="nav-link py-1" href="#dpf_tab2" data-bs-toggle="tab">B. Orang Tua/Wali</a></li>
        </ul>
        <div class="tab-content p-3" style="overflow-y:auto;max-height:calc(100vh - 330px);">
          <div class="tab-pane fade show active" id="dpf_tab1">
            <div class="row g-2">
                <div class="col-12"><label class="form-label mb-0 small">Nama Lengkap</label>
                    <input class="form-control form-control-sm" id="dpf_namarv" readonly style="background:#f0fdf4;"></div>
                <div class="col-5"><label class="form-label mb-0 small">NISN</label>
                    <input class="form-control form-control-sm" id="dpf_nisnrv" readonly style="background:#f0fdf4;"></div>
                <div class="col-7"><label class="form-label mb-0 small">NIS <span class="fw-normal text-muted">(opsional)</span></label>
                    <input type="text" name="nis" id="dpf_nis" class="form-control form-control-sm" placeholder="Dari sekolah"></div>
                <div class="col-4"><label class="form-label mb-0 small">NIK Siswa</label>
                    <input type="text" name="nik" id="dpf_nik" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. KK</label>
                    <input type="text" name="no_kk" id="dpf_no_kk" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kewarganegaraan</label>
                    <input type="text" name="kewarganegaraan" id="dpf_kewarganegaraan" class="form-control form-control-sm"></div>
                <div class="col-4"><label class="form-label mb-0 small">Agama</label>
                    <select name="agama" id="dpf_agama" class="form-select form-select-sm">
                        <option value="">— Pilih —</option>
                        <?php foreach ($agama_opts as $ag): ?><option value="<?=$ag?>"><?=$ag?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-4"><label class="form-label mb-0 small">Anak Ke-</label>
                    <input type="number" name="anak_ke" id="dpf_anak_ke" class="form-control form-control-sm" min="1" max="20"></div>
                <div class="col-4"><label class="form-label mb-0 small">Tahun Lulus SMP</label>
                    <input type="number" name="tahun_lulus" id="dpf_tahun_lulus" class="form-control form-control-sm" min="2000" max="2030" placeholder="2025"></div>
                <div class="col-6"><label class="form-label mb-0 small">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" id="dpf_tempat_lahir" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label mb-0 small">Email</label>
                    <input type="email" name="email" id="dpf_email" class="form-control form-control-sm"></div>
                <div class="col-12"><label class="form-label mb-0 small">Alamat Lengkap</label>
                    <textarea name="alamat_lengkap" id="dpf_alamat_lengkap" class="form-control form-control-sm" rows="2"></textarea></div>
                <div class="col-2"><label class="form-label mb-0 small">RT</label>
                    <input type="text" name="rt" id="dpf_rt" class="form-control form-control-sm" maxlength="5" inputmode="numeric"></div>
                <div class="col-2"><label class="form-label mb-0 small">RW</label>
                    <input type="text" name="rw" id="dpf_rw" class="form-control form-control-sm" maxlength="5" inputmode="numeric"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kecamatan</label>
                    <input type="text" name="kecamatan" id="dpf_kecamatan" class="form-control form-control-sm"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kabupaten/Kota</label>
                    <input type="text" name="kabupaten" id="dpf_kabupaten" class="form-control form-control-sm"></div>
                <div class="col-5"><label class="form-label mb-0 small">Provinsi</label>
                    <input type="text" name="provinsi" id="dpf_provinsi" class="form-control form-control-sm"></div>
                <div class="col-3"><label class="form-label mb-0 small">Kode Pos</label>
                    <input type="text" name="kode_pos" id="dpf_kode_pos" class="form-control form-control-sm" maxlength="10" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">KIP/KJP/KPS</label>
                    <input type="text" name="kip_kjp_kps" id="dpf_kip_kjp_kps" class="form-control form-control-sm"></div>
            </div>
          </div>
          <div class="tab-pane fade" id="dpf_tab2">
            <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px;">1. Ayah Kandung</div>
            <div class="row g-2 mb-3">
                <div class="col-7"><label class="form-label mb-0 small">Nama Ayah</label>
                    <input type="text" name="nama_ayah" id="dpf_nama_ayah" class="form-control form-control-sm"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Ayah</label>
                    <input type="text" name="nik_ayah" id="dpf_nik_ayah" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan</label>
                    <select name="pendidikan_ayah" id="dpf_pendidikan_ayah" class="form-select form-select-sm"><option value="">—</option><?php foreach($pend_opts as $po): ?><option value="<?=$po?>"><?=$po?></option><?php endforeach;?></select></div>
                <div class="col-4"><label class="form-label mb-0 small">Pekerjaan</label>
                    <input type="text" name="pekerjaan_ayah" id="dpf_pekerjaan_ayah" class="form-control form-control-sm" list="pekerjaan_list2"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label>
                    <input type="text" name="penghasilan_ayah" id="dpf_penghasilan_ayah" class="form-control form-control-sm" list="penghasilan_list2"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. HP</label>
                    <input type="text" name="telp_ayah" id="dpf_telp_ayah" class="form-control form-control-sm" inputmode="tel"></div>
                <div class="col-8"><label class="form-label mb-0 small">Alamat Ayah</label>
                    <input type="text" name="alamat_ayah" id="dpf_alamat_ayah" class="form-control form-control-sm"></div>
            </div>
            <hr class="my-2">
            <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px;">2. Ibu Kandung</div>
            <div class="row g-2 mb-3">
                <div class="col-7"><label class="form-label mb-0 small">Nama Ibu</label>
                    <input type="text" name="nama_ibu" id="dpf_nama_ibu" class="form-control form-control-sm"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Ibu</label>
                    <input type="text" name="nik_ibu" id="dpf_nik_ibu" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan</label>
                    <select name="pendidikan_ibu" id="dpf_pendidikan_ibu" class="form-select form-select-sm"><option value="">—</option><?php foreach($pend_opts as $po): ?><option value="<?=$po?>"><?=$po?></option><?php endforeach;?></select></div>
                <div class="col-4"><label class="form-label mb-0 small">Pekerjaan</label>
                    <input type="text" name="pekerjaan_ibu" id="dpf_pekerjaan_ibu" class="form-control form-control-sm" list="pekerjaan_list2"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label>
                    <input type="text" name="penghasilan_ibu" id="dpf_penghasilan_ibu" class="form-control form-control-sm" list="penghasilan_list2"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. HP</label>
                    <input type="text" name="telp_ibu" id="dpf_telp_ibu" class="form-control form-control-sm" inputmode="tel"></div>
                <div class="col-8"><label class="form-label mb-0 small">Alamat Ibu</label>
                    <input type="text" name="alamat_ibu" id="dpf_alamat_ibu" class="form-control form-control-sm"></div>
            </div>
            <hr class="my-2">
            <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px;">3. Wali <span class="fw-normal">(jika orang tua tidak ada)</span></div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label mb-0 small">Nama Wali</label>
                    <input type="text" name="nama_wali" id="dpf_nama_wali" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label mb-0 small">Hubungan</label>
                    <input type="text" name="hubungan_wali" id="dpf_hubungan_wali" class="form-control form-control-sm"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Wali</label>
                    <input type="text" name="nik_wali" id="dpf_nik_wali" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan</label>
                    <select name="pendidikan_wali" id="dpf_pendidikan_wali" class="form-select form-select-sm"><option value="">—</option><?php foreach($pend_opts as $po): ?><option value="<?=$po?>"><?=$po?></option><?php endforeach;?></select></div>
                <div class="col-3"><label class="form-label mb-0 small">No. HP</label>
                    <input type="text" name="telp_wali" id="dpf_telp_wali" class="form-control form-control-sm" inputmode="tel"></div>
                <div class="col-5"><label class="form-label mb-0 small">Pekerjaan</label>
                    <input type="text" name="pekerjaan_wali" id="dpf_pekerjaan_wali" class="form-control form-control-sm" list="pekerjaan_list2"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label>
                    <input type="text" name="penghasilan_wali" id="dpf_penghasilan_wali" class="form-control form-control-sm" list="penghasilan_list2"></div>
                <div class="col-12"><label class="form-label mb-0 small">Alamat Wali</label>
                    <input type="text" name="alamat_wali" id="dpf_alamat_wali" class="form-control form-control-sm"></div>
            </div>
          </div>
        </div>
        <div class="border-top p-3 d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="dpf_cetak_btn">
                <i class="bi bi-printer me-1"></i>Cetak
            </button>
            <button type="submit" class="btn btn-success btn-sm flex-grow-1" id="dpf_submit">
                <i class="bi bi-check-circle me-1"></i><span id="dpf_submit_label">Simpan (→ Proses)</span>
            </button>
            <button type="button" class="btn btn-outline-success btn-sm" id="dpf_tandai_btn" style="display:none;"
                    onclick="if(confirm('Tandai siswa ini sudah Daftar Ulang?')) document.getElementById('dpf_form_tandai').submit()">
                <i class="bi bi-check-all me-1"></i>Sudah DU
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="dpf_batal_btn" style="display:none;"
                    onclick="if(confirm('Reset status daftar ulang siswa ini ke Belum?')) document.getElementById('dpf_form_batal').submit()">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </div>
    </form>
    <form method="POST" id="dpf_form_tandai" style="display:none;">
        <input type="hidden" name="action" value="tandai_du_sudah">
        <input type="hidden" name="pendaftar_id" id="dpf_form_tandai_id">
    </form>
    <form method="POST" id="dpf_form_batal" style="display:none;">
        <input type="hidden" name="action" value="batal_du">
        <input type="hidden" name="pendaftar_id" id="dpf_form_batal_id">
    </form>
</div>

</div><!-- /col-lg-5 -->
</div><!-- /row -->

<script>
(function(){
    const search  = document.getElementById('cariTabelDU');
    const filterG = document.getElementById('filterJurTabelDU');
    let activeJur = '';
    function applyFilter(){
        const q = (search?.value||'').toLowerCase().trim();
        document.querySelectorAll('.du-tabel-row').forEach(tr=>{
            const matchJur  = !activeJur || tr.dataset.jur === activeJur;
            const matchText = !q || (tr.dataset.search||'').includes(q);
            tr.style.display = (matchJur && matchText) ? '' : 'none';
        });
    }
    search?.addEventListener('input', applyFilter);
    filterG?.querySelectorAll('button').forEach(btn=>{
        btn.addEventListener('click',()=>{
            filterG.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
            activeJur = btn.dataset.jur;
            applyFilter();
        });
    });
})();
</script>

<?php else: ?>
<!-- ══ TAMPILAN MEJA ═══════════════════════════════════════════════════════════ -->
<div class="row g-3">

<!-- Kolom kiri: nomor & aksi -->
<div class="col-lg-6">

<?php
$du_jurusan_meja = $du_meja['jurusan_du'] ?? null;
$du_kode_meja    = JURUSAN_SHORT[$du_jurusan_meja ?? ''] ?? '';
?>
<?php if ($current): ?>
<!-- Sedang melayani -->
<div class="text-center mb-3">
    <div class="small fw-bold text-uppercase mb-1" style="letter-spacing:.8px;color:#059669;">Sedang Dilayani</div>
    <div class="du-nomor-box">
        <div class="du-nomor-big"><?= $fmtDuNomor($current['nomor'], $du_jurusan_meja) ?></div>
    </div>
    <?php if ($current_p): ?>
    <div class="fw-bold mt-2"><?= htmlspecialchars($current_p['nama']) ?></div>
    <div class="text-muted small"><?= htmlspecialchars($current_p['no_pendaftaran']) ?> &middot; <?= JURUSAN_SHORT[$current_p['jurusan']] ?? $current_p['jurusan'] ?></div>
    <?php else: ?>
    <div class="text-warning small mt-2 fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Belum terhubung ke siswa</div>
    <div class="text-muted small">Cari nama siswa di daftar bawah lalu klik <strong>Link</strong></div>
    <?php endif; ?>
</div>

<!-- Tombol aksi utama -->
<div class="d-flex gap-2 justify-content-center flex-wrap mb-3">
    <form method="POST">
        <input type="hidden" name="action" value="recall_du">
        <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
        <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-megaphone me-1"></i>Panggil Ulang</button>
    </form>
    <form method="POST" onsubmit="return confirm('Skip nomor <?= $fmtDuNomor($current['nomor'], $du_jurusan_meja) ?>? (Tidak hadir)')">
        <input type="hidden" name="action" value="skip_du">
        <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
        <button class="btn-du-main btn-du-skip btn"><i class="bi bi-skip-forward me-1"></i>Skip</button>
    </form>
</div>

<?php else: ?>
<!-- Belum ada nomor aktif -->
<div class="text-center mb-4 py-4">
    <div class="du-nomor-box" style="opacity:.35;">
        <div class="du-nomor-big">—</div>
    </div>
    <form method="POST" class="mt-3">
        <input type="hidden" name="action" value="mulai_du">
        <button class="btn-du-main btn-du-start btn px-5"><i class="bi bi-megaphone me-1"></i>Panggil</button>
    </form>
    <div class="d-flex align-items-center justify-content-center gap-2 mt-3">
        <div class="text-muted small">atau</div>
    </div>
    <form method="POST" class="d-flex justify-content-center align-items-center gap-2 mt-2">
        <input type="hidden" name="action" value="mulai_du_manual">
        <input type="number" name="nomor_manual" class="form-control form-control-sm text-center"
               style="width:80px;" min="1" max="99" placeholder="1-99" required>
        <button class="btn btn-outline-success btn-sm"><i class="bi bi-hash me-1"></i>Panggil Nomor Ini</button>
    </form>
    <div class="mt-2 text-muted small">Siswa datang → klik <strong>Link</strong> di daftar bawah</div>
</div>
<?php endif; ?>


<!-- Daftar siswa DU -->
<div class="card">
    <div class="card-header small fw-semibold">
        <i class="bi bi-people me-1"></i>Daftar Siswa
        <?php if ($current && !$current['pendaftar_id']): ?>
        <span class="badge bg-warning text-dark ms-2" style="font-size:.65rem;"><i class="bi bi-link-45deg me-1"></i>Pilih siswa → Link</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-2">
        <div class="d-flex gap-1 flex-wrap mb-2" id="filterJurDU">
            <button class="btn btn-xs btn-outline-secondary py-0 px-2 du-jur-btn active" data-jur="" style="font-size:.72rem;">Semua</button>
            <?php foreach (JURUSAN_SHORT as $jFull => $jShort): ?>
            <button class="btn btn-xs btn-outline-secondary py-0 px-2 du-jur-btn" data-jur="<?= htmlspecialchars($jFull) ?>" style="font-size:.72rem;"><?= $jShort ?></button>
            <?php endforeach; ?>
        </div>
        <input type="text" class="form-control form-control-sm mb-2" id="searchDU" placeholder="Cari nama / NISN / No. Daftar...">
        <div style="max-height:300px;overflow-y:auto;" id="listDU">
        <?php
        $canLink = $current && empty($current['pendaftar_id']);
        foreach ($diterima_list as $s):
            $duStatus = $s['daftar_ulang'] ?? 'belum';
        ?>
        <div class="du-search-item d-flex align-items-center gap-2 p-2 border-bottom"
             data-search="<?= strtolower(htmlspecialchars($s['nama'].' '.$s['nisn'].' '.$s['no_pendaftaran'])) ?>"
             data-jurusan="<?= htmlspecialchars($s['jurusan']) ?>">
            <div class="flex-grow-1">
                <div class="small fw-semibold"><?= htmlspecialchars($s['nama']) ?></div>
                <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($s['no_pendaftaran']) ?> &middot; <?= JURUSAN_SHORT[$s['jurusan']] ?? '' ?></div>
            </div>
            <?php if ($duStatus === 'sudah'): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.62rem;">Sudah DU</span>
            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size:.72rem;" title="Edit data DU"
                onclick="showEditPanel(<?= $s['id'] ?>)">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-xs btn-outline-success py-0 px-1" style="font-size:.72rem;" title="Cetak SPTJM + Formulir"
                onclick="cetakSPTJM(DU_DATA[<?= $s['id'] ?>])">
                <i class="bi bi-printer"></i>
            </button>
            <form method="POST" class="d-inline" onsubmit="return confirm('Reset status daftar ulang siswa ini?')">
                <input type="hidden" name="action" value="batal_du">
                <input type="hidden" name="pendaftar_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:.72rem;" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </form>
            <?php elseif ($duStatus === 'proses'): ?>
            <span class="badge" style="background:#fef9c3;color:#854d0e;font-size:.62rem;">Proses</span>
            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size:.72rem;" title="Edit data DU"
                onclick="showEditPanel(<?= $s['id'] ?>)">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button" class="btn btn-xs btn-outline-success py-0 px-1" style="font-size:.72rem;" title="Cetak SPTJM + Formulir"
                onclick="cetakSPTJM(DU_DATA[<?= $s['id'] ?>])">
                <i class="bi bi-printer"></i>
            </button>
            <form method="POST" class="d-inline" onsubmit="return confirm('Reset status daftar ulang siswa ini?')">
                <input type="hidden" name="action" value="batal_du">
                <input type="hidden" name="pendaftar_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:.72rem;" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </form>
            <?php elseif ($canLink): ?>
            <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="link_du">
                <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
                <input type="hidden" name="pendaftar_id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-xs btn-warning py-0 px-2" style="font-size:.72rem;" title="Link ke nomor aktif">
                    <i class="bi bi-link-45deg me-1"></i>Link
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

</div><!-- /col kiri -->

<!-- Datalists shared -->
<datalist id="pekerjaan_list">
    <option value="PNS/ASN"><option value="TNI"><option value="POLRI">
    <option value="Pegawai Swasta"><option value="Wiraswasta"><option value="Pedagang">
    <option value="Buruh/Karyawan"><option value="Petani"><option value="Nelayan">
    <option value="Pensiunan"><option value="Ibu Rumah Tangga"><option value="Tidak Bekerja">
    <option value="Guru/Dosen"><option value="Driver/Ojek Online"><option value="Supir">
    <option value="Karyawan BUMN"><option value="Tenaga Kesehatan"><option value="Lainnya">
</datalist>
<datalist id="penghasilan_list">
    <option value="Rp 500.000"><option value="Rp 1.000.000"><option value="Rp 1.500.000">
    <option value="Rp 2.000.000"><option value="Rp 2.500.000"><option value="Rp 3.000.000">
    <option value="Rp 4.000.000"><option value="Rp 5.000.000"><option value="Rp 7.500.000">
    <option value="Rp 10.000.000"><option value="Tidak ada penghasilan"><option value="Lainnya">
</datalist>

<!-- Kolom kanan: form data siswa -->
<div class="col-lg-6">
<?php if ($current && $current_p): ?>
<div class="card h-100">
    <div class="card-header" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff;">
        <div class="fw-bold"><i class="bi bi-person-vcard me-2"></i><?= htmlspecialchars($current_p['nama']) ?></div>
        <div style="font-size:.75rem;opacity:.85;"><?= htmlspecialchars($current_p['no_pendaftaran']) ?> &middot; NISN: <?= htmlspecialchars($current_p['nisn'] ?? '—') ?></div>
    </div>
    <div class="card-body p-0">
    <form method="POST" id="formDU" novalidate>
        <input type="hidden" name="action" value="selesai_du">
        <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
        <input type="hidden" name="pendaftar_id" value="<?= $current_p['id'] ?>">

        <!-- Nav Tabs -->
        <ul class="nav nav-tabs nav-fill px-3 pt-2 border-bottom-0 bg-light small" id="duTabs">
            <li class="nav-item"><a class="nav-link active py-1" href="#du1" data-bs-toggle="tab">A. Data Siswa</a></li>
            <li class="nav-item"><a class="nav-link py-1" href="#du2" data-bs-toggle="tab">B. Orang Tua/Wali</a></li>
        </ul>
        <div class="tab-content p-3">
          <?php $p = $current_p; ?>

          <!-- Tab A: Data Peserta Didik -->
          <div class="tab-pane fade show active" id="du1">
            <div class="row g-2">
                <!-- Data readonly dari SPMB -->
                <div class="col-6"><label class="form-label mb-0 small">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control form-control-sm text-uppercase" value="<?= htmlspecialchars($p['nama']??'') ?>" oninput="this.value=this.value.toUpperCase()"></div>
                <div class="col-3"><label class="form-label mb-0 small">NISN</label>
                    <input type="text" name="nisn" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nisn']??'') ?>" maxlength="20"></div>
                <div class="col-3"><label class="form-label mb-0 small">NIS <span class="fw-normal text-muted">(opsional)</span></label>
                    <input type="text" name="nis" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nis']??'') ?>" placeholder="Dari sekolah"></div>

                <div class="col-4"><label class="form-label mb-0 small">NIK Siswa</label>
                    <input type="text" name="nik" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nik']??'') ?>" maxlength="16" placeholder="16 digit" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. Kartu Keluarga</label>
                    <input type="text" name="no_kk" class="form-control form-control-sm" value="<?= htmlspecialchars($p['no_kk']??'') ?>" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kewarganegaraan</label>
                    <input type="text" name="kewarganegaraan" class="form-control form-control-sm" value="<?= htmlspecialchars($p['kewarganegaraan'] ?: 'WNI') ?>"></div>

                <div class="col-4"><label class="form-label mb-0 small">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select form-select-sm">
                        <option value="L" <?= ($p['jenis_kelamin']??'')==='L'?'selected':'' ?>>Laki-Laki</option>
                        <option value="P" <?= ($p['jenis_kelamin']??'')==='P'?'selected':'' ?>>Perempuan</option>
                    </select></div>
                <div class="col-4"><label class="form-label mb-0 small">Agama</label>
                    <select name="agama" class="form-select form-select-sm">
                        <option value="">— Pilih —</option>
                        <?php foreach ($agama_opts as $ag): ?><option value="<?= $ag ?>" <?= ($p['agama']??'')===$ag?'selected':'' ?>><?= $ag ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-4"><label class="form-label mb-0 small">Anak Ke-</label>
                    <input type="number" name="anak_ke" class="form-control form-control-sm" value="<?= htmlspecialchars($p['anak_ke']??'') ?>" min="1" max="20"></div>

                <div class="col-5"><label class="form-label mb-0 small">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" class="form-control form-control-sm" value="<?= htmlspecialchars($p['tempat_lahir']??'') ?>"></div>
                <div class="col-4"><label class="form-label mb-0 small">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="form-control form-control-sm" value="<?= htmlspecialchars($p['tanggal_lahir']??'') ?>"></div>
                <div class="col-3"><label class="form-label mb-0 small">Tahun Lulus SMP</label>
                    <input type="number" name="tahun_lulus" class="form-control form-control-sm" value="<?= htmlspecialchars($p['tahun_lulus']??'') ?>" min="2000" max="2030" placeholder="2025"></div>

                <div class="col-12"><label class="form-label mb-0 small">Alamat Lengkap</label>
                    <textarea name="alamat_lengkap" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($p['alamat_lengkap'] ?: ($p['alamat']??'')) ?></textarea></div>
                <div class="col-2"><label class="form-label mb-0 small">RT</label><input type="text" name="rt" class="form-control form-control-sm" value="<?= htmlspecialchars($p['rt']??'') ?>" maxlength="5" inputmode="numeric"></div>
                <div class="col-2"><label class="form-label mb-0 small">RW</label><input type="text" name="rw" class="form-control form-control-sm" value="<?= htmlspecialchars($p['rw']??'') ?>" maxlength="5" inputmode="numeric"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kelurahan</label>
                    <input type="text" name="kelurahan" class="form-control form-control-sm" value="<?= htmlspecialchars($p['kelurahan']??'') ?>"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kecamatan</label>
                    <input type="text" name="kecamatan" class="form-control form-control-sm" value="<?= htmlspecialchars($p['kecamatan']??'') ?>"></div>
                <div class="col-5"><label class="form-label mb-0 small">Kabupaten/Kota</label>
                    <input type="text" name="kabupaten" class="form-control form-control-sm" value="<?= htmlspecialchars($p['kabupaten']??'') ?>"></div>
                <div class="col-4"><label class="form-label mb-0 small">Provinsi</label>
                    <input type="text" name="provinsi" class="form-control form-control-sm" value="<?= htmlspecialchars($p['provinsi'] ?: 'DKI Jakarta') ?>"></div>
                <div class="col-3"><label class="form-label mb-0 small">Kode Pos</label>
                    <input type="text" name="kode_pos" class="form-control form-control-sm" value="<?= htmlspecialchars($p['kode_pos']??'') ?>" maxlength="10" inputmode="numeric" pattern="[0-9]*"></div>

                <div class="col-6"><label class="form-label mb-0 small">No. HP/WhatsApp</label>
                    <input type="text" name="no_telp" class="form-control form-control-sm" value="<?= htmlspecialchars($p['no_telp']??'') ?>" inputmode="tel"></div>
                <div class="col-6"><label class="form-label mb-0 small">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($p['email']??'') ?>"></div>
                <div class="col-6"><label class="form-label mb-0 small">Asal Sekolah</label>
                    <input type="text" name="asal_sekolah" class="form-control form-control-sm" value="<?= htmlspecialchars($p['asal_sekolah']??'') ?>"></div>
                <div class="col-6"><label class="form-label mb-0 small">Penerima KIP/KJP/KPS</label>
                    <input type="text" name="kip_kjp_kps" class="form-control form-control-sm" value="<?= htmlspecialchars($p['kip_kjp_kps']??'') ?>" placeholder="Kosongkan jika tidak ada"></div>
            </div>
          </div>

          <!-- Tab B: Data Orang Tua/Wali -->
          <div class="tab-pane fade" id="du2">
            <div class="alert alert-info py-2 small mb-3">
              <i class="bi bi-info-circle me-1"></i>
              Semua data di bawah <strong>opsional</strong> — isi yang ada saja, <strong>kosongkan</strong> bila tidak ada.
              Jangan diisi "0". Untuk yatim/piatu/hanya wali, cukup isi bagian yang tersedia.
            </div>
            <!-- Ayah -->
            <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px;">1. Ayah Kandung <span class="fw-normal text-muted">(opsional)</span></div>
            <div class="row g-2 mb-3">
                <div class="col-7"><label class="form-label mb-0 small">Nama Lengkap Ayah</label>
                    <input type="text" name="nama_ayah" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nama_ayah']??'') ?>"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Ayah</label>
                    <input type="text" name="nik_ayah" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nik_ayah']??'') ?>" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan Terakhir</label>
                    <select name="pendidikan_ayah" class="form-select form-select-sm"><option value="">—</option><?php foreach ($pend_opts as $po): ?><option value="<?= $po ?>" <?= ($p['pendidikan_ayah']??'')===$po?'selected':'' ?>><?= $po ?></option><?php endforeach; ?></select></div>
                <div class="col-4"><label class="form-label mb-0 small">Pekerjaan</label>
                    <input type="text" name="pekerjaan_ayah" class="form-control form-control-sm" value="<?= htmlspecialchars($p['pekerjaan_ayah']??'') ?>" list="pekerjaan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label>
                    <input type="text" name="penghasilan_ayah" class="form-control form-control-sm" value="<?= htmlspecialchars($p['penghasilan_ayah']??'') ?>" placeholder="Rp 3.000.000" list="penghasilan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. HP</label>
                    <input type="text" name="telp_ayah" class="form-control form-control-sm" value="<?= htmlspecialchars($p['telp_ayah']??'') ?>" inputmode="tel"></div>
                <div class="col-8"><label class="form-label mb-0 small">Alamat Ayah</label>
                    <input type="text" name="alamat_ayah" class="form-control form-control-sm" value="<?= htmlspecialchars($p['alamat_ayah']??'') ?>" placeholder="Kosongkan jika sama dengan siswa"></div>
            </div>
            <hr class="my-2">
            <!-- Ibu -->
            <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px;">2. Ibu Kandung <span class="fw-normal text-muted">(opsional)</span></div>
            <div class="row g-2 mb-3">
                <div class="col-7"><label class="form-label mb-0 small">Nama Lengkap Ibu</label>
                    <input type="text" name="nama_ibu" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nama_ibu']??'') ?>"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Ibu</label>
                    <input type="text" name="nik_ibu" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nik_ibu']??'') ?>" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan Terakhir</label>
                    <select name="pendidikan_ibu" class="form-select form-select-sm"><option value="">—</option><?php foreach ($pend_opts as $po): ?><option value="<?= $po ?>" <?= ($p['pendidikan_ibu']??'')===$po?'selected':'' ?>><?= $po ?></option><?php endforeach; ?></select></div>
                <div class="col-4"><label class="form-label mb-0 small">Pekerjaan</label>
                    <input type="text" name="pekerjaan_ibu" class="form-control form-control-sm" value="<?= htmlspecialchars($p['pekerjaan_ibu']??'') ?>" list="pekerjaan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label>
                    <input type="text" name="penghasilan_ibu" class="form-control form-control-sm" value="<?= htmlspecialchars($p['penghasilan_ibu']??'') ?>" placeholder="Rp 3.000.000" list="penghasilan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. HP</label>
                    <input type="text" name="telp_ibu" class="form-control form-control-sm" value="<?= htmlspecialchars($p['telp_ibu']??'') ?>" inputmode="tel"></div>
                <div class="col-8"><label class="form-label mb-0 small">Alamat Ibu</label>
                    <input type="text" name="alamat_ibu" class="form-control form-control-sm" value="<?= htmlspecialchars($p['alamat_ibu']??'') ?>" placeholder="Kosongkan jika sama dengan siswa"></div>
            </div>
            <hr class="my-2">
            <!-- Wali -->
            <div class="small fw-bold text-uppercase text-muted mb-2" style="letter-spacing:.5px;">3. Wali <span class="fw-normal text-muted">(jika orang tua tidak ada)</span></div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label mb-0 small">Nama Lengkap Wali</label>
                    <input type="text" name="nama_wali" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nama_wali']??'') ?>"></div>
                <div class="col-6"><label class="form-label mb-0 small">Hubungan dengan Siswa</label>
                    <input type="text" name="hubungan_wali" class="form-control form-control-sm" value="<?= htmlspecialchars($p['hubungan_wali']??'') ?>" placeholder="Contoh: Paman, Nenek"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Wali</label>
                    <input type="text" name="nik_wali" class="form-control form-control-sm" value="<?= htmlspecialchars($p['nik_wali']??'') ?>" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan Terakhir</label>
                    <select name="pendidikan_wali" class="form-select form-select-sm"><option value="">—</option><?php foreach ($pend_opts as $po): ?><option value="<?= $po ?>" <?= ($p['pendidikan_wali']??'')===$po?'selected':'' ?>><?= $po ?></option><?php endforeach; ?></select></div>
                <div class="col-3"><label class="form-label mb-0 small">No. HP</label>
                    <input type="text" name="telp_wali" class="form-control form-control-sm" value="<?= htmlspecialchars($p['telp_wali']??'') ?>" inputmode="tel"></div>
                <div class="col-5"><label class="form-label mb-0 small">Pekerjaan</label>
                    <input type="text" name="pekerjaan_wali" class="form-control form-control-sm" value="<?= htmlspecialchars($p['pekerjaan_wali']??'') ?>" list="pekerjaan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label>
                    <input type="text" name="penghasilan_wali" class="form-control form-control-sm" value="<?= htmlspecialchars($p['penghasilan_wali']??'') ?>" list="penghasilan_list"></div>
                <div class="col-12"><label class="form-label mb-0 small">Alamat Wali</label>
                    <input type="text" name="alamat_wali" class="form-control form-control-sm" value="<?= htmlspecialchars($p['alamat_wali']??'') ?>"></div>
            </div>
          </div>

        </div><!-- /tab-content -->

        <!-- Tombol aksi -->
        <div class="border-top p-3 d-flex gap-2">
            <button type="submit" form="unlinkDU" class="btn btn-outline-danger btn-sm"
                    onclick="return confirm('Lepas tautan siswa ini dari nomor antrian? Berguna jika salah pilih siswa. Data DU belum disimpan, jadi aman.')">
                <i class="bi bi-x-lg me-1"></i>Batal
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    onclick="cetakSPTJM(DU_DATA[<?= (int)$p['id'] ?>])">
                <i class="bi bi-printer me-1"></i>Cetak SPTJM
            </button>
            <button type="submit" class="btn-du-main btn-du-selesai btn flex-grow-1">
                <i class="bi bi-check-circle me-1"></i>Simpan (→ Proses)
            </button>
        </div>
    </form>
    <!-- Form terpisah utk lepas tautan (tidak boleh nested di dalam #formDU) -->
    <form method="POST" id="unlinkDU">
        <input type="hidden" name="action" value="link_du">
        <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
        <input type="hidden" name="pendaftar_id" value="0">
    </form>
    </div>
</div>

<?php elseif ($current && !$current_p): ?>
<!-- Nomor aktif tapi belum terhubung -->
<div class="card">
    <div class="card-header fw-semibold text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Cari & Hubungkan Siswa</div>
    <div class="card-body">
        <input type="text" class="form-control mb-2" id="searchLink" placeholder="Cari nama siswa...">
        <div style="max-height:300px;overflow-y:auto;">
        <?php foreach ($diterima_list as $s):
            // Hanya siswa yang BELUM daftar ulang yang boleh dihubungkan ke nomor antrian.
            // Yang sudah 'proses'/'sudah' sudah pernah DU → jangan tampil di sini.
            if (($s['daftar_ulang'] ?? 'belum') !== 'belum') continue;
        ?>
        <form method="POST" class="d-flex align-items-center gap-2 p-2 border-bottom link-item"
              data-s="<?= strtolower(htmlspecialchars($s['nama'])) ?>">
            <input type="hidden" name="action" value="link_du">
            <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
            <input type="hidden" name="pendaftar_id" value="<?= $s['id'] ?>">
            <div class="flex-grow-1 small">
                <div class="fw-semibold"><?= htmlspecialchars($s['nama']) ?></div>
                <div class="text-muted"><?= htmlspecialchars($s['no_pendaftaran']) ?> &middot; <?= JURUSAN_SHORT[$s['jurusan']]??'' ?></div>
            </div>
            <button type="submit" class="btn btn-sm btn-outline-primary py-0">Pilih</button>
        </form>
        <?php endforeach; ?>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Placeholder (default) -->
<div id="editEmptyPlaceholder" class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-arrow-left-circle d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
        Panggil nomor terlebih dahulu, atau klik <i class="bi bi-pencil"></i> pada siswa sudah DU untuk edit data.
    </div>
</div>
<!-- Form Edit (tersembunyi, muncul saat JS showEditPanel() dipanggil) -->
<div id="editPanel" class="card h-100" style="display:none;">
    <div class="card-header d-flex align-items-center justify-content-between py-2" style="background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;">
        <div>
            <div class="fw-bold" id="ep_nama">—</div>
            <div class="small" style="color:#e0e7ff;" id="ep_sub">—</div>
        </div>
        <button type="button" class="btn btn-sm btn-light py-0 px-2 opacity-75" onclick="hideEditPanel()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <form method="POST" class="d-flex flex-column" style="min-height:0;flex:1;" novalidate>
        <input type="hidden" name="action" value="simpan_edit_du">
        <input type="hidden" name="pendaftar_id" id="ep_pendaftar_id">
        <ul class="nav nav-tabs nav-fill px-3 pt-2 border-bottom-0 bg-light small" id="epTabs">
            <li class="nav-item"><a class="nav-link active py-1" href="#ep1" data-bs-toggle="tab">A. Data Siswa</a></li>
            <li class="nav-item"><a class="nav-link py-1" href="#ep2" data-bs-toggle="tab">B. Orang Tua/Wali</a></li>
        </ul>
        <div class="tab-content p-3" style="overflow-y:auto;flex:1;">
          <div class="tab-pane fade show active" id="ep1">
            <div class="row g-2">
                <div class="col-4"><label class="form-label mb-0 small">NIS</label><input type="text" name="nis" id="ep_nis" class="form-control form-control-sm" inputmode="numeric"></div>
                <div class="col-4"><label class="form-label mb-0 small">NIK Siswa</label><input type="text" name="nik" id="ep_nik" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. KK</label><input type="text" name="no_kk" id="ep_no_kk" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kewarganegaraan</label><input type="text" name="kewarganegaraan" id="ep_kewarganegaraan" class="form-control form-control-sm"></div>
                <div class="col-4"><label class="form-label mb-0 small">Agama</label>
                    <select name="agama" id="ep_agama" class="form-select form-select-sm"><option value="">— Pilih —</option><?php foreach($agama_opts as $ag): ?><option value="<?=$ag?>"><?=$ag?></option><?php endforeach;?></select></div>
                <div class="col-4"><label class="form-label mb-0 small">Anak Ke-</label><input type="number" name="anak_ke" id="ep_anak_ke" class="form-control form-control-sm" min="1" max="20"></div>
                <div class="col-5"><label class="form-label mb-0 small">Tempat Lahir</label><input type="text" name="tempat_lahir" id="ep_tempat_lahir" class="form-control form-control-sm"></div>
                <div class="col-3"><label class="form-label mb-0 small">Tahun Lulus SMP</label><input type="number" name="tahun_lulus" id="ep_tahun_lulus" class="form-control form-control-sm" min="2000" max="2030"></div>
                <div class="col-4"><label class="form-label mb-0 small">Email</label><input type="email" name="email" id="ep_email" class="form-control form-control-sm"></div>
                <div class="col-12"><label class="form-label mb-0 small">Alamat Lengkap</label><textarea name="alamat_lengkap" id="ep_alamat_lengkap" class="form-control form-control-sm" rows="2"></textarea></div>
                <div class="col-2"><label class="form-label mb-0 small">RT</label><input type="text" name="rt" id="ep_rt" class="form-control form-control-sm" maxlength="5" inputmode="numeric"></div>
                <div class="col-2"><label class="form-label mb-0 small">RW</label><input type="text" name="rw" id="ep_rw" class="form-control form-control-sm" maxlength="5" inputmode="numeric"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kecamatan</label><input type="text" name="kecamatan" id="ep_kecamatan" class="form-control form-control-sm"></div>
                <div class="col-4"><label class="form-label mb-0 small">Kabupaten/Kota</label><input type="text" name="kabupaten" id="ep_kabupaten" class="form-control form-control-sm"></div>
                <div class="col-5"><label class="form-label mb-0 small">Provinsi</label><input type="text" name="provinsi" id="ep_provinsi" class="form-control form-control-sm"></div>
                <div class="col-3"><label class="form-label mb-0 small">Kode Pos</label><input type="text" name="kode_pos" id="ep_kode_pos" class="form-control form-control-sm" maxlength="10" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">KIP/KJP/KPS</label><input type="text" name="kip_kjp_kps" id="ep_kip_kjp_kps" class="form-control form-control-sm"></div>
            </div>
          </div>
          <div class="tab-pane fade" id="ep2">
            <div class="alert alert-info py-2 small mb-3">
              <i class="bi bi-info-circle me-1"></i>
              Semua data orang tua/wali <strong>opsional</strong> — kosongkan bila tidak ada, jangan diisi "0".
            </div>
            <div class="small fw-bold text-uppercase text-muted mb-2">1. Ayah <span class="fw-normal text-muted">(opsional)</span></div>
            <div class="row g-2 mb-3">
                <div class="col-7"><label class="form-label mb-0 small">Nama Ayah</label><input type="text" name="nama_ayah" id="ep_nama_ayah" class="form-control form-control-sm"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Ayah</label><input type="text" name="nik_ayah" id="ep_nik_ayah" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan</label><select name="pendidikan_ayah" id="ep_pendidikan_ayah" class="form-select form-select-sm"><option value="">—</option><?php foreach($pend_opts as $po): ?><option value="<?=$po?>"><?=$po?></option><?php endforeach;?></select></div>
                <div class="col-4"><label class="form-label mb-0 small">Pekerjaan</label><input type="text" name="pekerjaan_ayah" id="ep_pekerjaan_ayah" class="form-control form-control-sm" list="pekerjaan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label><input type="text" name="penghasilan_ayah" id="ep_penghasilan_ayah" class="form-control form-control-sm" list="penghasilan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. HP</label><input type="text" name="telp_ayah" id="ep_telp_ayah" class="form-control form-control-sm" inputmode="tel"></div>
                <div class="col-8"><label class="form-label mb-0 small">Alamat Ayah</label><input type="text" name="alamat_ayah" id="ep_alamat_ayah" class="form-control form-control-sm"></div>
            </div>
            <hr class="my-2">
            <div class="small fw-bold text-uppercase text-muted mb-2">2. Ibu</div>
            <div class="row g-2 mb-3">
                <div class="col-7"><label class="form-label mb-0 small">Nama Ibu</label><input type="text" name="nama_ibu" id="ep_nama_ibu" class="form-control form-control-sm"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Ibu</label><input type="text" name="nik_ibu" id="ep_nik_ibu" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan</label><select name="pendidikan_ibu" id="ep_pendidikan_ibu" class="form-select form-select-sm"><option value="">—</option><?php foreach($pend_opts as $po): ?><option value="<?=$po?>"><?=$po?></option><?php endforeach;?></select></div>
                <div class="col-4"><label class="form-label mb-0 small">Pekerjaan</label><input type="text" name="pekerjaan_ibu" id="ep_pekerjaan_ibu" class="form-control form-control-sm" list="pekerjaan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label><input type="text" name="penghasilan_ibu" id="ep_penghasilan_ibu" class="form-control form-control-sm" list="penghasilan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">No. HP</label><input type="text" name="telp_ibu" id="ep_telp_ibu" class="form-control form-control-sm" inputmode="tel"></div>
                <div class="col-8"><label class="form-label mb-0 small">Alamat Ibu</label><input type="text" name="alamat_ibu" id="ep_alamat_ibu" class="form-control form-control-sm"></div>
            </div>
            <hr class="my-2">
            <div class="small fw-bold text-uppercase text-muted mb-2">3. Wali</div>
            <div class="row g-2">
                <div class="col-6"><label class="form-label mb-0 small">Nama Wali</label><input type="text" name="nama_wali" id="ep_nama_wali" class="form-control form-control-sm"></div>
                <div class="col-6"><label class="form-label mb-0 small">Hubungan</label><input type="text" name="hubungan_wali" id="ep_hubungan_wali" class="form-control form-control-sm"></div>
                <div class="col-5"><label class="form-label mb-0 small">NIK Wali</label><input type="text" name="nik_wali" id="ep_nik_wali" class="form-control form-control-sm" maxlength="16" inputmode="numeric" pattern="[0-9]*"></div>
                <div class="col-4"><label class="form-label mb-0 small">Pendidikan</label><select name="pendidikan_wali" id="ep_pendidikan_wali" class="form-select form-select-sm"><option value="">—</option><?php foreach($pend_opts as $po): ?><option value="<?=$po?>"><?=$po?></option><?php endforeach;?></select></div>
                <div class="col-3"><label class="form-label mb-0 small">No. HP</label><input type="text" name="telp_wali" id="ep_telp_wali" class="form-control form-control-sm" inputmode="tel"></div>
                <div class="col-5"><label class="form-label mb-0 small">Pekerjaan</label><input type="text" name="pekerjaan_wali" id="ep_pekerjaan_wali" class="form-control form-control-sm" list="pekerjaan_list"></div>
                <div class="col-4"><label class="form-label mb-0 small">Penghasilan/Bln</label><input type="text" name="penghasilan_wali" id="ep_penghasilan_wali" class="form-control form-control-sm" list="penghasilan_list"></div>
                <div class="col-12"><label class="form-label mb-0 small">Alamat Wali</label><input type="text" name="alamat_wali" id="ep_alamat_wali" class="form-control form-control-sm"></div>
            </div>
          </div>
        </div>
        <div class="border-top p-3 d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="ep_cetak_btn">
                <i class="bi bi-printer me-1"></i>Cetak SPTJM
            </button>
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-save me-1"></i>Simpan Perubahan
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

</div><!-- /col kanan -->
</div><!-- /row -->
<?php endif; ?>


<script>
// Data lengkap semua siswa diterima — dipakai oleh bukaEditDU() dan cetakSPTJM()
// JSON_INVALID_UTF8_SUBSTITUTE + fallback '{}' — cegah SATU record berdata rusak
// (UTF-8 tak valid dari copy-paste) membuat json_encode return false → "const DU_DATA = ;"
// (syntax error) → seluruh JS halaman mati → tombol Edit/Cetak tak berfungsi.
const DU_DATA = <?= json_encode(array_column($diterima_list, null, 'id'), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}' ?>;
</script>
<script>
// ── Filter jurusan + teks DU (localStorage agar tidak reset saat refresh) ─────
(function() {
    const LS_KEY = 'du_filter_jur';
    let activeJur = localStorage.getItem(LS_KEY) || '';

    function applyFilter() {
        const q = (document.getElementById('searchDU')?.value || '').toLowerCase();
        document.querySelectorAll('.du-search-item').forEach(el => {
            const matchJur = activeJur === '' || el.dataset.jurusan === activeJur;
            const matchQ   = q === '' || el.dataset.search.includes(q);
            el.style.display = (matchJur && matchQ) ? '' : 'none';
        });
    }

    function setActive(jur) {
        activeJur = jur;
        localStorage.setItem(LS_KEY, jur);
        document.querySelectorAll('.du-jur-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.jur === jur);
            btn.classList.toggle('btn-secondary', btn.dataset.jur === jur);
            btn.classList.toggle('btn-outline-secondary', btn.dataset.jur !== jur);
        });
        applyFilter();
    }

    // Restore filter dari localStorage saat halaman load
    document.querySelectorAll('.du-jur-btn').forEach(btn => {
        btn.addEventListener('click', () => setActive(btn.dataset.jur));
    });
    setActive(activeJur);

    document.getElementById('searchDU')?.addEventListener('input', applyFilter);
})();
// Filter link siswa
document.getElementById('searchLink')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.link-item').forEach(el => {
        el.style.display = el.dataset.s.includes(q) ? '' : 'none';
    });
});

// ── Form Edit di kolom kanan (tanpa reload halaman) ──────────────────────────
function showEditPanel(id) {
    const panel   = document.getElementById('editPanel');
    const holder  = document.getElementById('editEmptyPlaceholder');
    if (!panel || !holder) return; // sedang ada sesi aktif, edit tidak bisa ditampilkan

    const p = DU_DATA[id];
    if (!p) return;

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };

    document.getElementById('ep_pendaftar_id').value = p.id;
    document.getElementById('ep_nama').textContent = p.nama || '—';
    document.getElementById('ep_sub').textContent = (p.no_pendaftaran || '') + ' · ' + (p.jurusan_short || p.jurusan || '');

    set('ep_nis', p.nis); set('ep_nik', p.nik); set('ep_no_kk', p.no_kk);
    set('ep_kewarganegaraan', p.kewarganegaraan || 'WNI');
    set('ep_agama', p.agama); set('ep_anak_ke', p.anak_ke);
    set('ep_tempat_lahir', p.tempat_lahir); set('ep_tahun_lulus', p.tahun_lulus);
    set('ep_email', p.email); set('ep_alamat_lengkap', p.alamat_lengkap || p.alamat);
    set('ep_rt', p.rt); set('ep_rw', p.rw);
    set('ep_kecamatan', p.kecamatan); set('ep_kabupaten', p.kabupaten);
    set('ep_provinsi', p.provinsi || 'DKI Jakarta'); set('ep_kode_pos', p.kode_pos);
    set('ep_kip_kjp_kps', p.kip_kjp_kps);
    set('ep_nama_ayah', p.nama_ayah); set('ep_nik_ayah', p.nik_ayah);
    set('ep_pendidikan_ayah', p.pendidikan_ayah); set('ep_pekerjaan_ayah', p.pekerjaan_ayah);
    set('ep_penghasilan_ayah', p.penghasilan_ayah); set('ep_telp_ayah', p.telp_ayah);
    set('ep_alamat_ayah', p.alamat_ayah);
    set('ep_nama_ibu', p.nama_ibu); set('ep_nik_ibu', p.nik_ibu);
    set('ep_pendidikan_ibu', p.pendidikan_ibu); set('ep_pekerjaan_ibu', p.pekerjaan_ibu);
    set('ep_penghasilan_ibu', p.penghasilan_ibu); set('ep_telp_ibu', p.telp_ibu);
    set('ep_alamat_ibu', p.alamat_ibu);
    set('ep_nama_wali', p.nama_wali); set('ep_hubungan_wali', p.hubungan_wali);
    set('ep_nik_wali', p.nik_wali); set('ep_pendidikan_wali', p.pendidikan_wali);
    set('ep_pekerjaan_wali', p.pekerjaan_wali); set('ep_penghasilan_wali', p.penghasilan_wali);
    set('ep_telp_wali', p.telp_wali); set('ep_alamat_wali', p.alamat_wali);

    // Tombol cetak pakai data yg sudah ada di p
    const cetakBtn = document.getElementById('ep_cetak_btn');
    if (cetakBtn) cetakBtn.onclick = () => cetakSPTJM(p);

    // Reset ke Tab A
    const firstTab = document.querySelector('#epTabs .nav-link');
    if (firstTab) new bootstrap.Tab(firstTab).show();

    holder.style.display = 'none';
    panel.style.display = '';
}

function hideEditPanel() {
    const panel  = document.getElementById('editPanel');
    const holder = document.getElementById('editEmptyPlaceholder');
    if (panel)  panel.style.display  = 'none';
    if (holder) holder.style.display = '';
}

// ── Form DU Langsung (panel kanan di mode tanpa meja) ─────────────────────────
function openDuPanel(id) {
    const p = DU_DATA[id];
    if (!p) return;
    const s = (elId, val) => { const el = document.getElementById(elId); if (el) el.value = val ?? ''; };
    const du = p.daftar_ulang || 'belum';

    document.getElementById('dpf_id').value = p.id;
    document.getElementById('dpf_nama').textContent = p.nama || '—';
    const jurShort = p.jurusan ? (p.jurusan.match(/\(([^)]+)\)/) || ['',''])[1] || p.jurusan.substring(0,6) : '';
    const duLabel  = du === 'sudah' ? '✓ Sudah DU' : du === 'proses' ? '⏳ Proses' : 'Belum DU';
    document.getElementById('dpf_sub').textContent = (p.no_pendaftaran||'') + ' · ' + jurShort + ' · ' + duLabel;

    const nrv = document.getElementById('dpf_namarv');
    const nisnrv = document.getElementById('dpf_nisnrv');
    if (nrv) nrv.value = p.nama || '';
    if (nisnrv) nisnrv.value = p.nisn || '';

    s('dpf_nis', p.nis); s('dpf_nik', p.nik); s('dpf_no_kk', p.no_kk);
    s('dpf_kewarganegaraan', p.kewarganegaraan || 'WNI');
    s('dpf_agama', p.agama); s('dpf_anak_ke', p.anak_ke); s('dpf_tahun_lulus', p.tahun_lulus);
    s('dpf_tempat_lahir', p.tempat_lahir); s('dpf_email', p.email);
    s('dpf_alamat_lengkap', p.alamat_lengkap || p.alamat);
    s('dpf_rt', p.rt); s('dpf_rw', p.rw);
    s('dpf_kecamatan', p.kecamatan); s('dpf_kabupaten', p.kabupaten);
    s('dpf_provinsi', p.provinsi || 'DKI Jakarta'); s('dpf_kode_pos', p.kode_pos);
    s('dpf_kip_kjp_kps', p.kip_kjp_kps);
    s('dpf_nama_ayah', p.nama_ayah); s('dpf_nik_ayah', p.nik_ayah);
    s('dpf_pendidikan_ayah', p.pendidikan_ayah); s('dpf_pekerjaan_ayah', p.pekerjaan_ayah);
    s('dpf_penghasilan_ayah', p.penghasilan_ayah); s('dpf_telp_ayah', p.telp_ayah);
    s('dpf_alamat_ayah', p.alamat_ayah);
    s('dpf_nama_ibu', p.nama_ibu); s('dpf_nik_ibu', p.nik_ibu);
    s('dpf_pendidikan_ibu', p.pendidikan_ibu); s('dpf_pekerjaan_ibu', p.pekerjaan_ibu);
    s('dpf_penghasilan_ibu', p.penghasilan_ibu); s('dpf_telp_ibu', p.telp_ibu);
    s('dpf_alamat_ibu', p.alamat_ibu);
    s('dpf_nama_wali', p.nama_wali); s('dpf_hubungan_wali', p.hubungan_wali);
    s('dpf_nik_wali', p.nik_wali); s('dpf_pendidikan_wali', p.pendidikan_wali);
    s('dpf_pekerjaan_wali', p.pekerjaan_wali); s('dpf_penghasilan_wali', p.penghasilan_wali);
    s('dpf_telp_wali', p.telp_wali); s('dpf_alamat_wali', p.alamat_wali);

    const lbl = document.getElementById('dpf_submit_label');
    if (lbl) lbl.textContent = du === 'belum' ? 'Simpan (→ Proses)' : 'Update Data';

    const tandaiBtn = document.getElementById('dpf_tandai_btn');
    const batalBtn  = document.getElementById('dpf_batal_btn');
    if (tandaiBtn) tandaiBtn.style.display = du === 'proses' ? '' : 'none';
    if (batalBtn)  batalBtn.style.display  = du !== 'belum'  ? '' : 'none';

    const tId = document.getElementById('dpf_form_tandai_id');
    const bId = document.getElementById('dpf_form_batal_id');
    if (tId) tId.value = p.id;
    if (bId) bId.value = p.id;

    const cetakBtn = document.getElementById('dpf_cetak_btn');
    if (cetakBtn) cetakBtn.onclick = () => cetakSPTJM(p);

    const firstTab = document.querySelector('#dpfTabs .nav-link');
    if (firstTab) new bootstrap.Tab(firstTab).show();

    document.getElementById('duPanelPlaceholder').style.display = 'none';
    document.getElementById('duPanelForm').style.display = '';

    document.querySelectorAll('.du-tabel-row').forEach(tr => tr.classList.remove('table-active'));
    document.querySelector(`.du-tabel-row[data-id="${id}"]`)?.classList.add('table-active');
}

function closeDuPanel() {
    const ph = document.getElementById('duPanelPlaceholder');
    const pf = document.getElementById('duPanelForm');
    if (ph) ph.style.display = '';
    if (pf) pf.style.display = 'none';
    document.querySelectorAll('.du-tabel-row').forEach(tr => tr.classList.remove('table-active'));
}

// ── Cetak SPTJM (Surat Pernyataan Tanggung Jawab Mutlak) ──────────────────────
function cetakSPTJM(p) {
    const dot = '...........';
    // "0" / "-" dianggap kosong (sisa kebiasaan lama mengisi 0 saat data tak ada)
    const real = v => { const s = String(v ?? '').trim(); return (s === '' || s === '0' || s === '-') ? '' : s; };
    // Pilih SATU sumber ortu/wali yang konsisten: Wali → Ibu → Ayah
    let namaWali, gNik, gAlamat, gHp;
    if (real(p.nama_wali))      { namaWali = real(p.nama_wali); gNik = real(p.nik_wali); gAlamat = real(p.alamat_wali); gHp = real(p.telp_wali); }
    else if (real(p.nama_ibu))  { namaWali = real(p.nama_ibu);  gNik = real(p.nik_ibu);  gAlamat = real(p.alamat_ibu);  gHp = real(p.telp_ibu); }
    else if (real(p.nama_ayah)) { namaWali = real(p.nama_ayah); gNik = real(p.nik_ayah); gAlamat = real(p.alamat_ayah); gHp = real(p.telp_ayah); }
    namaWali = namaWali || dot;
    gNik     = gNik     || dot;
    gAlamat  = gAlamat  || p.kelurahan || dot;
    gHp      = gHp      || dot;

    const tglLahirFmt = p.tanggal_lahir ? new Date(p.tanggal_lahir).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : '...........';
    const ttlStr = tglLahirFmt;
    const ttlStr2 = (p.tempat_lahir || '...........') + ', ' + tglLahirFmt;

    const now = new Date();
    const bulanList = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const tglSurat = now.getDate() + ' ' + bulanList[now.getMonth()] + ' ' + now.getFullYear();

    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8">
<title>SPTJM — ${p.nama}</title>
<style>
    body { font-family: 'Times New Roman', serif; font-size: 12pt; margin: 0; padding: 0; }
    .page { width: 210mm; min-height: 0; margin: 0 auto; padding: 10mm 18mm 10mm; box-sizing: border-box; page-break-after: always; }
    .page:last-child { page-break-after: avoid; }
    .page-img { padding: 0; }
    .form-full { width: 100%; height: auto; display: block; }
    .kop { text-align: center; margin-bottom: 8px; }
    .kop img { height: 32mm; width: auto; max-width: 100%; display: block; margin: 0 auto; }
    .kop h2 { font-size: 13pt; margin: 4px 0 2px; text-transform: uppercase; letter-spacing: 1px; }
    .kop p { font-size: 9pt; margin: 2px 0; }
    h3 { text-align: center; font-size: 13pt; text-decoration: underline; margin: 6px 0 4px; letter-spacing: 1px; }
    .sub { text-align: center; font-size: 10pt; margin-bottom: 12px; }
    .identitas { margin-bottom: 8px; }
    .identitas table { border-collapse: collapse; }
    .identitas td { padding: 2px 4px; vertical-align: top; font-size: 11.5pt; }
    .identitas td:first-child { width: 180px; font-weight: bold; }
    .identitas td:nth-child(2) { width: 12px; }
    ol { margin: 0 0 10px 0; padding-left: 22px; }
    ol li { margin-bottom: 3px; line-height: 1.3; text-align: justify; }
    .penutup { line-height: 1.5; margin-bottom: 12px; text-align: justify; }
    .ttd { display: flex; justify-content: space-between; margin-top: 14px; }
    .ttd-box { text-align: center; }
    .ttd-box .label { margin-bottom: 40px; }
    .ttd-box .nama { border-top: 1px solid #000; padding-top: 4px; min-width: 180px; display: inline-block; }
    .materai { border: 1px dashed #888; width: 74px; height: 70px; margin: 0 auto 6px; display: flex; align-items: center; justify-content: center; font-size: 8pt; color: #888; text-align: center; }
    /* Formulir kosong */
    .form-title { text-align:center; font-size:13pt; font-weight:bold; text-decoration:underline; margin-bottom:4px; letter-spacing:1px; }
    .form-sub { text-align:center; font-size:9pt; margin-bottom:16px; }
    .f-section { font-size:10pt; font-weight:bold; text-transform:uppercase; letter-spacing:.5px; border-bottom:1.5px solid #000; margin:9px 0 4px; padding-bottom:2px; }
    .f-subsec { font-size:9.5pt; font-weight:bold; margin:4px 0 2px; color:#222; }
    .f-row { display:flex; align-items:flex-end; margin-bottom:3px; gap:6px; font-size:9.5pt; }
    .f-label { flex-shrink:0; width:150px; }
    .f-sep { flex-shrink:0; }
    .f-line { flex:1; border-bottom:1px solid #555; min-height:14px; }
    .f-fill { flex:1; border-bottom:1px dotted #555; min-height:14px; padding:0 4px; font-size:9.5pt; line-height:1.35; word-break:break-word; }
    .f-row-half { display:flex; gap:14px; }
    .f-row-half .f-row { flex:1; }
    .f-row-half .f-label { width:112px; }
    .f-chkrow { display:flex; flex-wrap:wrap; gap:5px 16px; margin:3px 0 2px; font-size:9.5pt; }
    .f-chk-item { display:inline-flex; align-items:center; gap:5px; }
    .f-chk { display:inline-flex; align-items:center; justify-content:center; width:13px; height:13px; border:1.2px solid #333; font-size:9px; line-height:1; flex-shrink:0; }
    .f-chk.on { background:#198754; border-color:#198754; color:#fff; }
    @media print { @page { size: A4; margin: 0; } body { print-color-adjust: exact; } }
</style></head><body>
<div class="page">
    <div class="kop">
        <img src="<?= htmlspecialchars($asset_base) ?>/assets/img/kop-surat.png" alt="Kop SMKS Laboratorium Jakarta">
    </div>

    <h3>SURAT PERNYATAAN TANGGUNG JAWAB MUTLAK</h3>
    <div class="sub">(SPTJM)</div>

    <p style="margin-bottom:12px;">Yang bertanda tangan di bawah ini:</p>
    <div class="identitas">
        <table>
            <tr><td>Nama Orang Tua/Wali</td><td>:</td><td>${namaWali}</td></tr>
            <tr><td>NIK</td><td>:</td><td>${gNik}</td></tr>
            <tr><td>Alamat</td><td>:</td><td>${gAlamat}</td></tr>
            <tr><td>No. HP</td><td>:</td><td>${gHp}</td></tr>
        </table>
    </div>

    <p style="margin-bottom:12px;">Selaku orang tua/wali dari:</p>
    <div class="identitas">
        <table>
            <tr><td>Nama Peserta Didik</td><td>:</td><td>${p.nama || dot}</td></tr>
            <tr><td>NISN</td><td>:</td><td>${p.nisn || dot}</td></tr>
            <tr><td>Tempat, Tanggal Lahir</td><td>:</td><td>${ttlStr2}</td></tr>
            <tr><td>Asal Sekolah</td><td>:</td><td>${p.asal_sekolah || dot}</td></tr>
        </table>
    </div>

    <p style="margin-bottom:8px;">Dengan ini menyatakan dengan sesungguhnya bahwa:</p>
    <ol>
        <li>Anak kami telah dinyatakan <strong>LULUS SELEKSI SPMB SMKS Laboratorium Jakarta</strong> Tahun Pelajaran 2026/2027.</li>
        <li>Seluruh data, dokumen, dan informasi yang kami sampaikan selama proses pendaftaran adalah benar, sah, dapat dipertanggungjawabkan dan sedang <strong>TIDAK DITERIMA PMB BERSAMA</strong> Sekolah Negeri dan Sekolah Lainnya.</li>
        <li>Apabila di kemudian hari ditemukan adanya ketidaksesuaian, pemalsuan, atau ketidakbenaran data dan dokumen yang kami sampaikan, maka kami bersedia menerima segala konsekuensi sesuai dengan ketentuan yang berlaku, termasuk pembatalan status penerimaan peserta didik.</li>
        <li>Kami bersedia mematuhi seluruh tata tertib, peraturan akademik, dan ketentuan yang berlaku di SMKS Laboratorium Jakarta.</li>
        <li>Kami bertanggung jawab penuh atas keikutsertaan anak kami sebagai peserta didik di SMKS Laboratorium Jakarta.</li>
    </ol>

    <p class="penutup">Demikian Surat Pernyataan Tanggung Jawab Mutlak ini dibuat dengan sebenar-benarnya untuk digunakan sebagaimana mestinya.</p>

    <div class="ttd">
        <div style="width:40%;"></div>
        <div class="ttd-box">
            <div class="label">Jakarta, ${tglSurat}</div>
            <div class="label">Orang Tua/Wali,</div>
            <div class="materai">Materai<br>Rp10.000</div>
            <div class="nama">(${namaWali})</div>
        </div>
    </div>
</div>
<script>window.onload = function() { window.print(); }<\/script>
</body></html>`;

    // Halaman paling depan: Surat Keterangan Penerimaan Murid Baru — terisi otomatis
    const suratPage = `
<div class="page">
    <div class="kop">
        <img src="<?= htmlspecialchars($asset_base) ?>/assets/img/kop-surat.png" alt="Kop SMKS Laboratorium Jakarta">
    </div>
    <h3>SURAT KETERANGAN PENERIMAAN MURID BARU</h3>
    <div style="height:6px;"></div>

    <p style="margin-bottom:6px;">Yang bertanda tangan di bawah ini:</p>
    <div class="identitas"><table>
        <tr><td>Nama</td><td>:</td><td>Momon Darmawan, S.Pd, Gr</td></tr>
        <tr><td>Jabatan</td><td>:</td><td>Kepala Sekolah SMKS Laboratorium Jakarta</td></tr>
    </table></div>

    <p style="margin-bottom:6px;">Dengan ini menerangkan bahwa:</p>
    <div class="identitas"><table>
        <tr><td>Nama</td><td>:</td><td>${p.nama || dot}</td></tr>
        <tr><td>Tanggal Lahir</td><td>:</td><td>${ttlStr}</td></tr>
        <tr><td>Asal Sekolah</td><td>:</td><td>${p.asal_sekolah || dot}</td></tr>
        <tr><td>Nomor Pendaftaran</td><td>:</td><td>${p.no_pendaftaran || dot}</td></tr>
    </table></div>

    <p class="penutup">Berdasarkan hasil seleksi Penerimaan Murid Baru Tahun Pelajaran 2026/2027, nama tersebut di atas <strong>DINYATAKAN LULUS SELEKSI</strong> dan <strong>DITERIMA</strong> sebagai peserta didik baru di:</p>
    <div class="identitas"><table>
        <tr><td>Nama Sekolah</td><td>:</td><td>SMKS Laboratorium Jakarta</td></tr>
        <tr><td>Program Keahlian/Jurusan</td><td>:</td><td>${p.jurusan || dot}</td></tr>
    </table></div>

    <p class="penutup">Selanjutnya, calon peserta didik diharapkan melakukan daftar ulang sesuai jadwal yang telah ditetapkan dengan melengkapi seluruh persyaratan administrasi. Apabila tidak melakukan daftar ulang hingga batas waktu yang ditentukan, maka status penerimaan dapat dinyatakan gugur sesuai ketentuan yang berlaku.</p>
    <p class="penutup">Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

    <div class="ttd" style="margin-top:18px;">
        <div style="width:45%;"></div>
        <div class="ttd-box">
            <div style="margin-bottom:2px;">Jakarta, ${tglSurat}</div>
            <div style="margin-bottom:4px;">Kepala Sekolah,</div>
            <div style="font-size:9pt;color:#666;margin-bottom:54px;"></div>
            <div class="nama" style="min-width:230px;">Momon Darmawan, S.Pd, Gr</div>
            <div style="font-size:10pt;margin-top:3px;"></div>
        </div>
    </div>
</div>`;

    const fullHtml = html.replace('<body>', '<body>' + suratPage);

    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write(fullHtml);
    w.document.close();
}

// Cetak Formulir Pendaftaran kosong (2 lembar, dari docx) — tombol di layar pilih meja
function cetakFormulir() {
    const base = '<?= htmlspecialchars($asset_base) ?>';
    const v = '<?= htmlspecialchars($asset_ver) ?>';
    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Formulir Pendaftaran</title>
<style>
  @page { size: A4; margin: 0; }
  html, body { margin: 0; padding: 0; }
  .page { width: 210mm; margin: 0 auto; page-break-after: always; }
  .page:last-child { page-break-after: avoid; }
  .page img { width: 100%; height: auto; display: block; }
</style></head><body>
  <div class="page"><img src="${base}/assets/img/formulir-1.png?v=${v}" alt="Formulir Pendaftaran Lembar 1"></div>
  <div class="page"><img src="${base}/assets/img/formulir-2.png?v=${v}" alt="Formulir Pendaftaran Lembar 2"></div>
  <script>window.onload = function() { window.print(); }<\/script>
</body></html>`;
    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write(html);
    w.document.close();
}
</script>
