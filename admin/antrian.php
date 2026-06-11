<?php
if (empty($_SESSION['admin_id']) && empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger">Akses ditolak.</div>'; return;
}

$today = date('Y-m-d');

$mejas_aktif = $conn->query("SELECT * FROM meja WHERE is_active=1 ORDER BY fase, nomor_meja")->fetchAll();

// Auto-migrate: pendaftar_id di antrian
try { $conn->exec("ALTER TABLE antrian ADD COLUMN pendaftar_id INT NULL AFTER nomor"); } catch (PDOException) {}

// ── POST Handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'pilih_meja') {
        $mid = (int)$_POST['meja_id'];
        $_SESSION['antrian_meja_id']   = $mid;
        foreach ($mejas_aktif as $m) {
            if ($m['id'] === $mid) { $_SESSION['antrian_meja_fase'] = (int)$m['fase']; break; }
        }
        echo '<script>window.location.href="?page=antrian";</script>'; return;
    }

    if ($action === 'ganti_meja') {
        unset($_SESSION['antrian_meja_id'], $_SESSION['antrian_meja_fase']);
        echo '<script>window.location.href="?page=antrian";</script>'; return;
    }

    $meja_id   = (int)($_SESSION['antrian_meja_id'] ?? 0);
    $meja_fase = (int)($_SESSION['antrian_meja_fase'] ?? 1);
    if (!$meja_id) { echo '<script>window.location.href="?page=antrian";</script>'; return; }

    // Helper: ambil nomor berikutnya dari fase tertentu untuk meja ini
    $ambilBerikutnya = function(int $fase) use ($conn, $meja_id, $today) {
        $stmt = $conn->prepare("SELECT id FROM antrian
            WHERE tanggal=? AND fase=? AND status='menunggu'
            ORDER BY nomor ASC LIMIT 1 FOR UPDATE");
        $stmt->execute([$today, $fase]);
        $next = $stmt->fetch();
        if ($next) {
            $conn->prepare("UPDATE antrian SET meja_id=?, status='dipanggil', dipanggil_at=NOW() WHERE id=?")
                 ->execute([$meja_id, $next['id']]);
        }
        return $next;
    };

    // ── FASE 1: Lulus → buat entri fase 2 ────────────────────────────────────
    if ($action === 'lulus') {
        $cur_id = (int)$_POST['antrian_id'];
        $nomor  = (int)$_POST['nomor'];
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE antrian SET status='selesai', hasil='lulus', selesai_at=NOW()
                            WHERE id=? AND meja_id=? AND status='dipanggil'")
                 ->execute([$cur_id, $meja_id]);
            $conn->prepare("INSERT IGNORE INTO antrian (tanggal, nomor, fase) VALUES (?,?,2)")
                 ->execute([$today, $nomor]);
            $ambilBerikutnya(1);
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        echo '<script>window.location.href="?page=antrian";</script>'; return;
    }

    // ── FASE 1: Gagal → tolak berkas ─────────────────────────────────────────
    if ($action === 'gagal') {
        $cur_id = (int)$_POST['antrian_id'];
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE antrian SET status='selesai', hasil='gagal', selesai_at=NOW()
                            WHERE id=? AND meja_id=? AND status='dipanggil'")
                 ->execute([$cur_id, $meja_id]);
            $ambilBerikutnya(1);
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        echo '<script>window.location.href="?page=antrian";</script>'; return;
    }

    // ── FASE 2: Selesai ───────────────────────────────────────────────────────
    if ($action === 'selesai') {
        $cur_id    = (int)$_POST['antrian_id'];
        $nomor     = (int)$_POST['nomor'];
        $back_page = in_array($_POST['redirect_to'] ?? '', ['pendaftar','antrian']) ? $_POST['redirect_to'] : 'antrian';
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE antrian SET status='selesai', selesai_at=NOW()
                            WHERE id=? AND meja_id=? AND status='dipanggil'")
                 ->execute([$cur_id, $meja_id]);
            $ambilBerikutnya(2);
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        echo '<script>window.location.href="?page=antrian&surat='.$nomor.'&back='.$back_page.'";</script>'; return;
    }

    // ── SKIP (berlaku semua fase) ─────────────────────────────────────────────
    if ($action === 'skip') {
        $cur_id    = (int)$_POST['antrian_id'];
        $back_page = in_array($_POST['redirect_to'] ?? '', ['pendaftar','antrian']) ? $_POST['redirect_to'] : 'antrian';
        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE antrian SET status='skip', selesai_at=NOW()
                            WHERE id=? AND meja_id=? AND status='dipanggil'")
                 ->execute([$cur_id, $meja_id]);
            $ambilBerikutnya($meja_fase);
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        echo '<script>window.location.href="?page='.$back_page.'";</script>'; return;
    }

    // ── Mulai (panggil nomor pertama) ─────────────────────────────────────────
    if ($action === 'mulai') {
        $back_page = in_array($_POST['redirect_to'] ?? '', ['pendaftar','antrian']) ? $_POST['redirect_to'] : 'antrian';
        $conn->beginTransaction();
        try {
            $cek = $conn->prepare("SELECT id FROM antrian WHERE tanggal=? AND meja_id=? AND status='dipanggil'");
            $cek->execute([$today, $meja_id]);
            if (!$cek->fetch()) $ambilBerikutnya($meja_fase);
            $conn->commit();
        } catch(Throwable $e) { $conn->rollBack(); }
        echo '<script>window.location.href="?page='.$back_page.'";</script>'; return;
    }

    // ── Hubungkan / lepas pendaftar dari antrian Fase 2 ───────────────────────
    if ($action === 'link_pendaftar') {
        $cur_id  = (int)$_POST['antrian_id'];
        $pend_id = (int)$_POST['pendaftar_id'];
        $conn->prepare("UPDATE antrian SET pendaftar_id=? WHERE id=?")
             ->execute([$pend_id ?: null, $cur_id]);
        echo '<script>window.location.href="?page=antrian";</script>'; return;
    }
}

