<?php
// Guard: superadmin selalu boleh; admin biasa harus punya tahap kelola_gelombang ATAU pengumuman
if (empty($_SESSION['is_super'])) {
    $ck = $conn->prepare("SELECT 1 FROM admin_tahapan at JOIN tahapan t ON t.id=at.tahap_id
        WHERE at.admin_id=? AND t.kode IN ('kelola_gelombang','pengumuman') AND t.is_active=1 LIMIT 1");
    $ck->execute([$_SESSION['admin_id'] ?? 0]);
    if (!$ck->fetchColumn()) {
        echo '<div class="alert alert-danger"><i class="bi bi-shield-lock me-2"></i>Akses ditolak. Anda tidak memiliki tahap <strong>Kelola Gelombang</strong> atau <strong>Pengumuman</strong>.</div>';
        return;
    }
}

$msg = '';
if (!empty($_SESSION['flash_pengaturan_spmb'])) {
    $msg = $_SESSION['flash_pengaturan_spmb'];
    unset($_SESSION['flash_pengaturan_spmb']);
}

// Auto-migrate: kolom fitur & ketentuan gelombang
foreach ([
    "ALTER TABLE gelombang ADD COLUMN min_tka TINYINT NOT NULL DEFAULT 0 AFTER kuota_glm",
    "ALTER TABLE gelombang ADD COLUMN buta_warna_wajib TINYINT(1) NOT NULL DEFAULT 0 AFTER min_tka",
    "ALTER TABLE gelombang ADD COLUMN pesan_gugur TEXT NULL AFTER buta_warna_wajib",
    "ALTER TABLE gelombang ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE gelombang ADD COLUMN locked_at DATETIME NULL",
] as $_gsql) {
    try { $conn->exec($_gsql); } catch(PDOException) {}
}
// "Ditahan" = status KEDUA (flag terpisah), tidak menimpa status kompetisi (diproses/gugur/terima)
try { $conn->exec("ALTER TABLE pendaftar ADD COLUMN is_ditahan TINYINT(1) NOT NULL DEFAULT 0"); } catch (PDOException) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $id   = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE gelombang SET
            tanggal_buka=?, tanggal_tutup=?, tanggal_pengumuman=?,
            tanggal_daftar_ulang_mulai=?, tanggal_daftar_ulang_selesai=?,
            kuota_glm=?, min_tka=?, buta_warna_wajib=?, pesan_gugur=?,
            jadwal_pendaftaran_text=?, jadwal_pengumuman_text=?, jadwal_daftar_ulang_text=?
            WHERE id=?");
        $stmt->execute([
            $_POST['tanggal_buka'],
            $_POST['tanggal_tutup'],
            $_POST['tanggal_pengumuman'],
            $_POST['tanggal_daftar_ulang_mulai'] ?: null,
            $_POST['tanggal_daftar_ulang_selesai'] ?: null,
            (int)$_POST['kuota_glm'],
            (int)($_POST['min_tka'] ?? 0),
            (int)($_POST['buta_warna_wajib'] ?? 0),
            trim($_POST['pesan_gugur'] ?? '') ?: null,
            trim($_POST['jadwal_pendaftaran_text'] ?? ''),
            trim($_POST['jadwal_pengumuman_text'] ?? ''),
            trim($_POST['jadwal_daftar_ulang_text'] ?? ''),
            $id,
        ]);
        log_admin_action($conn, 'UPDATE_GELOMBANG', "Update setting gelombang ID:{$id}");
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Pengaturan gelombang berhasil disimpan.</div>';

    } elseif ($action === 'publish') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE gelombang SET is_published=1, published_at=NOW() WHERE id=?")->execute([$id]);
        log_admin_action($conn, 'PUBLISH_PENGUMUMAN', "Publish pengumuman gelombang ID:{$id}");
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Gelombang diumumkan — banner tampil di halaman publik.</div>';

    } elseif ($action === 'unpublish') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE gelombang SET is_published=0, published_at=NULL, is_hasil_published=0, hasil_published_at=NULL WHERE id=?")->execute([$id]);
        log_admin_action($conn, 'UNPUBLISH_PENGUMUMAN', "Unpublish semua pengumuman gelombang ID:{$id}");
        $msg = '<div class="alert alert-warning"><i class="bi bi-eye-slash me-2"></i>Pengumuman dan hasil disembunyikan dari halaman publik.</div>';

    } elseif ($action === 'publish_hasil') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE gelombang SET is_hasil_published=1, hasil_published_at=NOW(), is_published=1 WHERE id=?")->execute([$id]);
        log_admin_action($conn, 'PUBLISH_HASIL', "Publish hasil penerimaan gelombang ID:{$id}");
        $msg = '<div class="alert alert-success"><i class="bi bi-trophy me-2"></i>Hasil penerimaan berhasil dipublish — daftar siswa diterima tampil di halaman publik.</div>';

    } elseif ($action === 'unpublish_hasil') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE gelombang SET is_hasil_published=0, hasil_published_at=NULL WHERE id=?")->execute([$id]);
        log_admin_action($conn, 'UNPUBLISH_HASIL', "Unpublish hasil penerimaan gelombang ID:{$id}");
        $msg = '<div class="alert alert-warning"><i class="bi bi-eye-slash me-2"></i>Daftar hasil penerimaan disembunyikan (banner pengumuman masih tampil).</div>';

    } elseif ($action === 'kunci') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE gelombang SET is_locked=1, locked_at=NOW() WHERE id=?")->execute([$id]);
        log_admin_action($conn, 'KUNCI_GELOMBANG', "Kunci kompetisi gelombang ID:{$id}");
        $msg = '<div class="alert alert-warning"><i class="bi bi-lock-fill me-2"></i>Gelombang dikunci — peringkat dibekukan, edit data kompetisi diblokir, pendaftar baru jadi <strong>Ditahan</strong>.</div>';

    } elseif ($action === 'buka_kunci') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE gelombang SET is_locked=0, locked_at=NULL WHERE id=?")->execute([$id]);
        log_admin_action($conn, 'BUKA_KUNCI_GELOMBANG', "Buka kunci gelombang ID:{$id}");
        $msg = '<div class="alert alert-success"><i class="bi bi-unlock me-2"></i>Gelombang dibuka — peringkat &amp; edit data bisa diubah lagi.</div>';
    }

    // PRG: redirect setelah POST agar refresh tidak mengulang aksi
    $_SESSION['flash_pengaturan_spmb'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . '?page=pengaturan_spmb');
    exit;
}

