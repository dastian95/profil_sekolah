<?php
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $id     = (int)$_POST['id'];
        $stmt   = $conn->prepare("UPDATE gelombang SET
            tanggal_buka=?, tanggal_tutup=?, tanggal_pengumuman=?,
            tanggal_daftar_ulang_mulai=?, tanggal_daftar_ulang_selesai=?,
            kuota_glm=?,
            jadwal_pendaftaran_text=?, jadwal_pengumuman_text=?, jadwal_daftar_ulang_text=?
            WHERE id=?");
        $stmt->execute([
            $_POST['tanggal_buka'],
            $_POST['tanggal_tutup'],
            $_POST['tanggal_pengumuman'],
            $_POST['tanggal_daftar_ulang_mulai'] ?: null,
            $_POST['tanggal_daftar_ulang_selesai'] ?: null,
            (int)$_POST['kuota_glm'],
            trim($_POST['jadwal_pendaftaran_text'] ?? ''),
            trim($_POST['jadwal_pengumuman_text'] ?? ''),
            trim($_POST['jadwal_daftar_ulang_text'] ?? ''),
            $id,
        ]);

        log_admin_action($conn, 'UPDATE_GELOMBANG', "Update setting gelombang ID:{$id}");
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Pengaturan gelombang berhasil disimpan.</div>';

    } elseif ($action === 'publish') {
        $id  = (int)$_POST['id'];
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
    }
}

$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();

// Hitung pendaftar per gelombang
$counts = [];
foreach ($gel_rows as $g) {
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=?");
    $s->execute([$g['gelombang']]);
    $counts[$g['gelombang']] = $s->fetchColumn();

    $s2 = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=? AND status='terima'");
    $s2->execute([$g['gelombang']]);
    $counts[$g['gelombang'].'_diterima'] = $s2->fetchColumn();
}
?>

<?= $msg ?>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Alur dua tahap:</strong>
    <strong>① Umumkan Gelombang</strong> → banner muncul di halaman publik (tanpa daftar nama).
    <strong>② Publish Hasil Penerimaan</strong> → daftar siswa yang diterima tampil di halaman publik dengan filter jurusan.
    Pastikan ranking &amp; seleksi sudah dijalankan sebelum Tahap ②.
</div>

<div class="row g-4">
<?php foreach ($gel_rows as $g):
    $kuota_glm   = (int)($g['kuota_glm'] ?? 0);
    $total_kuota = $kuota_glm * count(JURUSAN_LIST);