// ── State saat ini ────────────────────────────────────────────────────────────
$my_meja_id   = (int)($_SESSION['antrian_meja_id'] ?? 0);
$my_meja_fase = (int)($_SESSION['antrian_meja_fase'] ?? 1);
$my_meja      = null;
$current      = null;
$current_pendaftar = null;
$sisa_fase1 = 0; $sisa_fase2 = 0;
$selesai_fase1 = 0; $selesai_fase2 = 0;
$total_hari = 0;
$antrian_dibuka = false;
$show_surat  = isset($_GET['surat']) ? (int)$_GET['surat'] : 0;
$surat_back  = in_array($_GET['back'] ?? '', ['pendaftar','antrian']) ? $_GET['back'] : 'antrian';

if ($my_meja_id) {
    foreach ($mejas_aktif as $m) {
        if ((int)$m['id'] === $my_meja_id) { $my_meja = $m; break; }
    }
    if (!$my_meja) { unset($_SESSION['antrian_meja_id'], $_SESSION['antrian_meja_fase']); $my_meja_id = 0; }
}

$lulus_fase1 = 0;
$gagal_fase1 = 0;
try {
    $s_total = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND fase=1"); $s_total->execute([$today]);
    $total_hari = (int)$s_total->fetchColumn();
    $antrian_dibuka = $total_hari > 0;

    if ($my_meja_id && $antrian_dibuka) {
        $cStmt = $conn->prepare("SELECT * FROM antrian WHERE tanggal=? AND meja_id=? AND fase=? AND status='dipanggil'
            ORDER BY dipanggil_at DESC LIMIT 1");
        $cStmt->execute([$today, $my_meja_id, $my_meja_fase]);
        $current = $cStmt->fetch();
    }

    // Jika ada antrian aktif di Fase 2, muat data pendaftar yang terhubung
    if ($current && !empty($current['pendaftar_id'])) {
        $ps = $conn->prepare("SELECT * FROM pendaftar WHERE id=?");
        $ps->execute([$current['pendaftar_id']]);
        $current_pendaftar = $ps->fetch() ?: null;
    }

    $s1 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND fase=1 AND status='menunggu'");   $s1->execute([$today]); $sisa_fase1    = (int)$s1->fetchColumn();
    $s2 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND fase=2 AND status='menunggu'");   $s2->execute([$today]); $sisa_fase2    = (int)$s2->fetchColumn();
    $s3 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND fase=1 AND status='selesai'");    $s3->execute([$today]); $selesai_fase1 = (int)$s3->fetchColumn();
    $s4 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND fase=2 AND status='selesai'");    $s4->execute([$today]); $selesai_fase2 = (int)$s4->fetchColumn();
    $s5 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND fase=1 AND hasil='lulus'");       $s5->execute([$today]); $lulus_fase1   = (int)$s5->fetchColumn();
    $s6 = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND fase=1 AND hasil='gagal'");       $s6->execute([$today]); $gagal_fase1   = (int)$s6->fetchColumn();
} catch(Throwable) {}


// Stat per meja untuk board
$meja_stat = [];
try {
    $ms = $conn->prepare("SELECT m.id, m.nomor_meja, m.nama, m.fase,
        (SELECT a.nomor FROM antrian a WHERE a.tanggal=? AND a.meja_id=m.id AND a.status='dipanggil'
         ORDER BY a.dipanggil_at DESC LIMIT 1) AS nomor_aktif,
        (SELECT COUNT(*) FROM antrian a WHERE a.tanggal=? AND a.meja_id=m.id AND a.status='selesai') AS selesai_count
        FROM meja m WHERE m.is_active=1 ORDER BY m.fase, m.nomor_meja");
    $ms->execute([$today, $today]);
    $meja_stat = $ms->fetchAll();
} catch(Throwable) {}

$fase_label = [1 => 'Cek Berkas', 2 => 'Input Data & Surat'];
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
    <p class="text-muted mb-1">Klik meja tempat Anda bertugas sekarang</p>
    <div class="small text-muted"><?= date('l, d F Y') ?> &nbsp;|&nbsp;
        Antrian: <?= $antrian_dibuka ? "<strong>$total_hari nomor Fase 1</strong>" : '<span class="text-danger fw-semibold">Belum dibuka</span>' ?>
        <?php if ($sisa_fase2 > 0): ?>
            &nbsp;·&nbsp; <span class="text-purple fw-semibold"><?= $sisa_fase2 ?> menunggu Fase 2</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!$antrian_dibuka): ?>
<div class="alert alert-warning text-center">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Antrian Fase 1 hari ini belum dibuka. Super Admin perlu membuka antrian di
    <strong>Super Admin Panel → Kelola Meja Antrian → Buka Antrian Hari Ini</strong>.
</div>
<?php endif; ?>

<?php
$mejas_fase1 = array_filter($mejas_aktif, fn($m) => (int)$m['fase'] === 1);
$mejas_fase2 = array_filter($mejas_aktif, fn($m) => (int)$m['fase'] === 2);
?>

<?php if (!empty($mejas_fase1)): ?>
<div class="mb-2 fw-semibold small text-uppercase" style="color:#2563eb;letter-spacing:.5px;">
    <i class="bi bi-folder-check me-1"></i>Fase 1 — Cek Berkas
</div>
<div class="row g-3 mb-4">
    <?php foreach ($mejas_fase1 as $m):
        $nomor_aktif = null;
        foreach ($meja_stat as $ms2) { if ($ms2['id'] === $m['id'] && $ms2['nomor_aktif']) { $nomor_aktif = $ms2['nomor_aktif']; break; } }
    ?>
    <div class="col-6 col-md-3">
        <form method="POST">
            <input type="hidden" name="action" value="pilih_meja">
            <input type="hidden" name="meja_id" value="<?= $m['id'] ?>">
            <button class="meja-pick-card" type="submit" <?= !$antrian_dibuka?'disabled':'' ?>>
                <i class="bi bi-folder-check d-block mb-2" style="font-size:1.8rem;color:#2563eb;"></i>
                <div class="meja-num" style="color:#2563eb;">Meja <?= $m['nomor_meja'] ?></div>
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
<?php endif; ?>

<?php if (!empty($mejas_fase2)): ?>
<div class="mb-2 fw-semibold small text-uppercase" style="color:#7c3aed;letter-spacing:.5px;">
    <i class="bi bi-person-check me-1"></i>Fase 2 — Input Data & Surat Tanda Daftar
</div>
<div class="row g-3 mb-4">
    <?php foreach ($mejas_fase2 as $m):
        $nomor_aktif = null;
        foreach ($meja_stat as $ms2) { if ($ms2['id'] === $m['id'] && $ms2['nomor_aktif']) { $nomor_aktif = $ms2['nomor_aktif']; break; } }
        $ada_f2 = $sisa_fase2 > 0;
    ?>
    <div class="col-6 col-md-3">
        <form method="POST">
            <input type="hidden" name="action" value="pilih_meja">
            <input type="hidden" name="meja_id" value="<?= $m['id'] ?>">
            <button class="meja-pick-card" type="submit" <?= !$ada_f2 && !$nomor_aktif ? 'disabled' : '' ?>
                    style="border-color:<?= $ada_f2||$nomor_aktif?'#c4b5fd':'#e5e7eb' ?>;">
                <i class="bi bi-person-check d-block mb-2" style="font-size:1.8rem;color:#7c3aed;"></i>
                <div class="meja-num" style="color:#7c3aed;">Meja <?= $m['nomor_meja'] ?></div>
                <?php if ($m['nama']): ?><div class="text-muted small"><?= htmlspecialchars($m['nama']) ?></div><?php endif; ?>
                <div class="mt-2">
                    <?= $nomor_aktif
                        ? "<span class='badge bg-warning text-dark'>Melayani #$nomor_aktif</span>"
                        : ($ada_f2 ? "<span class='badge' style='background:#ede9fe;color:#7c3aed;'>$sisa_fase2 Menunggu</span>"
                                   : "<span class='badge bg-secondary'>Kosong</span>") ?>
                </div>
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

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
            <span class="fase-badge fase-badge-<?= $my_meja_fase ?>">
                <i class="bi <?= $my_meja_fase==1 ? 'bi-folder-check' : 'bi-person-check' ?>"></i>
                Fase <?= $my_meja_fase ?> — <?= $fase_label[$my_meja_fase] ?? '' ?>
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
    <?php if ($my_meja_fase == 1): ?>
    <span class="stat-pill" style="background:#dbeafe;color:#1d4ed8;">
        <i class="bi bi-hourglass-split"></i> <?= $sisa_fase1 ?> menunggu Fase 1
    </span>
    <span class="stat-pill" style="background:#d1fae5;color:#065f46;">
        <i class="bi bi-check-circle"></i> <?= $selesai_fase1 ?> selesai
    </span>
    <span class="stat-pill" style="background:#d1fae5;color:#065f46;">
        <i class="bi bi-arrow-right-circle"></i> <?= $lulus_fase1 ?> lulus ke Fase 2
    </span>
    <span class="stat-pill" style="background:#fee2e2;color:#991b1b;">
        <i class="bi bi-x-circle"></i> <?= $gagal_fase1 ?> berkas tidak lengkap
    </span>
    <?php else: ?>
    <span class="stat-pill" style="background:#ede9fe;color:#6d28d9;">
        <i class="bi bi-hourglass-split"></i> <?= $sisa_fase2 ?> menunggu Fase 2
    </span>
    <span class="stat-pill" style="background:#d1fae5;color:#065f46;">
        <i class="bi bi-check-circle"></i> <?= $selesai_fase2 ?> surat diterbitkan
    </span>
    <?php endif; ?>
</div>

<?php if (!$antrian_dibuka && $my_meja_fase == 1): ?>
<!-- Antrian belum dibuka -->
<div class="no-antrian-box no-print" style="background:#eff6ff;border:2px dashed #93c5fd;">
    <i class="bi bi-pause-circle fs-1 d-block mb-3" style="color:#2563eb;opacity:.5;"></i>
    <h5 class="fw-semibold">Antrian Fase 1 Belum Dibuka</h5>
    <p class="text-muted mb-0">Superadmin perlu membuka antrian terlebih dahulu.</p>
</div>

<?php elseif ($my_meja_fase == 2 && $sisa_fase2 == 0 && !$current): ?>
<!-- Fase 2 kosong -->
<div class="no-antrian-box no-print" style="background:#f5f3ff;border:2px dashed #c4b5fd;">
    <i class="bi bi-inbox fs-1 d-block mb-3" style="color:#7c3aed;opacity:.5;"></i>
    <h5 class="fw-semibold">Belum Ada Pendaftar di Fase 2</h5>
    <p class="text-muted mb-0">Pendaftar akan masuk ke fase ini setelah berkas dinyatakan lengkap di Fase 1.</p>
</div>

<?php elseif ($current): ?>
<!-- ══ ADA NOMOR AKTIF ══════════════════════════════════════════════════════ -->
<div class="text-center mb-4 <?= $my_meja_fase == 1 ? 'no-print' : '' ?>">
    <div class="text-muted small fw-semibold mb-2 text-uppercase letter-spacing-1">Sedang Dilayani</div>
    <div class="nomor-box fase-<?= $my_meja_fase ?> mb-3">
        <div class="nomor-display fase-<?= $my_meja_fase ?>">SSG<?= str_pad($current['nomor'], 3, '0', STR_PAD_LEFT) ?></div>
    </div>
    <div class="text-muted small mb-4">
        Dipanggil <?= date('H:i:s', strtotime($current['dipanggil_at'])) ?>
        · <span id="timer" data-start="<?= time() - strtotime($current['dipanggil_at']) ?>">...</span> yang lalu
    </div>

    <?php if ($my_meja_fase == 1): ?>
    <!-- FASE 1: Tombol Aksi -->
    <div class="mb-3">
        <div class="fw-semibold text-muted small mb-3">Hasil Pemeriksaan Berkas:</div>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <form method="POST">
                <input type="hidden" name="action" value="lulus">
                <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
                <input type="hidden" name="nomor" value="<?= $current['nomor'] ?>">
                <button type="submit" class="btn-aksi-main btn-lulus"
                        onclick="return confirm('Berkas lengkap? Pendaftar akan masuk ke Fase 2.')">
                    <i class="bi bi-check-lg me-2"></i>Berkas Lengkap → Fase 2
                </button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="gagal">
                <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
                <button type="submit" class="btn-aksi-main btn-gagal"
                        onclick="return confirm('Berkas tidak lengkap? Pendaftar diminta kembali saat berkas sudah lengkap.')">
                    <i class="bi bi-x-lg me-2"></i>Berkas Tidak Lengkap
                </button>
            </form>
        </div>
    </div>
    <div class="d-flex gap-2 justify-content-center">
        <form method="POST">
            <input type="hidden" name="action" value="skip">
            <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
            <button type="submit" class="btn btn-outline-warning"
                    onclick="return confirm('Lewati nomor <?= $current['nomor'] ?>? (Tidak hadir)')">
                <i class="bi bi-forward-fill me-1"></i>Skip (Tidak Hadir)
            </button>
        </form>
    </div>

    <?php else: ?>
    <!-- FASE 2: Checklist Langkah + Tombol Selesai -->
    <div class="fw-semibold text-muted small mb-3 text-uppercase" style="letter-spacing:.5px;">
        Langkah Fase 2 — Input Data &amp; Surat Tanda Daftar:
    </div>

    <div class="fase2-checklist mx-auto mb-4" style="max-width:440px;">
        <div class="fase2-step" role="button" tabindex="0"
             data-bs-toggle="offcanvas" data-bs-target="#f2Sidebar"
             style="cursor:pointer;" title="Buka panel data pendaftar">
            <div class="fase2-step-icon" style="background:#ede9fe;color:#7c3aed;"><i class="bi bi-person-lines-fill"></i></div>
            <div class="fase2-step-body">
                <div class="fw-bold">Hubungkan Data Pendaftar</div>
                <div class="text-muted small"><?= $current_pendaftar ? htmlspecialchars($current_pendaftar['nama']) : 'Klik untuk buka panel pendaftar →' ?></div>
            </div>
            <div class="fase2-step-check">
                <i class="bi <?= $current_pendaftar ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' ?> fs-5"></i>
            </div>
        </div>
        <div class="fase2-step">
            <div class="fase2-step-icon" style="background:#d1fae5;color:#065f46;"><i class="bi bi-file-earmark-text"></i></div>
            <div class="fase2-step-body">
                <div class="fw-bold">Verifikasi & Input Data</div>
                <div class="text-muted small">Buka Data Pendaftar, isi NISN, jurusan, nilai raport & TKA.</div>
            </div>
            <div class="fase2-step-check">
                <input type="checkbox" class="fase2-cb form-check-input" id="cbStep2" style="width:1.3rem;height:1.3rem;">
            </div>
        </div>
        <div class="fase2-step">
            <div class="fase2-step-icon" style="background:#fef3c7;color:#92400e;"><i class="bi bi-printer"></i></div>
            <div class="fase2-step-body">
                <div class="fw-bold">Selesai &amp; Terbitkan Surat</div>
                <div class="text-muted small">Klik tombol Selesai — surat otomatis muncul untuk dicetak.</div>
            </div>
            <div class="fase2-step-check">
                <input type="checkbox" class="fase2-cb form-check-input" id="cbStep3" style="width:1.3rem;height:1.3rem;">
            </div>
        </div>
    </div>

    <div class="d-flex gap-3 justify-content-center flex-wrap mb-2">
        <form method="POST">
            <input type="hidden" name="action" value="selesai">
            <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
            <input type="hidden" name="nomor" value="<?= $current['nomor'] ?>">
            <button type="submit" class="btn-aksi-main btn-selesai" id="btnSelesai"
                    onclick="clearBerkas(<?= $current['id'] ?>); return confirm('Selesai input data? Surat Tanda Daftar akan diterbitkan.')">
                <i class="bi bi-file-earmark-check me-2"></i>Selesai &amp; Terbitkan Surat
            </button>
        </form>
    </div>
    <div class="d-flex gap-2 justify-content-center">
        <form method="POST">
            <input type="hidden" name="action" value="skip">
            <input type="hidden" name="antrian_id" value="<?= $current['id'] ?>">
            <button type="submit" class="btn btn-outline-warning"
                    onclick="return confirm('Lewati nomor <?= $current['nomor'] ?>?')">
                <i class="bi bi-forward-fill me-1"></i>Skip (Tidak Hadir)
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>


<?php else: ?>
<!-- ══ BELUM ADA NOMOR AKTIF ════════════════════════════════════════════════ -->
<div class="no-antrian-box no-print"
     style="background:<?= $my_meja_fase==1?'#eff6ff':'#f5f3ff' ?>;border:2px dashed <?= $my_meja_fase==1?'#93c5fd':'#c4b5fd' ?>;">
    <i class="bi bi-arrow-right-circle fs-1 d-block mb-3"
       style="color:<?= $my_meja_fase==1?'#2563eb':'#7c3aed' ?>;opacity:.6;"></i>
    <h5 class="fw-semibold">
        <?= ($my_meja_fase==1 ? $sisa_fase1 : $sisa_fase2) > 0 ? 'Siap Melayani' : 'Antrian Habis' ?>
    </h5>
    <p class="text-muted mb-3">
        <?= ($my_meja_fase==1 ? $sisa_fase1 : $sisa_fase2) > 0
            ? 'Klik tombol di bawah untuk memanggil nomor berikutnya.'
            : ($my_meja_fase==2 ? 'Belum ada pendaftar yang lulus Fase 1.' : 'Semua antrian sudah selesai.') ?>
    </p>
    <?php if (($my_meja_fase==1 ? $sisa_fase1 : $sisa_fase2) > 0): ?>
    <form method="POST">
        <input type="hidden" name="action" value="mulai">
        <button type="submit" class="btn-aksi-main btn-start">
            <i class="bi bi-play-fill me-2"></i>Panggil Nomor Berikutnya
        </button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ══ BOARD SEMUA MEJA ═══════════════════════════════════════════════════ -->
<?php if (!empty($meja_stat)): ?>
<div class="card mt-4 no-print">
    <div class="card-header">
        <i class="bi bi-grid me-2"></i>Status Semua Meja Hari Ini
    </div>
    <div class="card-body">
        <?php
        $board_fase1 = array_filter($meja_stat, fn($m) => (int)$m['fase'] === 1);
        $board_fase2 = array_filter($meja_stat, fn($m) => (int)$m['fase'] === 2);
        ?>
        <?php if (!empty($board_fase1)): ?>
        <div class="small fw-semibold text-uppercase mb-2" style="color:#2563eb;">Fase 1 — Cek Berkas</div>
        <div class="row g-2 mb-3">
            <?php foreach ($board_fase1 as $ms2): ?>
            <div class="col-6 col-md-3">
                <div class="meja-mini-card <?= (int)$ms2['id'] === $my_meja_id ? 'active-meja' : '' ?>">
                    <div class="small text-muted">Meja <?= $ms2['nomor_meja'] ?></div>
                    <?php if ($ms2['nomor_aktif']): ?>
                        <div class="nomor-mini" style="color:#1d4ed8;">SSG<?= str_pad($ms2['nomor_aktif'], 3, '0', STR_PAD_LEFT) ?></div>
                        <div class="small text-info">Dilayani</div>
                    <?php else: ?>
                        <div class="nomor-mini" style="color:#d1d5db;">—</div>
                        <div class="small text-muted">Siap</div>
                    <?php endif; ?>
                    <div class="small text-success"><i class="bi bi-check"></i><?= $ms2['selesai_count'] ?> selesai</div>
                    <?php if ((int)$ms2['id'] === $my_meja_id): ?><div class="small fw-bold" style="color:#2563eb;">← Meja Anda</div><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($board_fase2)): ?>
        <div class="small fw-semibold text-uppercase mb-2" style="color:#7c3aed;">Fase 2 — Input Data & Surat</div>
        <div class="row g-2">
            <?php foreach ($board_fase2 as $ms2): ?>
            <div class="col-6 col-md-3">
                <div class="meja-mini-card <?= (int)$ms2['id'] === $my_meja_id ? 'active-meja' : '' ?>"
                     style="<?= (int)$ms2['id'] === $my_meja_id ? 'border-color:#7c3aed;' : '' ?>">
                    <div class="small text-muted">Meja <?= $ms2['nomor_meja'] ?></div>
                    <?php if ($ms2['nomor_aktif']): ?>
                        <div class="nomor-mini" style="color:#7c3aed;">SSG<?= str_pad($ms2['nomor_aktif'], 3, '0', STR_PAD_LEFT) ?></div>
                        <div class="small" style="color:#7c3aed;">Dilayani</div>
                    <?php else: ?>
                        <div class="nomor-mini" style="color:#d1d5db;">—</div>
                        <div class="small text-muted">Siap</div>
                    <?php endif; ?>
                    <div class="small text-success"><i class="bi bi-file-earmark-check"></i><?= $ms2['selesai_count'] ?> surat</div>
                    <?php if ((int)$ms2['id'] === $my_meja_id): ?><div class="small fw-bold" style="color:#7c3aed;">← Meja Anda</div><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; // end my_meja_id ?>

