<?php
// Kelola Sekolah Asal (SMP) — sumber dropdown "Asal Sekolah" di Data Pendaftar.
// Bisa diakses admin & superadmin.
ensure_sekolah_table($conn);

$msg = '';
if (!empty($_SESSION['flash_sekolah'])) {
    $msg = $_SESSION['flash_sekolah'];
    unset($_SESSION['flash_sekolah']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $nama   = trim($_POST['nama'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '') ?: null;
            if ($nama === '') throw new Exception('Nama sekolah wajib diisi.');
            $conn->prepare("INSERT INTO sekolah_asal (nama, alamat) VALUES (?,?)")->execute([$nama, $alamat]);
            log_admin_action($conn, 'SEKOLAH_ADD', "Tambah sekolah: $nama");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Sekolah <strong>' . htmlspecialchars($nama) . '</strong> ditambahkan.</div>';

        } elseif ($action === 'edit') {
            $id     = (int)$_POST['id'];
            $nama   = trim($_POST['nama'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '') ?: null;
            $aktif  = isset($_POST['is_active']) ? 1 : 0;
            if ($nama === '') throw new Exception('Nama sekolah wajib diisi.');
            $conn->prepare("UPDATE sekolah_asal SET nama=?, alamat=?, is_active=? WHERE id=?")->execute([$nama, $alamat, $aktif, $id]);
            log_admin_action($conn, 'SEKOLAH_EDIT', "Edit sekolah ID:$id → $nama");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Data sekolah diperbarui.</div>';

        } elseif ($action === 'toggle') {
            $id = (int)$_POST['id'];
            $conn->prepare("UPDATE sekolah_asal SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
            $msg = '<div class="alert alert-info">Status sekolah diperbarui.</div>';

        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $nm = $conn->prepare("SELECT nama FROM sekolah_asal WHERE id=?"); $nm->execute([$id]);
            $nama = $nm->fetchColumn();
            $conn->prepare("DELETE FROM sekolah_asal WHERE id=?")->execute([$id]);
            log_admin_action($conn, 'SEKOLAH_DELETE', "Hapus sekolah: $nama (ID:$id)");
            $msg = '<div class="alert alert-warning"><i class="bi bi-trash me-2"></i>Sekolah <strong>' . htmlspecialchars($nama) . '</strong> dihapus.</div>';
        }
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    }

    $_SESSION['flash_sekolah'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . '?page=sekolah');
    exit;
}

$cari = trim($_GET['cari_sekolah'] ?? '');
if ($cari !== '') {
    $st = $conn->prepare("SELECT * FROM sekolah_asal WHERE nama LIKE ? OR alamat LIKE ? ORDER BY nama");
    $st->execute(["%$cari%", "%$cari%"]);
    $list = $st->fetchAll();
} else {
    $list = $conn->query("SELECT * FROM sekolah_asal ORDER BY nama")->fetchAll();
}
$total_aktif = 0;
foreach ($list as $s) { if ($s['is_active']) $total_aktif++; }
?>

<?= $msg ?>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 fw-bold"><i class="bi bi-building me-2"></i>Kelola Sekolah Asal (SMP)</h4>
        <p class="text-muted mb-0 small">Daftar ini menjadi pilihan dropdown <strong>Asal Sekolah</strong> di Data Pendaftar. Alamat terisi otomatis saat sekolah dipilih.</p>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSekolah" onclick="resetSekolahForm()">
        <i class="bi bi-plus-lg me-1"></i>Tambah Sekolah
    </button>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="hidden" name="page" value="sekolah">
            <input type="text" name="cari_sekolah" class="form-control form-control-sm" style="max-width:280px"
                   placeholder="Cari nama / alamat sekolah..." value="<?= htmlspecialchars($cari) ?>">
            <button class="btn btn-primary btn-sm" type="submit">Cari</button>
            <?php if ($cari !== ''): ?><a href="?page=sekolah" class="btn btn-outline-secondary btn-sm">Reset</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center small">
        <span>Total: <strong><?= count($list) ?></strong> sekolah</span>
        <span><?= $total_aktif ?> aktif</span>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th style="width:40px">#</th>
                <th>Nama Sekolah</th>
                <th>Alamat</th>
                <th class="text-center" style="width:90px">Status</th>
                <th class="text-end" style="width:120px">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($list)): ?>
            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada sekolah. Klik "Tambah Sekolah".</td></tr>
        <?php else: $no = 0; foreach ($list as $s): $no++; ?>
            <tr class="<?= $s['is_active'] ? '' : 'opacity-50' ?>">
                <td class="text-muted small"><?= $no ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($s['nama']) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($s['alamat'] ?? '') ?: '<em>—</em>' ?></td>
                <td class="text-center">
                    <?php if ($s['is_active']): ?>
                        <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary py-0 px-1"
                            onclick='editSekolah(<?= json_encode($s, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Edit"><i class="bi bi-pencil"></i></button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="btn btn-sm btn-outline-secondary py-0 px-1" title="<?= $s['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                            <i class="bi bi-toggle-<?= $s['is_active'] ? 'on text-success' : 'off' ?>"></i>
                        </button>
                    </form>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus sekolah ini?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="modalSekolah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="modalSekolahTitle"><i class="bi bi-building me-2"></i>Tambah Sekolah</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" id="skAction" value="add">
          <input type="hidden" name="id" id="skId" value="">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Sekolah <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="skNama" class="form-control" placeholder="contoh: SMP Negeri 99 Jakarta" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Alamat Sekolah</label>
            <textarea name="alamat" id="skAlamat" class="form-control" rows="2" placeholder="Jl. ..., Kel. ..., Kec. ..., Jakarta Timur"></textarea>
            <small class="text-muted">Alamat ini akan terisi otomatis saat sekolah dipilih di form pendaftar.</small>
          </div>
          <div class="form-check" id="skActiveWrap" style="display:none;">
            <input type="checkbox" name="is_active" id="skActive" class="form-check-input" value="1" checked>
            <label class="form-check-label" for="skActive">Aktif (tampil di dropdown)</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetSekolahForm() {
    document.getElementById('skAction').value = 'add';
    document.getElementById('skId').value = '';
    document.getElementById('skNama').value = '';
    document.getElementById('skAlamat').value = '';
    document.getElementById('skActiveWrap').style.display = 'none';
    document.getElementById('skActive').checked = true;
    document.getElementById('modalSekolahTitle').innerHTML = '<i class="bi bi-building me-2"></i>Tambah Sekolah';
}
function editSekolah(s) {
    document.getElementById('skAction').value = 'edit';
    document.getElementById('skId').value = s.id;
    document.getElementById('skNama').value = s.nama || '';
    document.getElementById('skAlamat').value = s.alamat || '';
    document.getElementById('skActiveWrap').style.display = '';
    document.getElementById('skActive').checked = (parseInt(s.is_active, 10) === 1);
    document.getElementById('modalSekolahTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Sekolah';
    new bootstrap.Modal(document.getElementById('modalSekolah')).show();
}
</script>
