<?php
$msg = '';
if (!empty($_SESSION['flash_announcements'])) {
    $msg = $_SESSION['flash_announcements'];
    unset($_SESSION['flash_announcements']);
}

// Auto-migrate: kolom jadwal & urutan
foreach ([
    "ALTER TABLE announcements ADD COLUMN publish_at DATETIME NULL AFTER is_active",
    "ALTER TABLE announcements ADD COLUMN expire_at DATETIME NULL AFTER publish_at",
    "ALTER TABLE announcements ADD COLUMN target_gelombang TINYINT NULL AFTER expire_at",
    "ALTER TABLE announcements ADD COLUMN urutan TINYINT NOT NULL DEFAULT 0 AFTER target_gelombang",
    "ALTER TABLE announcements ADD COLUMN updated_at DATETIME NULL AFTER created_at",
    "ALTER TABLE announcements ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active",
] as $_asql) {
    try { $conn->exec($_asql); } catch(PDOException) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $publish_at = trim($_POST['publish_at'] ?? '') ?: null;
        $expire_at  = trim($_POST['expire_at']  ?? '') ?: null;
        $target_glm = trim($_POST['target_gelombang'] ?? '') !== '' ? (int)$_POST['target_gelombang'] : null;
        $urutan     = (int)($_POST['urutan'] ?? 0);
        $is_public  = isset($_POST['is_public']) ? 1 : 0;
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, type, publish_at, expire_at, target_gelombang, urutan, is_public) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([trim($_POST['title']), trim($_POST['message']), $_POST['type'], $publish_at, $expire_at, $target_glm, $urutan, $is_public]);
        $msg = '<div class="alert alert-success">Pengumuman berhasil ditambahkan.</div>';
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE announcements SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        $msg = '<div class="alert alert-info">Status pengumuman diperbarui.</div>';
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        // Cek window 3 jam dari created_at
        $chk = $conn->prepare("SELECT created_at FROM announcements WHERE id=?");
        $chk->execute([$id]);
        $row = $chk->fetch();
        if ($row && (time() - strtotime($row['created_at'])) <= 10800) {
            $publish_at = trim($_POST['publish_at'] ?? '') ?: null;
            $expire_at  = trim($_POST['expire_at']  ?? '') ?: null;
            $target_glm = trim($_POST['target_gelombang'] ?? '') !== '' ? (int)$_POST['target_gelombang'] : null;
            $urutan     = (int)($_POST['urutan'] ?? 0);
            $is_public  = isset($_POST['is_public']) ? 1 : 0;
            $conn->prepare("UPDATE announcements SET title=?, message=?, type=?, publish_at=?, expire_at=?, target_gelombang=?, urutan=?, is_public=?, updated_at=NOW() WHERE id=?")
                 ->execute([trim($_POST['title']), trim($_POST['message']), $_POST['type'], $publish_at, $expire_at, $target_glm, $urutan, $is_public, $id]);
            $msg = '<div class="alert alert-success">Pengumuman berhasil diperbarui.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Tidak dapat mengedit — sudah lebih dari 3 jam sejak dibuat.</div>';
        }
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM announcements WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = '<div class="alert alert-warning">Pengumuman dihapus.</div>';
    }

    // PRG: redirect setelah POST agar refresh tidak mengulang aksi
    $_SESSION['flash_announcements'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . '?page=announcements');
    exit;
}

$list = $conn->query("SELECT * FROM announcements ORDER BY urutan ASC, created_at DESC")->fetchAll();
$type_colors = ['info'=>'primary','warning'=>'warning','danger'=>'danger','success'=>'success'];
?>

<?= $msg ?>