$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();

// Hitung statistik per gelombang + per jurusan
$counts = [];
foreach ($gel_rows as $g) {
    $glm = $g['gelombang'];
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=?");
    $s->execute([$glm]); $counts[$glm]['total'] = (int)$s->fetchColumn();

    $s2 = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=? AND status='terima'");
    $s2->execute([$glm]); $counts[$glm]['diterima'] = (int)$s2->fetchColumn();

    $counts[$glm]['ditahan'] = 0;
    try { $s4 = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=? AND is_ditahan=1"); $s4->execute([$glm]); $counts[$glm]['ditahan'] = (int)$s4->fetchColumn(); } catch (Throwable) {}

    // Per jurusan: diterima
    foreach (JURUSAN_LIST as $kode => $nama) {
        $s3 = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=? AND jurusan=? AND status='terima'");
        $s3->execute([$glm, $kode]); $counts[$glm]['jur'][$kode] = (int)$s3->fetchColumn();
    }
}

$first_id = !empty($gel_rows) ? $gel_rows[0]['id'] : 0;
?>

<style>
.spmb-tab-nav .nav-link { border-radius: 10px 10px 0 0; font-weight: 600; padding: .6rem 1.4rem; }
.spmb-tab-nav .nav-link.active { background: #fff; border-color: #dee2e6 #dee2e6 #fff; color: var(--primary, #198754); }
.kuota-table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .3px; }
.kuota-table td, .kuota-table th { padding: .55rem .9rem; vertical-align: middle; }
.progress-thin { height: 6px; border-radius: 4px; }
.publish-state-card { border-radius: 10px; padding: 14px 16px; }
.stat-pill { background: #f8fafc; border-radius: 10px; padding: 12px 16px; text-align: center; }
.stat-pill .num { font-size: 1.5rem; font-weight: 700; line-height: 1.1; }
</style>

<?= $msg ?>

<div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Alur dua tahap:</strong>
    <strong>① Umumkan Gelombang</strong> → banner muncul di halaman publik (tanpa daftar nama).
    <strong>② Publish Hasil Penerimaan</strong> → daftar siswa yang diterima tampil di halaman publik.
    Pastikan ranking &amp; seleksi sudah dijalankan sebelum Tahap ②.
</div>

<?php if (empty($gel_rows)): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Belum ada data gelombang.</div>
<?php else: ?>

<!-- ── TABS per Gelombang ─────────────────────────────────────────── -->
<ul class="nav nav-tabs spmb-tab-nav mb-0" id="gelTabs" role="tablist">
    <?php foreach ($gel_rows as $i => $g): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $i === 0 ? 'active' : '' ?>"
                id="tab-gel-<?= $g['id'] ?>"
                data-bs-toggle="tab" data-bs-target="#pane-gel-<?= $g['id'] ?>"
                type="button" role="tab">
            <i class="bi bi-calendar-week me-1"></i>Gelombang <?= $g['gelombang'] ?>
            <?php if (!empty($g['is_hasil_published'])): ?>
                <span class="badge bg-success ms-1" style="font-size:.6rem;">Hasil</span>
            <?php elseif ($g['is_published']): ?>
                <span class="badge bg-info text-dark ms-1" style="font-size:.6rem;">Live</span>
            <?php endif; ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content border border-top-0 rounded-bottom bg-white p-3 p-md-4" id="gelTabsContent">
<?php foreach ($gel_rows as $i => $g):
    $glm        = $g['gelombang'];
    $kuota_glm  = (int)($g['kuota_glm'] ?? 0);
    $total_kuo  = $kuota_glm * count(JURUSAN_LIST);
    $total_pend = $counts[$glm]['total'];
    $total_dite = $counts[$glm]['diterima'];
?>
<div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
     id="pane-gel-<?= $g['id'] ?>" role="tabpanel">

    <!-- ── Stat Pills ── -->
    <div class="row g-2 mb-4">
        <div class="col-4">
            <div class="stat-pill">
                <div class="num text-primary"><?= $total_pend ?></div>
                <div class="small text-muted">Total Pendaftar</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-pill">
                <div class="num text-success"><?= $total_dite ?></div>
                <div class="small text-muted">Diterima</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-pill">
                <div class="num text-info"><?= $total_kuo ?></div>
                <div class="small text-muted">Total Kuota</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- ── Kolom Kiri: Kuota & Jadwal ── -->
        <div class="col-lg-7">

            <!-- Kuota per Jurusan -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-pie-chart-fill me-2 text-primary"></i>Kuota per Jurusan
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover kuota-table mb-0">
                        <thead>
                            <tr>
                                <th>Jurusan</th>
                                <th class="text-center">Kuota</th>
                                <th class="text-center">Diterima</th>
                                <th class="text-center">Sisa</th>
                                <th style="min-width:90px;">Terisi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (JURUSAN_LIST as $kode => $nama):
                            $diterima_jur = $counts[$glm]['jur'][$kode] ?? 0;
                            $sisa         = max(0, $kuota_glm - $diterima_jur);
                            $pct          = $kuota_glm > 0 ? min(100, round($diterima_jur / $kuota_glm * 100)) : 0;
                            $bar_cls      = $pct >= 100 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-success');
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($kode) ?></span>
                                <span class="d-none d-sm-inline"><?= htmlspecialchars($nama) ?></span>
                            </td>
                            <td class="text-center fw-semibold"><?= $kuota_glm ?></td>
                            <td class="text-center text-success fw-semibold"><?= $diterima_jur ?></td>
                            <td class="text-center <?= $sisa === 0 ? 'text-danger' : 'text-muted' ?>"><?= $sisa ?></td>
                            <td>
                                <div class="progress progress-thin">
                                    <div class="progress-bar <?= $bar_cls ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                                <div class="text-muted" style="font-size:.7rem;"><?= $pct ?>%</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-semibold">
                            <tr>
                                <td>Total</td>
                                <td class="text-center"><?= $total_kuo ?></td>
                                <td class="text-center text-success"><?= $total_dite ?></td>
                                <td class="text-center"><?= max(0, $total_kuo - $total_dite) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Form Jadwal & Kuota -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-sliders me-2 text-primary"></i>Pengaturan Jadwal &amp; Kuota
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">

                        <h6 class="text-muted text-uppercase small mb-2 fw-bold">
                            <i class="bi bi-calendar3 me-1"></i>Periode Tanggal
                        </h6>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Buka Pendaftaran</label>
                                <input type="date" name="tanggal_buka" class="form-control form-control-sm"
                                       value="<?= $g['tanggal_buka'] ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Tutup Pendaftaran</label>
                                <input type="date" name="tanggal_tutup" class="form-control form-control-sm"
                                       value="<?= $g['tanggal_tutup'] ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Tanggal Pengumuman</label>
                                <input type="date" name="tanggal_pengumuman" class="form-control form-control-sm"
                                       value="<?= $g['tanggal_pengumuman'] ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Kuota / Jurusan</label>
                                <input type="number" name="kuota_glm" class="form-control form-control-sm"
                                       value="<?= $kuota_glm ?>" min="1" required>
                                <div class="form-text">Sama untuk semua jurusan. Total = <?= count(JURUSAN_LIST) ?> × kuota.</div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Daftar Ulang Mulai</label>
                                <input type="date" name="tanggal_daftar_ulang_mulai" class="form-control form-control-sm"
                                       value="<?= $g['tanggal_daftar_ulang_mulai'] ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">Daftar Ulang Selesai</label>
                                <input type="date" name="tanggal_daftar_ulang_selesai" class="form-control form-control-sm"
                                       value="<?= $g['tanggal_daftar_ulang_selesai'] ?>">
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase small mb-2 fw-bold">
                            <i class="bi bi-card-text me-1"></i>Teks Jadwal (tampil di halaman publik)
                        </h6>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Jadwal Pendaftaran</label>
                            <textarea name="jadwal_pendaftaran_text" class="form-control form-control-sm" rows="2"
                                      placeholder="contoh:&#10;15 - 29 Juni 2026 | 08:00 - 16:00&#10;30 Juni 2026 | 08:00 - 12:00"><?= htmlspecialchars($g['jadwal_pendaftaran_text'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold mb-1">Jadwal Pengumuman</label>
                            <textarea name="jadwal_pengumuman_text" class="form-control form-control-sm" rows="1"
                                      placeholder="contoh: 1 Juli 2026 | 08:00 - 16:00"><?= htmlspecialchars($g['jadwal_pengumuman_text'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Jadwal Daftar Ulang</label>
                            <textarea name="jadwal_daftar_ulang_text" class="form-control form-control-sm" rows="1"
                                      placeholder="contoh: 3 - 4 Juli 2026 | 08:00 - 16:00"><?= htmlspecialchars($g['jadwal_daftar_ulang_text'] ?? '') ?></textarea>
                            <div class="form-text">Gunakan baris baru untuk multi-jadwal. Tampil di halaman publik bagian Cara Mendaftar.</div>
                        </div>

                        <h6 class="text-muted text-uppercase small mb-2 fw-bold mt-3">
                            <i class="bi bi-toggle-on me-1"></i>Fitur &amp; Ketentuan
                        </h6>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold mb-1">
                                    Nilai Min. TKA
                                    <small class="text-muted fw-normal">(0 = tidak dibatasi)</small>
                                </label>
                                <input type="number" name="min_tka" class="form-control form-control-sm"
                                       value="<?= (int)($g['min_tka'] ?? 0) ?>" min="0" max="100">
                            </div>
                            <div class="col-sm-6 d-flex align-items-end pb-1">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="buta_warna_wajib" value="0">
                                    <input class="form-check-input" type="checkbox"
                                           id="bww_<?= $g['id'] ?>"
                                           name="buta_warna_wajib" value="1"
                                           <?= !empty($g['buta_warna_wajib']) ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="bww_<?= $g['id'] ?>">
                                        Tes Buta Warna Wajib
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Pesan untuk Peserta Gugur</label>
                            <textarea name="pesan_gugur" class="form-control form-control-sm" rows="2"
                                      placeholder="contoh: Terima kasih telah mendaftar. Nilai Anda belum memenuhi syarat minimum..."><?= htmlspecialchars($g['pesan_gugur'] ?? '') ?></textarea>
                            <div class="form-text">Tampil di halaman cek status pendaftar yang ditolak/gugur.</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-save me-1"></i>Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>

        </div><!-- /col-lg-7 -->

        <!-- ── Kolom Kanan: Status Publikasi ── -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-broadcast me-2 text-primary"></i>Status Publikasi
                </div>
                <div class="card-body">

                    <!-- Status badge -->
                    <div class="mb-3 text-center">
                        <?php if (!empty($g['is_hasil_published'])): ?>
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="bi bi-trophy-fill me-1"></i>Hasil Published
                            </span>
                            <div class="small text-muted mt-2">
                                Dipublish: <?= date('d M Y H:i', strtotime($g['hasil_published_at'])) ?>
                            </div>
                        <?php elseif ($g['is_published']): ?>
                            <span class="badge bg-info text-dark fs-6 px-3 py-2">
                                <i class="bi bi-broadcast me-1"></i>Diumumkan (Banner)
                            </span>
                            <div class="small text-muted mt-2">
                                Diumumkan: <?= date('d M Y H:i', strtotime($g['published_at'])) ?>
                            </div>
                        <?php else: ?>
                            <span class="badge bg-secondary fs-6 px-3 py-2">
                                <i class="bi bi-eye-slash me-1"></i>Belum Dipublish
                            </span>
                            <div class="small text-muted mt-2">Tidak tampil di halaman publik</div>
                        <?php endif; ?>
                    </div>

                    <!-- Alur visual -->
                    <div class="mb-3 p-3 rounded" style="background:#f8fafc;font-size:.82rem;">
                        <div class="d-flex align-items-center gap-2 mb-2 <?= !$g['is_published'] ? 'text-muted' : 'text-success fw-semibold' ?>">
                            <i class="bi bi-<?= $g['is_published'] ? 'check-circle-fill' : 'circle' ?> fs-5"></i>
                            Tahap 1: Banner pengumuman live
                        </div>
                        <div class="d-flex align-items-center gap-2 <?= empty($g['is_hasil_published']) ? 'text-muted' : 'text-success fw-semibold' ?>">
                            <i class="bi bi-<?= !empty($g['is_hasil_published']) ? 'check-circle-fill' : 'circle' ?> fs-5"></i>
                            Tahap 2: Daftar hasil diterima live
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <?php if (!empty($g['is_hasil_published'])): ?>
                        <form method="POST" class="mb-2"
                              onsubmit="return confirm('Sembunyikan daftar hasil? Banner pengumuman masih akan tampil.')">
                            <input type="hidden" name="action" value="unpublish_hasil">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                <i class="bi bi-eye-slash me-1"></i>Sembunyikan Daftar Hasil
                            </button>
                        </form>
                        <form method="POST"
                              onsubmit="return confirm('Sembunyikan SEMUA pengumuman gelombang ini dari halaman publik?')">
                            <input type="hidden" name="action" value="unpublish">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                <i class="bi bi-x-circle me-1"></i>Sembunyikan Semua Pengumuman
                            </button>
                        </form>

                    <?php elseif ($g['is_published']): ?>
                        <div class="alert alert-warning py-2 small mb-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Pastikan proses ranking sudah dijalankan sebelum publish hasil.
                        </div>
                        <form method="POST" class="mb-2"
                              onsubmit="return confirm('Publish daftar hasil penerimaan Gelombang <?= $g['gelombang'] ?>? Nama-nama siswa yang diterima akan tampil di halaman publik.')">
                            <input type="hidden" name="action" value="publish_hasil">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-trophy me-1"></i>Publish Hasil Penerimaan
                            </button>
                        </form>
                        <form method="POST"
                              onsubmit="return confirm('Sembunyikan pengumuman gelombang ini dari halaman publik?')">
                            <input type="hidden" name="action" value="unpublish">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                                <i class="bi bi-eye-slash me-1"></i>Sembunyikan Pengumuman
                            </button>
                        </form>

                    <?php else: ?>
                        <form method="POST"
                              onsubmit="return confirm('Umumkan bahwa Gelombang <?= $g['gelombang'] ?> telah dibuka? Banner pengumuman akan tampil di halaman publik.')">
                            <input type="hidden" name="action" value="publish">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-broadcast me-1"></i>Umumkan Gelombang <?= $g['gelombang'] ?>
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div><!-- /card status -->

            <!-- ── Kunci Kompetisi ── -->
            <?php $locked = !empty($g['is_locked']); $ditahan = $counts[$glm]['ditahan'] ?? 0; ?>
            <div class="card mt-3 border-<?= $locked ? 'danger' : 'secondary' ?>">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-lock-fill me-2 <?= $locked ? 'text-danger' : 'text-secondary' ?>"></i>Kunci Kompetisi</span>
                    <?php if ($locked): ?>
                        <span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>
                    <?php else: ?>
                        <span class="badge bg-success"><i class="bi bi-unlock-fill me-1"></i>Dibuka</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Saat <strong>terkunci</strong>: peringkat dibekukan, edit data kompetisi diblokir, dan pendaftar baru masuk status <strong>Ditahan</strong> (tidak ikut peringkat). Lapor Diri tetap jalan.</p>
                    <?php if ($ditahan > 0): ?>
                    <div class="alert alert-warning py-2 small mb-3"><i class="bi bi-pause-circle me-1"></i><strong><?= $ditahan ?></strong> pendaftar berstatus <strong>Ditahan</strong> di gelombang ini.</div>
                    <?php endif; ?>
                    <?php if ($locked): ?>
                        <form method="POST" onsubmit="return confirm('Buka kunci Gelombang <?= $g['gelombang'] ?>? Peringkat &amp; edit data bisa diubah lagi.')">
                            <input type="hidden" name="action" value="buka_kunci">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <button class="btn btn-outline-success w-100"><i class="bi bi-unlock me-1"></i>Buka Kunci Gelombang <?= $g['gelombang'] ?></button>
                        </form>
                        <?php if (!empty($g['locked_at'])): ?>
                        <div class="text-muted small mt-2 text-center"><i class="bi bi-clock-history me-1"></i>Dikunci sejak <?= date('d M Y H:i', strtotime($g['locked_at'])) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="POST" onsubmit="return confirm('Kunci Gelombang <?= $g['gelombang'] ?>? Peringkat dibekukan, edit data kompetisi diblokir, dan pendaftar baru jadi Ditahan.')">
                            <input type="hidden" name="action" value="kunci">
                            <input type="hidden" name="id" value="<?= $g['id'] ?>">
                            <button class="btn btn-danger w-100"><i class="bi bi-lock-fill me-1"></i>Kunci Gelombang <?= $g['gelombang'] ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /col-lg-5 -->

    </div><!-- /row -->
</div><!-- /tab-pane -->
<?php endforeach; ?>
</div><!-- /tab-content -->

<?php endif; ?>
