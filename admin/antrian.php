<?php
if (empty($_SESSION['admin_id']) && empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger">Akses ditolak.</div>'; return;
}

$today = date('Y-m-d');

// Identitas sekolah untuk kop bukti daftar — ikut Konten Website (site_settings)
$sch_nama   = 'SMKS Laboratorium Jakarta';
$sch_alamat = 'Jl. Rawa Jaya No.37, Duren Sawit, Jakarta Timur 13460';
try {
    $sq = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('sekolah_nama','sekolah_alamat')");
    foreach ($sq as $row) {
        if (trim((string)$row['setting_value']) === '') continue;
        if ($row['setting_key'] === 'sekolah_nama')   $sch_nama   = $row['setting_value'];
        if ($row['setting_key'] === 'sekolah_alamat') $sch_alamat = $row['setting_value'];
    }
} catch (PDOException $e) {}

$mejas_aktif = $conn->query("SELECT * FROM meja WHERE is_active=1 ORDER BY fase, nomor_meja")->fetchAll();

// Auto-migrate: pendaftar_id di antrian
try { $conn->exec("ALTER TABLE antrian ADD COLUMN pendaftar_id INT NULL AFTER nomor"); } catch (PDOException) {}
// Auto-migrate: kolom fase & hasil (schema lama belum punya)
try { $conn->exec("ALTER TABLE antrian ADD COLUMN fase TINYINT NOT NULL DEFAULT 1 AFTER pendaftar_id"); } catch (PDOException) {}
try { $conn->exec("ALTER TABLE antrian ADD COLUMN hasil ENUM('lulus','gagal') NULL AFTER fase"); } catch (PDOException) {}
// Auto-migrate: unique key lama (tanggal,nomor) memblokir nomor yang sama di fase 2
// → ganti dengan (tanggal,nomor,fase). Gagal harmless jika sudah dimigrate.
try { $conn->exec("ALTER TABLE antrian DROP INDEX uk_tanggal_nomor, ADD UNIQUE KEY uk_tanggal_nomor_fase (tanggal, nomor, fase)"); } catch (PDOException) {}
// Auto-migrate: presisi milidetik agar urutan panggilan antar-meja akurat (tekan hampir bersamaan)
try { $conn->exec("ALTER TABLE antrian MODIFY dipanggil_at TIMESTAMP(3) NULL DEFAULT NULL"); } catch (PDOException) {}

