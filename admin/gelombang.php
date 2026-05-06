<?php
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $id     = (int)$_POST['id'];
        $buka   = $_POST['tanggal_buka'];
        $tutup  = $_POST['tanggal_tutup'];
        $umum   = $_POST['tanggal_pengumuman'];
        $kuota  = (int)$_POST['kuota_per_jurusan'];
        $persen = (float)$_POST['persen_gelombang'];

        $stmt = $conn->prepare("UPDATE gelombang SET tanggal_buka=?, tanggal_tutup=?, tanggal_pengumuman=?,
                                kuota_per_jurusan=?, persen_gelombang=? WHERE id=?");
        $stmt->execute([$buka, $tutup, $umum, $kuota, $persen, $id]);

        $log = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
        $log->execute([$_SESSION['admin_id'], 'UPDATE_GELOMBANG', "Update setting gelombang ID:{$id}", $_SERVER['REMOTE_ADDR']]);
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Pengaturan gelombang berhasil disimpan.</div>';

    } elseif ($action === 'publish') {
        $id  = (int)$_POST['id'];
        $now = date('Y-m-d H:i:s');
        $conn->prepare("UPDATE gelombang SET is_published=1, published_at=? WHERE id=?")->execute([$now, $id]);

        $log = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
        $log->execute([$_SESSION['admin_id'], 'PUBLISH_PENGUMUMAN', "Publish pengumuman gelombang ID:{$id}", $_SERVER['REMOTE_ADDR']]);
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Pengumuman berhasil dipublish dan sekarang tampil di halaman publik.</div>';

    } elseif ($action === 'unpublish') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE gelombang SET is_published=0, published_at=NULL WHERE id=?")->execute([$id]);

        $log = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
        $log->execute([$_SESSION['admin_id'], 'UNPUBLISH_PENGUMUMAN', "Unpublish pengumuman gelombang ID:{$id}", $_SERVER['REMOTE_ADDR']]);
        $msg = '<div class="alert alert-warning"><i class="bi bi-eye-slash me-2"></i>Pengumuman disembunyikan dari halaman publik.</div>';
    }
}

$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();

// Hitung pendaftar per gelombang
$counts = [];
foreach ($gel_rows as $g) {
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=?");
    $s->execute([$g['gelombang']]);
    $counts[$g['gelombang']] = $s->fetchColumn();

    $s2 = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=? AND status='diterima'");
    $s2->execute([$g['gelombang']]);
    $counts[$g['gelombang'].'_diterima'] = $s2->fetchColumn();
}
?>

<?= $msg ?>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Catatan:</strong> Pastikan proses penerimaan (di halaman Ranking & Hasil) sudah dijalankan sebelum mempublish pengumuman.
    Hasil yang ditampilkan di halaman publik adalah semua pendaftar dengan status <strong>Diterima</strong>.
</div>

<div class="row g-4">
<?php foreach ($gel_rows as $g):
    $kuota_glm = (int)round($g['kuota_per_jurusan'] * $g['persen_gelombang'] / 100);
    $total_kuota = $kuota_glm * 4;
?>
<div class="col-lg-6">
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold fs-6">Gelombang <?= $g['gelombang'] ?></span>
        <?php if ($g['is_published']): ?>
            <span class="badge bg-success"><i class="bi bi-broadcast me-1"></i>Live — Publik dapat melihat</span>
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
                <small class="text-muted">Total Kuota (4 jur.)</small>
            </div>
        </div>

        <!-- Form Update Setting -->
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= $g['id'] ?>">
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Tanggal Buka Pendaftaran</label>
                    <input type="date" name="tanggal_buka" class="form-control form-control-sm"
                           value="<?= $g['tanggal_buka'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Tanggal Tutup Pendaftaran</label>
                    <input type="date" name="tanggal_tutup" class="form-control form-control-sm"
                           value="<?= $g['tanggal_tutup'] ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold mb-1">Tanggal Pengumuman</label>
                    <input type="date" name="tanggal_pengumuman" class="form-control form-control-sm"
                           value="<?= $g['tanggal_pengumuman'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Kuota/Jurusan</label>
                    <input type="number" name="kuota_per_jurusan" class="form-control form-control-sm"
                           value="<?= $g['kuota_per_jurusan'] ?>" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Porsi (%)</label>
                    <input type="number" name="persen_gelombang" class="form-control form-control-sm"
                           value="<?= $g['persen_gelombang'] ?>" min="1" max="100" step="0.01" required>
                    <small class="text-muted">Glm1=70, Glm2=30</small>
                </div>
            </div>
            <div class="small text-muted mb-2">
                Ambil <strong><?= $kuota_glm ?></strong> terbaik per jurusan
                (= <?= $g['kuota_per_jurusan'] ?> × <?= $g['persen_gelombang'] ?>%)
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-save me-1"></i>Simpan Pengaturan
            </button>
        </form>

        <hr>

        <!-- Publish / Unpublish -->
        <?php if ($g['is_published']): ?>
        <div class="mb-2 small text-muted">
            Dipublish pada: <?= date('d M Y H:i', strtotime($g['published_at'])) ?>
        </div>
        <form method="POST" onsubmit="return confirm('Sembunyikan pengumuman gelombang ini dari halaman publik?')">
            <input type="hidden" name="action" value="unpublish">
            <input type="hidden" name="id" value="<?= $g['id'] ?>">
            <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                <i class="bi bi-eye-slash me-1"></i>Sembunyikan Pengumuman
            </button>
        </form>
        <?php else: ?>
        <form method="POST" onsubmit="return confirm('Publish pengumuman? Hasil penerimaan Gelombang <?= $g['gelombang'] ?> akan tampil di halaman publik.')">
            <input type="hidden" name="action" value="publish">
            <input type="hidden" name="id" value="<?= $g['id'] ?>">
            <button type="submit" class="btn btn-success btn-sm w-100">
                <i class="bi bi-broadcast me-1"></i>Publish Pengumuman Gelombang <?= $g['gelombang'] ?>
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>
</div>
<?php endforeach; ?>
</div>