<!-- ══ MODAL SURAT TANDA DAFTAR ══════════════════════════════════════════════ -->
<?php if ($show_surat > 0): ?>
<div class="modal fade" id="modalSurat" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header no-print" style="background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-file-earmark-check me-2"></i>Surat Tanda Daftar</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div id="suratContent" style="padding:40px;font-family:Georgia,serif;">
            <div class="text-center border-bottom pb-3 mb-3">
                <div style="font-size:.8rem;text-transform:uppercase;letter-spacing:1px;color:#666;">SMK Laboratorium Jakarta</div>
                <div style="font-size:1.5rem;font-weight:700;color:#1e1b4b;margin:4px 0;">TANDA TERIMA PENDAFTARAN</div>
                <div style="font-size:.8rem;color:#666;">Seleksi Penerimaan Murid Baru (SPMB) <?= date('Y') ?></div>
            </div>
            <table style="width:100%;font-size:.9rem;border-collapse:collapse;margin-bottom:24px;">
                <tr>
                    <td style="padding:6px 0;color:#555;width:40%;">No. Antrian</td>
                    <td style="padding:6px 0;font-weight:700;font-size:1.4rem;color:#7c3aed;">
                        <?= str_pad($show_surat, 3, '0', STR_PAD_LEFT) ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#555;">Tanggal</td>
                    <td style="padding:6px 0;font-weight:600;"><?= date('d F Y') ?></td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#555;">Waktu</td>
                    <td style="padding:6px 0;"><?= date('H:i') ?> WIB</td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#555;">Meja Fase 2</td>
                    <td style="padding:6px 0;">Meja <?= $my_meja['nomor_meja'] ?? '-' ?></td>
                </tr>
            </table>
            <div style="background:#f5f3ff;border:1px solid #c4b5fd;border-radius:8px;padding:14px;text-align:center;margin-bottom:20px;">
                <div style="font-size:.85rem;color:#6d28d9;font-weight:600;">
                    ✓ Pendaftar telah menyelesaikan proses pendaftaran
                </div>
                <div style="font-size:.78rem;color:#7c3aed;margin-top:4px;">
                    Simpan surat ini sebagai bukti pendaftaran. Pengumuman hasil seleksi akan diinformasikan kemudian.
                </div>
            </div>
            <div style="font-size:.75rem;color:#aaa;text-align:center;">
                SMK Laboratorium Jakarta · <?= date('d/m/Y H:i') ?>
            </div>
        </div>
      </div>
      <div class="modal-footer no-print justify-content-between">
        <?php if ($surat_back === 'pendaftar'): ?>
        <a href="?page=pendaftar" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Data Pendaftar
        </a>
        <?php else: ?>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
        <?php endif; ?>
        <button type="button" class="btn btn-primary" onclick="printSurat()">
            <i class="bi bi-printer me-1"></i>Print Surat
        </button>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new bootstrap.Modal(document.getElementById('modalSurat')).show();
});
function printSurat() {
    const content = document.getElementById('suratContent').innerHTML;
    const w = window.open('', '_blank', 'width=600,height=700');
    w.document.write('<html><head><title>Surat Tanda Daftar</title></head><body style="margin:0;padding:20px;font-family:Georgia,serif;">');
    w.document.write(content);
    w.document.write('</body></html>');
    w.document.close();
    w.onload = () => w.print();
}
</script>
<?php endif; ?>

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
function initBerkas(antrianId) {
    const data = loadBerkas(antrianId);
    ['kk','tka','akta','buta_warna'].forEach(k => applyBerkasRow(k, !!data[k]));
}
function clearBerkas(antrianId) {
    localStorage.removeItem(berkasKey(antrianId));
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
<?php if ($current && $current_pendaftar): ?>
document.addEventListener('DOMContentLoaded', () => initBerkas(<?= $current['id'] ?>));
<?php endif; ?>

</script>