<div class="card mb-4">
    <div class="card-header fw-semibold">Tambah Pengumuman</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Judul</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select">
                        <option value="info">Info</option>
                        <option value="success">Sukses</option>
                        <option value="warning">Peringatan</option>
                        <option value="danger">Penting</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Isi Pengumuman</label>
                    <textarea name="message" class="form-control" rows="2" required></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tayang Mulai <small class="text-muted">(opsional)</small></label>
                    <input type="datetime-local" name="publish_at" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kadaluarsa <small class="text-muted">(opsional)</small></label>
                    <input type="datetime-local" name="expire_at" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Target Gelombang <small class="text-muted">(opsional)</small></label>
                    <select name="target_gelombang" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="1">Gelombang 1</option>
                        <option value="2">Gelombang 2</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Urutan <small class="text-muted">(kecil = atas)</small></label>
                    <input type="number" name="urutan" class="form-control form-control-sm" value="0" min="0">
                </div>
                <div class="col-12">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_public" id="addIsPublic" class="form-check-input" value="1">
                        <label class="form-check-label" for="addIsPublic">
                            <i class="bi bi-globe me-1"></i>Tampilkan di halaman publik (website)
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Tambah
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Daftar Pengumuman</div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
        <thead>
            <tr><th>#</th><th>Judul</th><th>Tipe</th><th>Jadwal</th><th>Target</th><th>Publik</th><th>Status</th><th>Dibuat</th><th class="text-end">Aksi</th></tr>
        </thead>
        <tbody>
        <?php if (empty($list)): ?>
            <tr><td colspan="8" class="text-center py-3 text-muted">Belum ada pengumuman.</td></tr>
        <?php else:
        $now = new DateTime();
        foreach ($list as $a):
            $is_scheduled = $a['publish_at'] && new DateTime($a['publish_at']) > $now;
            $is_expired   = $a['expire_at']  && new DateTime($a['expire_at'])  < $now;
        ?>
        <tr class="<?= $is_expired ? 'opacity-50' : '' ?>">
            <td class="text-muted small"><?= (int)($a['urutan'] ?? 0) ?></td>
            <td>
                <div class="fw-semibold"><?= htmlspecialchars($a['title']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars(mb_substr($a['message'], 0, 60)) ?>...</div>
            </td>
            <td><span class="badge bg-<?= $type_colors[$a['type']] ?? 'secondary' ?>"><?= ucfirst($a['type']) ?></span></td>
            <td class="small text-muted">
                <?php if ($a['publish_at']): ?><div><i class="bi bi-play-circle me-1 text-success"></i><?= date('d/m/y H:i', strtotime($a['publish_at'])) ?></div><?php endif; ?>
                <?php if ($a['expire_at']): ?><div><i class="bi bi-stop-circle me-1 text-danger"></i><?= date('d/m/y H:i', strtotime($a['expire_at'])) ?></div><?php endif; ?>
                <?php if (!$a['publish_at'] && !$a['expire_at']): ?>—<?php endif; ?>
            </td>
            <td class="small">
                <?php if ($a['target_gelombang']): ?>
                    <span class="badge bg-secondary">Glm <?= $a['target_gelombang'] ?></span>
                <?php else: ?>
                    <span class="text-muted">Semua</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <?php if ($a['is_public'] ?? 0): ?>
                    <span class="badge bg-success" title="Tampil di website"><i class="bi bi-globe"></i></span>
                <?php else: ?>
                    <span class="badge bg-secondary" title="Internal admin saja"><i class="bi bi-lock"></i></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($is_expired): ?>
                    <span class="badge bg-danger">Kadaluarsa</span>
                <?php elseif ($is_scheduled): ?>
                    <span class="badge bg-warning text-dark">Terjadwal</span>
                <?php elseif ($a['is_active']): ?>
                    <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                <?php endif; ?>
            </td>
            <td class="small text-muted">
                <?= date('d/m/Y H:i', strtotime($a['created_at'])) ?>
                <?php if ($a['updated_at']): ?>
                    <div class="text-muted" style="font-size:.7rem;">diedit <?= date('H:i', strtotime($a['updated_at'])) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <?php $canEdit = (time() - strtotime($a['created_at'])) <= 10800; ?>
                <?php if ($canEdit): ?>
                <button class="btn btn-sm btn-outline-primary py-0 px-1 me-1"
                        onclick="openEditAnn(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)"
                        title="Edit (tersedia 3 jam)">
                    <i class="bi bi-pencil"></i>
                </button>
                <?php endif; ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <button class="btn btn-sm btn-outline-secondary py-0 px-1">
                        <i class="bi bi-toggle-<?= $a['is_active']?'on text-success':'off' ?>"></i>
                    </button>
                </form>
                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus pengumuman ini?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <button class="btn btn-sm btn-danger py-0 px-1"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>

<!-- Modal Edit Pengumuman -->
<div class="modal fade" id="modalEditAnn" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id" id="eAnnId">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Pengumuman</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Judul</label>
              <input type="text" name="title" id="eAnnTitle" class="form-control" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Tipe</label>
              <select name="type" id="eAnnType" class="form-select">
                <option value="info">Info</option>
                <option value="success">Sukses</option>
                <option value="warning">Peringatan</option>
                <option value="danger">Penting</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Isi Pengumuman</label>
              <textarea name="message" id="eAnnMessage" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tayang Mulai</label>
              <input type="datetime-local" name="publish_at" id="eAnnPublish" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
              <label class="form-label">Kadaluarsa</label>
              <input type="datetime-local" name="expire_at" id="eAnnExpire" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
              <label class="form-label">Target Gelombang</label>
              <select name="target_gelombang" id="eAnnGlm" class="form-select form-select-sm">
                <option value="">Semua</option>
                <option value="1">Gelombang 1</option>
                <option value="2">Gelombang 2</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Urutan</label>
              <input type="number" name="urutan" id="eAnnUrutan" class="form-control form-control-sm" min="0">
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
                <input type="checkbox" name="is_public" id="eAnnIsPublic" class="form-check-input" value="1">
                <label class="form-check-label" for="eAnnIsPublic">
                    <i class="bi bi-globe me-1"></i>Tampilkan di halaman publik (website)
                </label>
            </div>
          </div>
          <div class="alert alert-warning mt-3 py-2 small mb-0">
            <i class="bi bi-clock me-1"></i>Edit hanya tersedia dalam <strong>3 jam</strong> sejak pengumuman dibuat.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditAnn(a) {
    document.getElementById('eAnnId').value      = a.id;
    document.getElementById('eAnnTitle').value   = a.title;
    document.getElementById('eAnnType').value    = a.type;
    document.getElementById('eAnnMessage').value = a.message;
    document.getElementById('eAnnPublish').value = a.publish_at ? a.publish_at.replace(' ','T').slice(0,16) : '';
    document.getElementById('eAnnExpire').value  = a.expire_at  ? a.expire_at.replace(' ','T').slice(0,16)  : '';
    document.getElementById('eAnnGlm').value     = a.target_gelombang || '';
    document.getElementById('eAnnUrutan').value    = a.urutan || 0;
    document.getElementById('eAnnIsPublic').checked = !!(a.is_public && parseInt(a.is_public));
    new bootstrap.Modal(document.getElementById('modalEditAnn')).show();
}
</script>
