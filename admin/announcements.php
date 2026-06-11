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
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, type, publish_at, expire_at, target_gelombang, urutan) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([trim($_POST['title']), trim($_POST['message']), $_POST['type'], $publish_at, $expire_at, $target_glm, $urutan]);
        $msg = '<div class="alert alert-success">Pengumuman berhasil ditambahkan.</div>';
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE announcements SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        $msg = '<div class="alert alert-info">Status pengumuman diperbarui.</div>';
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
            <tr><th>#</th><th>Judul</th><th>Tipe</th><th>Jadwal</th><th>Target</th><th>Status</th><th>Dibuat</th><th class="text-end">Aksi</th></tr>
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
            <td class="small text-muted"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
            <td>
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