// ── POST Handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // PRG redirect: langsung ke dashboard yang sesuai (ob_start di dashboard membuat header() aman)
    $redir_antrian = function(string $qs) {
        $dash = !empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php';
        while (ob_get_level() > 0) ob_end_clean();
        header('Location: ' . $dash . '?' . $qs);
        exit;
    };

    if ($action === 'pilih_meja') {
        $mid = (int)$_POST['meja_id'];
        $_SESSION['antrian_meja_id']   = $mid;
        foreach ($mejas_aktif as $m) {
            if ((int)$m['id'] === $mid) { $_SESSION['antrian_meja_fase'] = (int)$m['fase']; break; }
        }
        $redir_antrian('page=antrian');
    }

    if ($action === 'ganti_meja') {
        unset($_SESSION['antrian_meja_id'], $_SESSION['antrian_meja_fase']);
        $redir_antrian('page=antrian');
    }

    // Hapus pendaftar dari tabel Riwayat (panel Meja Antrian / sidebar meja)
    if ($action === 'delete_pendaftar') {
        $pid = (int)($_POST['pendaftar_id'] ?? 0);
        // Kembali ke halaman asal (sidebar bisa dipanggil dari halaman mana saja)
        $back_page = preg_match('/^[a-z_]+$/', $_POST['redirect_to'] ?? '') ? $_POST['redirect_to'] : 'antrian';
        if ($pid) {
            try {
                $info = $conn->prepare("SELECT nama, no_pendaftaran FROM pendaftar WHERE id=?");
                $info->execute([$pid]);
                $del = $info->fetch();
                try { $conn->prepare("DELETE FROM pendaftar_raport WHERE pendaftar_id=?")->execute([$pid]); } catch (Throwable) {}
                $conn->prepare("DELETE FROM pendaftar WHERE id=?")->execute([$pid]);
                if ($del) log_admin_action($conn, 'HAPUS_PENDAFTAR', "Hapus dari Meja Antrian: {$del['nama']} ({$del['no_pendaftaran']})");
            } catch (Throwable) {}
        }
        $redir_antrian('page=' . $back_page);
    }

    $meja_id   = (int)($_SESSION['antrian_meja_id'] ?? 0);
    $meja_fase = (int)($_SESSION['antrian_meja_fase'] ?? 1);
    if (!$meja_id) { $redir_antrian('page=antrian'); }

    // Helper: ambil nomor menunggu berikutnya (fase digabung — semua nomor satu antrian)
    $ambilBerikutnya = function() use ($conn, $meja_id, $today) {
        // Meja yang di-pause tidak memanggil nomor baru
        try {
            $pchk = $conn->prepare("SELECT is_paused FROM meja WHERE id=?");
            $pchk->execute([$meja_id]);
            if ((int)$pchk->fetchColumn() === 1) return null;
        } catch (Throwable) {} // kolom is_paused belum ada → anggap tidak pause
        $stmt = $conn->prepare("SELECT id FROM antrian
            WHERE tanggal=? AND status='menunggu'
            ORDER BY nomor ASC LIMIT 1 FOR UPDATE");
        $stmt->execute([$today]);
        $next = $stmt->fetch();
        if ($next) {
            $conn->prepare("UPDATE antrian SET meja_id=?, status='dipanggil', dipanggil_at=NOW(3) WHERE id=?")
                 ->execute([$meja_id, $next['id']]);
        }
        return $next;
    };

    // ── SELESAI: pelayanan selesai. Cetak bukti kini MANUAL (tombol Cetak Bukti),
    //    tidak lagi auto-print di sini. ('lulus' = alias lama, perilaku sama) ──────
    if ($action === 'selesai' || $action === 'lulus') {
        $cur_id    = (int)$_POST['antrian_id'];
        $back_page = in_array($_POST['redirect_to'] ?? '', ['pendaftar','antrian']) ? $_POST['redirect_to'] : 'antrian';
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE antrian SET status='selesai', hasil='lulus', selesai_at=NOW()
                            WHERE id=? AND meja_id=? AND status='dipanggil'")
                 ->execute([$cur_id, $meja_id]);
            $ambilBerikutnya();
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        $redir_antrian('page=' . $back_page);
    }

    // ── GAGAL: berkas tidak lengkap ───────────────────────────────────────────
    if ($action === 'gagal') {
        $cur_id = (int)$_POST['antrian_id'];
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE antrian SET status='selesai', hasil='gagal', selesai_at=NOW()
                            WHERE id=? AND meja_id=? AND status='dipanggil'")
                 ->execute([$cur_id, $meja_id]);
            $ambilBerikutnya();
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        $redir_antrian('page=antrian');
    }

    // ── SKIP ──────────────────────────────────────────────────────────────────
    if ($action === 'skip') {
        $cur_id    = (int)$_POST['antrian_id'];
        $back_page = in_array($_POST['redirect_to'] ?? '', ['pendaftar','antrian']) ? $_POST['redirect_to'] : 'antrian';
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE antrian SET status='skip', selesai_at=NOW()
                            WHERE id=? AND meja_id=? AND status='dipanggil'")
                 ->execute([$cur_id, $meja_id]);
            $ambilBerikutnya();
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        $redir_antrian('page=' . $back_page);
    }

    // ── Mulai (panggil nomor pertama) ─────────────────────────────────────────
    if ($action === 'mulai') {
        $back_page = in_array($_POST['redirect_to'] ?? '', ['pendaftar','antrian']) ? $_POST['redirect_to'] : 'antrian';
        $conn->beginTransaction();
        try {
            $cek = $conn->prepare("SELECT id FROM antrian WHERE tanggal=? AND meja_id=? AND status='dipanggil'");
            $cek->execute([$today, $meja_id]);
            if (!$cek->fetch()) $ambilBerikutnya();
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        $redir_antrian('page=' . $back_page);
    }

    // ── Panggil Ulang: update dipanggil_at agar display re-announce ─────────────
    if ($action === 'recall') {
        $cur_id    = (int)$_POST['antrian_id'];
        $back_page = in_array($_POST['redirect_to'] ?? '', ['pendaftar','antrian']) ? $_POST['redirect_to'] : 'antrian';
        $conn->prepare("UPDATE antrian SET dipanggil_at=NOW() WHERE id=? AND meja_id=? AND status='dipanggil'")
             ->execute([$cur_id, $meja_id]);
        $redir_antrian('page=' . $back_page);
    }

    // ── Hubungkan / lepas pendaftar dari nomor antrian ────────────────────────
    if ($action === 'link_pendaftar') {
        $cur_id  = (int)$_POST['antrian_id'];
        $pend_id = (int)$_POST['pendaftar_id'];
        $conn->prepare("UPDATE antrian SET pendaftar_id=? WHERE id=?")
             ->execute([$pend_id ?: null, $cur_id]);
        $redir_antrian('page=antrian');
    }
}

// ── State saat ini ────────────────────────────────────────────────────────────
$my_meja_id   = (int)($_SESSION['antrian_meja_id'] ?? 0);
$my_meja_fase = (int)($_SESSION['antrian_meja_fase'] ?? 1);
$my_meja      = null;
$current      = null;
$current_pendaftar = null;
$berkas_auto  = ['kk' => false, 'tka' => false, 'akta' => false, 'buta_warna' => false];
$sisa_fase1 = 0; $sisa_fase2 = 0;
$selesai_fase1 = 0; $selesai_fase2 = 0;
$total_hari = 0;
$antrian_dibuka = false;

if ($my_meja_id) {
    foreach ($mejas_aktif as $m) {
        if ((int)$m['id'] === $my_meja_id) { $my_meja = $m; break; }
    }
    if (!$my_meja) { unset($_SESSION['antrian_meja_id'], $_SESSION['antrian_meja_fase']); $my_meja_id = 0; }
}

$lulus_fase1 = 0;
$gagal_fase1 = 0;
try {
    // Fase digabung — semua statistik dihitung tanpa filter fase
    $s_total = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=?"); $s_total->execute([$today]);
    $total_hari = (int)$s_total->fetchColumn();
    $antrian_dibuka = $total_hari > 0;

    if ($my_meja_id && $antrian_dibuka) {
        $cStmt = $conn->prepare("SELECT * FROM antrian WHERE tanggal=? AND meja_id=? AND status='dipanggil'
            ORDER BY dipanggil_at DESC LIMIT 1");
        $cStmt->execute([$today, $my_meja_id]);
        $current = $cStmt->fetch();
    }

    // Muat data pendaftar yang terhubung ke nomor aktif
    if ($current && !empty($current['pendaftar_id'])) {
        $ps = $conn->prepare("SELECT * FROM pendaftar WHERE id=?");
        $ps->execute([$current['pendaftar_id']]);
        $current_pendaftar = $ps->fetch() ?: null;
        // Auto-centang checklist berkas dari data pendaftar yang sudah terisi
        if ($current_pendaftar) {
            $berkas_auto['kk']         = !empty($current_pendaftar['tgl_kk']) && $current_pendaftar['tgl_kk'] <= '2025-06-15';
            $berkas_auto['tka']        = (float)($current_pendaftar['nilai_tka'] ?? 0) > 0;
            $berkas_auto['buta_warna'] = in_array($current_pendaftar['buta_warna'] ?? 'belum', ['normal','buta_warna_parsial','buta_warna_total'], true);
            // 'akta' tidak punya sumber data → tetap manual
        }
    }

    $s1 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND status='menunggu'");  $s1->execute([$today]); $sisa_fase1    = (int)$s1->fetchColumn();
    $s3 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND status='selesai'");   $s3->execute([$today]); $selesai_fase1 = (int)$s3->fetchColumn();
    $s5 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND hasil='lulus'");      $s5->execute([$today]); $lulus_fase1   = (int)$s5->fetchColumn();
    $s6 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND hasil='gagal'");      $s6->execute([$today]); $gagal_fase1   = (int)$s6->fetchColumn();
    $sisa_fase2 = 0; $selesai_fase2 = $selesai_fase1; // kompatibilitas variabel lama
} catch(Throwable) {}


// Stat per meja untuk board
$meja_stat = [];
try {
    $ms = $conn->prepare("SELECT m.id, m.nomor_meja, m.nama, m.fase,
        (SELECT a.nomor FROM antrian a WHERE a.tanggal=? AND a.meja_id=m.id AND a.status='dipanggil'
         ORDER BY a.dipanggil_at DESC LIMIT 1) AS nomor_aktif,
        (SELECT COUNT(*) FROM antrian a WHERE a.tanggal=? AND a.meja_id=m.id AND a.status='selesai') AS selesai_count
        FROM meja m WHERE m.is_active=1 ORDER BY m.nomor_meja");
    $ms->execute([$today, $today]);
    $meja_stat = $ms->fetchAll();
} catch(Throwable) {}

// Preview nomor berikutnya
$next_up = [];
if ($my_meja_id) {
    try {
        $nu = $conn->prepare("SELECT nomor FROM antrian
            WHERE tanggal=? AND status='menunggu' ORDER BY nomor ASC LIMIT 3");
        $nu->execute([$today]);
        $next_up = $nu->fetchAll(PDO::FETCH_COLUMN);
    } catch(Throwable) {}
}

// History pengisian data untuk meja ini — tabel seperti Data Pendaftar,
// otomatis terfilter ke loket ini & urut terbaru di atas.
$riwayat_pendaftar = [];
$riwayat_limit = 100;
if ($my_meja_id) {
    try {
        $rpq = $conn->prepare("SELECT p.*,
                (SELECT a.nomor FROM antrian a
                   WHERE a.pendaftar_id=p.id AND a.meja_id=?
                   ORDER BY a.fase DESC, a.id DESC LIMIT 1) AS antri_nomor
            FROM pendaftar p
            WHERE EXISTS (SELECT 1 FROM antrian a2 WHERE a2.pendaftar_id=p.id AND a2.meja_id=?)
            ORDER BY p.id DESC
            LIMIT $riwayat_limit");
        $rpq->execute([$my_meja_id, $my_meja_id]);
        $riwayat_pendaftar = $rpq->fetchAll();
    } catch (PDOException) {}
}

// Cek buta warna: sembunyikan dari checklist hanya jika gelombang aktif set buta_warna_wajib=0
$show_buta_warna = true;
try {
    $ga_bw = getActiveGelombang($conn);
    if ($ga_bw && isset($ga_bw['buta_warna_wajib']) && (int)$ga_bw['buta_warna_wajib'] === 0) {
        $show_buta_warna = false;
    }
} catch(Throwable) {}

$fase_color = [1 => '#2563eb', 2 => '#7c3aed'];
?>

<style>
.meja-pick-card {
    border: 2px solid #e5e7eb; border-radius: 16px; padding: 24px 20px;
    text-align: center; cursor: pointer; transition: all .2s; background: #fff;
    width: 100%;
}
.meja-pick-card:hover:not(:disabled) { border-color: #7c3aed; box-shadow: 0 8px 24px rgba(124,58,237,.12); transform: translateY(-3px); }
.meja-pick-card .meja-num { font-size: 2.2rem; font-weight: 800; line-height: 1; }

.nomor-display { font-size: 7rem; font-weight: 900; line-height: 1; letter-spacing: -4px; }
.nomor-box {
    display: inline-block; border-radius: 24px; padding: 28px 48px;
    border: 3px solid;
}
.nomor-box.fase-1 { background: #eff6ff; border-color: #93c5fd; }
.nomor-box .nomor-display.fase-1 { color: #1d4ed8; }
.nomor-box.fase-2 { background: #f5f3ff; border-color: #c4b5fd; }
.nomor-box .nomor-display.fase-2 { color: #6d28d9; }

.fase-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 20px; font-size: .78rem; font-weight: 700; }
.fase-badge-1 { background: #dbeafe; color: #1d4ed8; }
.fase-badge-2 { background: #ede9fe; color: #6d28d9; }

.btn-aksi-main {
    border: 0; border-radius: 14px; padding: 16px 32px;
    font-size: 1.1rem; font-weight: 700; transition: all .2s; color: #fff;
    box-shadow: 0 6px 20px rgba(0,0,0,.15);
}
.btn-aksi-main:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,0,0,.2); color: #fff; }
.btn-lulus  { background: linear-gradient(135deg, #059669, #10b981); }
.btn-gagal  { background: linear-gradient(135deg, #dc2626, #f87171); }
.btn-selesai { background: linear-gradient(135deg, #7c3aed, #a855f7); }
.btn-start  { background: linear-gradient(135deg, #2563eb, #60a5fa); }

.stat-pill { display: inline-flex; align-items: center; gap: 8px; padding: 7px 16px; border-radius: 40px; font-weight: 600; font-size: .85rem; }
.meja-mini-card { border-radius: 12px; padding: 14px; text-align: center; border: 2px solid #e5e7eb; }
.meja-mini-card.active-meja { border-color: #7c3aed; background: #f5f3ff; }
.nomor-mini { font-size: 1.6rem; font-weight: 800; }
.no-antrian-box { border-radius: 20px; padding: 50px 40px; text-align: center; }

/* ── Fase 2 Panel Kanan ───────────────────────────────── */
.f2p-berkas-row {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1.5px solid #e5e7eb;
    border-radius: 10px; padding: 10px 12px; cursor: pointer;
    transition: border-color .15s, background .15s;
}
.f2p-berkas-row.checked { border-color: #a855f7 !important; background: #f5f0ff !important; }
/* Mobile/tablet: panel tidak sticky, ikut mengalir di bawah nomor */
@media (max-width: 991.98px) {
    .f2-panel { position: static !important; }
    .nomor-display { font-size: 4.5rem; letter-spacing: -2px; }
    .nomor-box { padding: 20px 28px; }
    .btn-aksi-main { padding: 13px 20px; font-size: .95rem; width: 100%; }
}

/* ── Fase 2 Checklist ─────────────────────────────────── */
.fase2-checklist { display: flex; flex-direction: column; gap: 12px; }
.fase2-step {
    display: flex; align-items: center; gap: 14px;
    background: #fff; border: 1.5px solid #e5e7eb;
    border-radius: 14px; padding: 14px 16px;
    transition: border-color .2s, background .2s;
}
.fase2-step:has(.fase2-cb:checked) { border-color: #a855f7; background: #faf5ff; }
.fase2-step-icon {
    width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
}
.fase2-step-body { flex: 1; text-align: left; }
.fase2-step-check { flex-shrink: 0; }

/* ── Fase 2 Sidebar (Offcanvas) ───────────────────────── */
.offcanvas-pendaftar { width: 380px !important; }
@media (max-width: 480px) { .offcanvas-pendaftar { width: 100vw !important; } }
.panel-pendaftar-header {
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #7c3aed; margin-bottom: 14px;
    display: flex; align-items: center; gap: 6px;
}
.panel-sidebar-btn {
    position: fixed; right: 20px; bottom: 110px; z-index: 1040;
    background: linear-gradient(135deg, #7c3aed, #a855f7);
    color: #fff; border: 0; border-radius: 50px;
    padding: 11px 18px; font-weight: 700; font-size: .85rem;
    box-shadow: 0 4px 16px rgba(124,58,237,.35);
    display: flex; align-items: center; gap: 8px; cursor: pointer;
    transition: box-shadow .2s, transform .2s;
    white-space: nowrap; max-width: 220px; overflow: hidden; text-overflow: ellipsis;
}
.panel-sidebar-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(124,58,237,.45); color: #fff; }
.panel-sidebar-btn.linked { background: linear-gradient(135deg, #059669, #10b981); box-shadow: 0 4px 16px rgba(5,150,105,.3); }
.berkas-row {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1.5px solid #e5e7eb;
    border-radius: 10px; padding: 10px 12px; cursor: pointer;
    transition: border-color .15s, background .15s;
}
.berkas-row.checked { border-color: #a855f7 !important; background: #f5f0ff !important; }

@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
}
.print-only { display: none; }
</style>

<?php if (!$my_meja_id): ?>
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- PILIH MEJA                                                                -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="text-center mb-4">
    <h4 class="fw-bold" style="color:#1e1b4b;">Pilih Meja Anda Hari Ini</h4>
    <p class="text-muted mb-1">Klik meja tempat Anda bertugas sekarang — setiap meja melayani cek berkas + input data sekaligus</p>
    <div class="small text-muted"><?= date('l, d F Y') ?> &nbsp;|&nbsp;
        Antrian: <?= $antrian_dibuka ? "<strong>$total_hari nomor</strong>, <strong>$sisa_fase1 menunggu</strong>" : '<span class="text-danger fw-semibold">Belum dibuka</span>' ?>
    </div>
</div>

<?php if (!$antrian_dibuka): ?>
<div class="alert alert-warning text-center">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Antrian hari ini belum dibuka. Super Admin perlu membuka antrian di
    <strong>Super Admin Panel → Kelola Meja Antrian → Buka Antrian Hari Ini</strong>.
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($mejas_aktif as $m):
        $nomor_aktif = null;
        foreach ($meja_stat as $ms2) { if ((int)$ms2['id'] === (int)$m['id'] && $ms2['nomor_aktif']) { $nomor_aktif = $ms2['nomor_aktif']; break; } }
    ?>
    <div class="col-6 col-md-3">
        <form method="POST">
            <input type="hidden" name="action" value="pilih_meja">
            <input type="hidden" name="meja_id" value="<?= $m['id'] ?>">
            <button class="meja-pick-card" type="submit" <?= !$antrian_dibuka && !$nomor_aktif ? 'disabled' : '' ?>>
                <i class="bi bi-person-workspace d-block mb-2" style="font-size:1.8rem;color:#7c3aed;"></i>
                <div class="meja-num" style="color:#7c3aed;">Meja <?= $m['nomor_meja'] ?></div>
                <?php if ($m['nama']): ?><div class="text-muted small"><?= htmlspecialchars($m['nama']) ?></div><?php endif; ?>
                <div class="mt-2">
                    <?= $nomor_aktif
                        ? "<span class='badge bg-warning text-dark'>Melayani #$nomor_aktif</span>"
                        : ($antrian_dibuka ? "<span class='badge bg-success'>Siap</span>" : "<span class='badge bg-secondary'>Menunggu</span>") ?>
                </div>
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($mejas_aktif)): ?>
<div class="text-center text-muted py-5">
    <i class="bi bi-grid-3x2-gap fs-1 d-block mb-2 opacity-40"></i>
    Belum ada meja aktif. Superadmin perlu menambahkan meja.
</div>
<?php endif; ?>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- TAMPILAN MEJA                                                             -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2 no-print">
    <div class="d-flex align-items-center gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="color:#1e1b4b;">
                Meja <?= $my_meja['nomor_meja'] ?>
                <?php if ($my_meja['nama']): ?><small class="fw-normal text-muted">— <?= htmlspecialchars($my_meja['nama']) ?></small><?php endif; ?>
            </h4>
            <span class="fase-badge fase-badge-2">
                <i class="bi bi-person-workspace"></i>
                Meja Pendaftaran — Cek Berkas &amp; Input Data
            </span>
        </div>
    </div>
    <form method="POST" class="d-inline">
        <input type="hidden" name="action" value="ganti_meja">
        <button type="submit" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left-right me-1"></i>Ganti Meja
        </button>
    </form>
</div>

<!-- Stat Bar -->
<div class="d-flex gap-2 flex-wrap mb-4 no-print">
    <span class="stat-pill" style="background:#ede9fe;color:#6d28d9;">
        <i class="bi bi-hourglass-split"></i> <?= $sisa_fase1 ?> menunggu
    </span>
    <span class="stat-pill" style="background:#d1fae5;color:#065f46;">
        <i class="bi bi-check-circle"></i> <?= $lulus_fase1 ?> selesai &amp; bukti terbit
    </span>
</div>

<?php if (!$antrian_dibuka): ?>
<!-- Antrian belum dibuka -->
<div class="no-antrian-box no-print" style="background:#f5f3ff;border:2px dashed #c4b5fd;">
    <i class="bi bi-pause-circle fs-1 d-block mb-3" style="color:#7c3aed;opacity:.5;"></i>
    <h5 class="fw-semibold">Antrian Hari Ini Belum Dibuka</h5>
    <p class="text-muted mb-0">Superadmin perlu membuka antrian terlebih dahulu.</p>
</div>

<?php elseif ($current): ?>
<!-- ══ ADA NOMOR AKTIF — LAYOUT 2 KOLOM (cek berkas + input data digabung) ══ -->
<div class="row g-4 mb-4">
    <!-- Kolom kiri: nomor + aksi utama -->
    <div class="col-lg-7">
        <div class="text-center">
            <div class="text-muted small fw-semibold mb-2 text-uppercase letter-spacing-1">Sedang Dilayani</div>
            <div class="nomor-box fase-2 mb-3">
                <div class="nomor-display fase-2">SSG<?= str_pad($current['nomor'], 3, '0', STR_PAD_LEFT) ?></div>
            </div>
            <div class="text-muted small mb-4">
                Dipanggil <?= date('H:i:s', strtotime($current['dipanggil_at'])) ?>
                · <span id="timer" data-start="<?= time() - strtotime($current['dipanggil_at']) ?>">...</span> yang lalu
            </div>

            <div class="d-flex gap-3 justify-content-center flex-wrap mb-3">
                <form method="POST">
                    <input type="hidden" name="action" value="selesai">
                    <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
                    <input type="hidden" name="nomor" value="<?= $current['nomor'] ?>">
                    <?php $selesai_confirm = $current_pendaftar
                        ? 'Selesaikan pelayanan SSG' . str_pad($current['nomor'], 3, '0', STR_PAD_LEFT) . '? Pastikan bukti sudah dicetak dan diberikan ke pendaftar.'
                        : 'PERHATIAN: nomor ini belum terhubung ke pendaftar. Selesaikan tanpa data?'; ?>
                    <button type="submit" class="btn-aksi-main btn-selesai" id="btnSelesai"
                            style="font-size:1.3rem; padding:20px 56px; border-radius:16px;"
                            onclick="return trySelesai(<?= $current['id'] ?>, '<?= $selesai_confirm ?>', <?= $current_pendaftar ? 'true' : 'false' ?>)">
                        <i class="bi bi-check-circle-fill me-2"></i>Selesaikan Pelayanan
                    </button>
                </form>
            </div>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <form method="POST">
                    <input type="hidden" name="action" value="recall">
                    <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
                    <input type="hidden" name="redirect_to" value="antrian">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-megaphone-fill me-1"></i>Panggil Ulang
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="action" value="skip">
                    <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
                    <button type="submit" class="btn btn-outline-warning"
                            onclick="return confirm('Lewati nomor <?= $current['nomor'] ?>? (Tidak hadir)')">
                        <i class="bi bi-forward-fill me-1"></i>Skip (Tidak Hadir)
                    </button>
                </form>
            </div>

            <?php if (!empty($next_up)): ?>
            <div class="text-muted small mt-4">
                <i class="bi bi-skip-forward me-1"></i>Berikutnya:
                <?php foreach ($next_up as $nu_n): ?>
                <span class="badge bg-light text-dark border ms-1">SSG<?= str_pad($nu_n, 3, '0', STR_PAD_LEFT) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kolom kanan: panel pendaftar & berkas (sticky) -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm f2-panel" style="position:sticky;top:80px;border-radius:16px;overflow:hidden;">
            <div class="card-header border-0 fw-semibold"
                 style="background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;">
                <i class="bi bi-person-lines-fill me-2"></i>Panel Pendaftar — SSG<?= str_pad($current['nomor'], 3, '0', STR_PAD_LEFT) ?>
            </div>
            <div class="card-body" style="background:#faf5ff;">

                <?php if ($current_pendaftar): ?>
                <!-- Pendaftar terhubung -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($current_pendaftar['nama']) ?></div>
                                <?php if (!empty($current_pendaftar['nisn'])): ?>
                                <div class="text-muted small"><i class="bi bi-hash me-1"></i>NISN: <?= htmlspecialchars($current_pendaftar['nisn']) ?></div>
                                <?php endif; ?>
                                <div class="text-muted small"><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($current_pendaftar['jurusan']) ?></div>
                            </div>
                            <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        </div>
                        <?php $f2sb = STATUS_BADGE[$current_pendaftar['status']] ?? 'bg-secondary';
                              $f2sl = STATUS_LABEL[$current_pendaftar['status']] ?? $current_pendaftar['status']; ?>
                        <div class="mt-2"><span class="badge <?= $f2sb ?>"><?= $f2sl ?></span></div>
                    </div>
                </div>
                <?php $cur_print = $current_pendaftar;
                      $cur_print['_antrian'] = [
                          'nomor' => 'SSG' . str_pad($current['nomor'], 3, '0', STR_PAD_LEFT),
                          'meja'  => ($my_meja['nama'] ?? '') ?: 'Loket ' . ($my_meja['nomor_meja'] ?? ''),
                      ]; ?>
                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="btn btn-sm fw-semibold text-white" style="background:linear-gradient(135deg,#0891b2,#06b6d4);"
                            onclick='printBukti(<?= json_encode($cur_print, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>
                        <i class="bi bi-printer me-1"></i>Cetak Bukti
                    </button>
                    <a href="?page=pendaftar" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i>Edit Data Pendaftar
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="unlinkPendaftar(<?= $current['id'] ?>)">
                        <i class="bi bi-arrow-left-right me-1"></i>Ganti Pendaftar
                    </button>
                </div>

                <?php else: ?>
                <!-- Belum terhubung -->
                <div class="text-center py-3 px-2 mb-3" style="background:#fff;border-radius:12px;">
                    <div style="width:54px;height:54px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="bi bi-person-plus-fill" style="font-size:1.6rem;color:#7c3aed;"></i>
                    </div>
                    <div class="fw-semibold mb-1">Belum Ada Data Pendaftar</div>
                    <div class="small text-muted mb-3">Daftarkan di halaman Data Pendaftar —<br>otomatis terhubung ke nomor ini.</div>
                    <a href="?page=pendaftar&add=1" class="btn btn-sm fw-semibold text-white px-4"
                       style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
                        <i class="bi bi-plus-lg me-1"></i>Daftarkan Sekarang
                    </a>
                </div>
                <?php endif; ?>

                <!-- Checklist Berkas -->
                <div class="small fw-semibold text-uppercase mb-2" style="color:#7c3aed;letter-spacing:.3px;">
                    <i class="bi bi-card-checklist me-1"></i>Cek Berkas
                </div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                <?php
                $f2_berkas = [
                    'kk'   => ['Kartu Keluarga (KK)', 'bi-house-fill',         '#dbeafe', '#1d4ed8'],
                    'tka'  => ['Hasil Tes TKA',       'bi-file-earmark-text',  '#d1fae5', '#065f46'],
                    'akta' => ['Akta Kelahiran',      'bi-calendar-event',     '#fef3c7', '#92400e'],
                ];
                if ($show_buta_warna) $f2_berkas['buta_warna'] = ['Tes Buta Warna', 'bi-eye', '#fce7f3', '#9d174d'];
                // Langkah terakhir: konfirmasi bukti sudah dicetak & diserahkan (centang, bukan tombol print)
                $f2_berkas['bukti'] = ['Bukti sudah dicetak & diberikan ke pendaftar', 'bi-printer-fill', '#ede9fe', '#6d28d9'];
                foreach ($f2_berkas as $bk => [$bl, $bi, $bbg, $bco]): ?>
                <label class="f2p-berkas-row" id="berkasRow_<?= $bk ?>"
                       onclick="toggleBerkas(<?= (int)$current['id'] ?>, '<?= $bk ?>', this)">
                    <div style="width:32px;height:32px;border-radius:8px;background:<?= $bbg ?>;color:<?= $bco ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi <?= $bi ?>"></i>
                    </div>
                    <span class="small flex-grow-1"><?= $bl ?></span>
                    <i class="bi bi-circle" id="berkasIco_<?= $bk ?>" style="color:#d1d5db;"></i>
                </label>
                <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ══ BELUM ADA NOMOR AKTIF ════════════════════════════════════════════════ -->
<div class="no-antrian-box no-print" style="background:#f5f3ff;border:2px dashed #c4b5fd;">
    <i class="bi bi-arrow-right-circle fs-1 d-block mb-3" style="color:#7c3aed;opacity:.6;"></i>
    <h5 class="fw-semibold">
        <?= $sisa_fase1 > 0 ? 'Siap Melayani' : 'Antrian Habis' ?>
    </h5>
    <p class="text-muted mb-3">
        <?= $sisa_fase1 > 0
            ? 'Klik tombol di bawah untuk memanggil nomor berikutnya.'
            : 'Semua antrian sudah selesai.' ?>
    </p>
    <?php if ($sisa_fase1 > 0): ?>
    <form method="POST">
        <input type="hidden" name="action" value="mulai">
        <button type="submit" class="btn-aksi-main btn-start">
            <i class="bi bi-play-fill me-2"></i>Panggil Nomor Berikutnya
        </button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══ RIWAYAT PENGISIAN DATA — MEJA INI (tabel seperti Data Pendaftar) ═══════ -->
<div class="card mt-4 no-print">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-semibold">
            <i class="bi bi-clock-history me-2"></i>Riwayat Pengisian Data — Meja Anda
            <span class="badge bg-secondary ms-1"><?= count($riwayat_pendaftar) ?></span>
            <span class="text-muted small ms-1 fw-normal">terbaru di atas</span>
        </span>
        <a href="?page=pendaftar&loket=<?= $my_meja_id ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i>Buka di Data Pendaftar
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>No. Daftar</th><th>Nama</th><th class="text-center">Antrian</th>
                    <th>NISN</th><th class="text-center">L/P</th><th>Jurusan</th>
                    <th class="text-center">Glm</th><th class="text-center">Raport</th>
                    <th class="text-center">TKA</th><th class="text-center">Nilai Akhir</th>
                    <th class="text-center">Usia</th><th>Status</th><th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($riwayat_pendaftar)): ?>
                <tr><td colspan="13" class="text-center py-4 text-muted">Belum ada pendaftar yang datanya diisi dari meja ini.</td></tr>
            <?php else: foreach ($riwayat_pendaftar as $rp):
                $rp_badge = STATUS_BADGE[$rp['status']] ?? 'bg-warning text-dark';
                $rp_gugur = empty($rp['lolos_usia']);
                // Data untuk cetak bukti (sertakan info loket/antrian)
                $rp_print = $rp;
                $rp_print['_antrian'] = $rp['antri_nomor'] !== null ? [
                    'nomor' => 'SSG' . str_pad($rp['antri_nomor'], 3, '0', STR_PAD_LEFT),
                    'meja'  => ($my_meja['nama'] ?? '') ?: 'Loket ' . ($my_meja['nomor_meja'] ?? ''),
                ] : null;
            ?>
                <tr class="<?= $rp_gugur ? 'table-secondary text-muted' : '' ?>">
                    <td><?= htmlspecialchars($rp['no_pendaftaran']) ?></td>
                    <td>
                        <?= htmlspecialchars($rp['nama']) ?>
                        <?php if ($rp_gugur): ?><i class="bi bi-exclamation-circle text-danger" title="Gugur: usia > 21"></i><?php endif; ?>
                        <?php if (($rp['sistem_pendidikan'] ?? '') === 'khusus'): ?>
                          <span class="badge bg-warning text-dark ms-1">Khusus</span>
                        <?php elseif (($rp['sistem_pendidikan'] ?? '') === 'pkbm'): ?>
                          <span class="badge bg-info text-dark ms-1">PKBM</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($rp['antri_nomor'] !== null): ?>
                            <span class="badge bg-secondary-subtle text-secondary-emphasis">SSG<?= str_pad($rp['antri_nomor'], 3, '0', STR_PAD_LEFT) ?></span>
                        <?php else: ?><span class="text-muted">&mdash;</span><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($rp['nisn']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($rp['jenis_kelamin']) ?></td>
                    <td><?= JURUSAN_SHORT[$rp['jurusan']] ?? htmlspecialchars($rp['jurusan']) ?></td>
                    <td class="text-center"><?= (int)$rp['gelombang'] ?></td>
                    <td class="text-center"><?= number_format($rp['nilai_raport'], 2) ?></td>
                    <td class="text-center"><?= number_format($rp['nilai_tka'], 2) ?></td>
                    <td class="text-center fw-bold text-success"><?= number_format($rp['nilai_akhir'], 2) ?></td>
                    <td class="text-center"><?= (int)$rp['usia'] ?></td>
                    <td><span class="badge <?= $rp_badge ?>"><?= STATUS_LABEL[$rp['status']] ?? ucfirst($rp['status']) ?></span></td>
                    <td class="text-end" style="white-space:nowrap;">
                        <button type="button" class="btn btn-sm btn-outline-info py-0 px-2 me-1" onclick='printBukti(<?= json_encode($rp_print, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Cetak Bukti"><i class="bi bi-printer"></i></button>
                        <a href="?page=pendaftar&edit_id=<?= $rp['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2 me-1" title="Buka & edit di Data Pendaftar"><i class="bi bi-pencil"></i></a>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Hapus pendaftar ini? Detail raport akan ikut terhapus.')">
                            <input type="hidden" name="action" value="delete_pendaftar">
                            <input type="hidden" name="pendaftar_id" value="<?= $rp['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus Pendaftar"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php if (count($riwayat_pendaftar) >= $riwayat_limit): ?>
    <div class="card-footer small text-muted">
        Menampilkan <?= $riwayat_limit ?> data terbaru. <a href="?page=pendaftar&loket=<?= $my_meja_id ?>">Lihat semua di Data Pendaftar &raquo;</a>
    </div>
    <?php endif; ?>
</div>

<!-- ══ BOARD SEMUA MEJA ═══════════════════════════════════════════════════ -->
<?php if (!empty($meja_stat)): ?>
<div class="card mt-4 no-print">
    <div class="card-header">
        <i class="bi bi-grid me-2"></i>Status Semua Meja Hari Ini
    </div>
    <div class="card-body">
        <?php
        ?>
        <div class="row g-2">
            <?php foreach ($meja_stat as $ms2): ?>
            <div class="col-6 col-md-3">
                <div class="meja-mini-card <?= (int)$ms2['id'] === $my_meja_id ? 'active-meja' : '' ?>">
                    <div class="small text-muted">Meja <?= $ms2['nomor_meja'] ?></div>
                    <?php if ($ms2['nomor_aktif']): ?>
                        <div class="nomor-mini" style="color:#7c3aed;">SSG<?= str_pad($ms2['nomor_aktif'], 3, '0', STR_PAD_LEFT) ?></div>
                        <div class="small" style="color:#7c3aed;">Dilayani</div>
                    <?php else: ?>
                        <div class="nomor-mini" style="color:#d1d5db;">—</div>
                        <div class="small text-muted">Siap</div>
                    <?php endif; ?>
                    <div class="small text-success"><i class="bi bi-check"></i><?= $ms2['selesai_count'] ?> selesai</div>
                    <?php if ((int)$ms2['id'] === $my_meja_id): ?><div class="small fw-bold" style="color:#7c3aed;">← Meja Anda</div><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; // end my_meja_id ?>



<script>
// Timer
const timerEl = document.getElementById('timer');
if (timerEl) {
    let elapsed = parseInt(timerEl.dataset.start || 0);
    const fmt = (s) => { const m = Math.floor(s/60); return (m>0?m+' mnt ':'')+s%60+' dtk'; };
    timerEl.textContent = fmt(elapsed);
    setInterval(() => timerEl.textContent = fmt(++elapsed), 1000);
}
// Auto-refresh 30 detik
setTimeout(() => location.reload(), 30000);

// ── Berkas Checklist (localStorage) ─────────────────────────────────────────
function berkasKey(id) { return 'berkas_antrian_' + id; }
function loadBerkas(id) {
    try { return JSON.parse(localStorage.getItem(berkasKey(id)) || '{}'); } catch { return {}; }
}
function toggleBerkas(antrianId, key, rowEl) {
    const data = loadBerkas(antrianId);
    data[key] = !data[key];
    localStorage.setItem(berkasKey(antrianId), JSON.stringify(data));
    applyBerkasRow(key, data[key]);
}
function applyBerkasRow(key, checked) {
    const row = document.getElementById('berkasRow_' + key);
    const ico = document.getElementById('berkasIco_' + key);
    if (row) row.classList.toggle('checked', !!checked);
    if (ico) { ico.className = checked ? 'bi bi-check-circle-fill text-success' : 'bi bi-circle'; ico.style.color = checked ? '' : '#d1d5db'; }
}
// Default tercentang dari data pendaftar (KK/TKA/Buta Warna). localStorage menang
// agar petugas tetap bisa meng-uncheck secara manual.
const BERKAS_AUTO = <?= json_encode($berkas_auto) ?>;
function initBerkas(antrianId) {
    const data = loadBerkas(antrianId);
    ['kk','tka','akta','buta_warna','bukti'].forEach(k => {
        const checked = (data[k] !== undefined) ? !!data[k] : !!BERKAS_AUTO[k];
        applyBerkasRow(k, checked);
    });
}
function clearBerkas(antrianId) {
    localStorage.removeItem(berkasKey(antrianId));
}
// Gerbang Selesai: pengaman 1 = wajib centang "Bukti sudah dicetak"; pengaman 2 = popup konfirmasi
function trySelesai(antrianId, msg, requireBukti) {
    if (requireBukti) {
        const data = loadBerkas(antrianId);
        if (!data.bukti) {
            alert('Centang dulu "Bukti sudah dicetak & diberikan ke pendaftar" di Cek Berkas sebelum menyelesaikan.');
            return false;
        }
    }
    if (!confirm(msg)) return false;
    clearBerkas(antrianId);
    return true;
}
function unlinkPendaftar(antrianId) {
    if (!confirm('Ganti pendaftar yang terhubung ke nomor ini?')) return;
    const f = document.createElement('form');
    f.method = 'POST';
    f.innerHTML = '<input name="action" value="link_pendaftar">'
                + '<input name="antrian_id" value="' + antrianId + '">'
                + '<input name="pendaftar_id" value="0">';
    document.body.appendChild(f);
    f.submit();
}
<?php if ($current): ?>
document.addEventListener('DOMContentLoaded', () => initBerkas(<?= $current['id'] ?>));
<?php endif; ?>
</script>