?>
<div class="col-lg-6">
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6">Gelombang <?= $g['gelombang'] ?></span>
        <?php if (!empty($g['is_hasil_published'])): ?>
            <span class="badge bg-success"><i class="bi bi-trophy me-1"></i>Hasil Published</span>
        <?php elseif ($g['is_published']): ?>
            <span class="badge bg-info text-dark"><i class="bi bi-broadcast me-1"></i>Diumumkan (tanpa hasil)</span>
        <?php else: ?>
            <span class="badge bg-secondary"><i class="bi bi-eye-slash me-1"></i>Belum dipublish</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <!-- Statistik singkat -->
        <div class="row g-2 mb-3">
            <div class="col-4 text-center">
                <div class="fw-bold fs-5 text-primary"><?= $counts[$g['gelombang']] ?></div>
                <small class="text-muted">Total Pendaftar</small>
            </div>
            <div class="col-4 text-center">
                <div class="fw-bold fs-5 text-success"><?= $counts[$g['gelombang'].'_diterima'] ?></div>
                <small class="text-muted">Diterima</small>
            </div>
            <div class="col-4 text-center">
                <div class="fw-bold fs-5 text-info"><?= $total_kuota ?></div>
                <small class="text-muted">Total Kuota (<?= count(JURUSAN_LIST) ?> jur.)</small>
            </div>
        </div>

        <!-- Form Update Setting -->
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $g['id'] ?>">

            <h6 class="text-muted text-uppercase small mt-2 mb-2 fw-bold"><i class="bi bi-calendar3 me-1"></i>Periode Tanggal</h6>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Buka Pendaftaran</label>
                    <input type="date" name="tanggal_buka" class="form-control form-control-sm"
                           value="<?= $g['tanggal_buka'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Tutup Pendaftaran</label>
                    <input type="date" name="tanggal_tutup" class="form-control form-control-sm"
                           value="<?= $g['tanggal_tutup'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Tanggal Pengumuman</label>
                    <input type="date" name="tanggal_pengumuman" class="form-control form-control-sm"
                           value="<?= $g['tanggal_pengumuman'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Kuota / Jurusan</label>
                    <input type="number" name="kuota_glm" class="form-control form-control-sm"
                           value="<?= (int)$g['kuota_glm'] ?>" min="1" required>
                    <small class="text-muted">Ambil N terbaik per jurusan</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Daftar Ulang Mulai</label>
                    <input type="date" name="tanggal_daftar_ulang_mulai" class="form-control form-control-sm"
                           value="<?= $g['tanggal_daftar_ulang_mulai'] ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Daftar Ulang Selesai</label>
                    <input type="date" name="tanggal_daftar_ulang_selesai" class="form-control form-control-sm"
                           value="<?= $g['tanggal_daftar_ulang_selesai'] ?>">
                </div>
            </div>

            <h6 class="text-muted text-uppercase small mt-2 mb-2 fw-bold"><i class="bi bi-card-text me-1"></i>Teks Jadwal (Tampil di Halaman Publik)</h6>
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
                <small class="text-muted">Gunakan baris baru untuk multi-jadwal. Akan tampil di section "Cara Mendaftar" halaman publik.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-save me-1"></i>Simpan Pengaturan
            </button>
        </form>

        <hr>

        <!-- Tahap publish (3 state) -->
        <?php if (!empty($g['is_hasil_published'])): ?>
            <!-- State 3: Hasil sudah live -->
            <div class="mb-2 small text-muted">
                <i class="bi bi-trophy-fill text-warning me-1"></i>
                Hasil published: <?= date('d M Y H:i', strtotime($g['hasil_published_at'])) ?>
            </div>
            <form method="POST" class="mb-2" onsubmit="return confirm('Sembunyikan daftar hasil penerimaan? Banner pengumuman masih akan tampil.')">
                <input type="hidden" name="action" value="unpublish_hasil">
                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                    <i class="bi bi-eye-slash me-1"></i>Sembunyikan Daftar Hasil
                </button>
            </form>
            <form method="POST" onsubmit="return confirm('Sembunyikan semua pengumuman gelombang ini dari halaman publik?')">
                <input type="hidden" name="action" value="unpublish">
                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                    <i class="bi bi-x-circle me-1"></i>Sembunyikan Semua Pengumuman
                </button>
            </form>

        <?php elseif ($g['is_published']): ?>
            <!-- State 2: Banner live, belum ada hasil -->
            <div class="mb-2 small text-muted">
                <i class="bi bi-broadcast text-info me-1"></i>
                Diumumkan: <?= date('d M Y H:i', strtotime($g['published_at'])) ?>
            </div>
            <div class="alert alert-warning py-2 small mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Pastikan proses ranking sudah dijalankan sebelum publish hasil.
            </div>
            <form method="POST" class="mb-2" onsubmit="return confirm('Publish daftar hasil penerimaan Gelombang <?= $g['gelombang'] ?>? Nama-nama siswa yang diterima akan tampil di halaman publik.')">
                <input type="hidden" name="action" value="publish_hasil">
                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm w-100">
                    <i class="bi bi-trophy me-1"></i>Publish Hasil Penerimaan
                </button>
            </form>
            <form method="POST" onsubmit="return confirm('Sembunyikan semua pengumuman gelombang ini dari halaman publik?')">
                <input type="hidden" name="action" value="unpublish">
                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                    <i class="bi bi-eye-slash me-1"></i>Sembunyikan Pengumuman
                </button>
            </form>

        <?php else: ?>
            <!-- State 1: Belum dipublish sama sekali -->
            <form method="POST" onsubmit="return confirm('Umumkan bahwa Gelombang <?= $g['gelombang'] ?> telah dibuka? Banner pengumuman akan tampil di halaman publik.')">
                <input type="hidden" name="action" value="publish">
                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-broadcast me-1"></i>Umumkan Gelombang <?= $g['gelombang'] ?>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>
