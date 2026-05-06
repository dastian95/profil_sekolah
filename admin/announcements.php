<?php
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, type) VALUES (?, ?, ?)");
        $stmt->execute([trim($_POST['title']), trim($_POST['message']), $_POST['type']]);
        $msg = '<div class="alert alert-success">Pengumuman berhasil ditambahkan.</div>';
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $conn->prepare("UPDATE announcements SET is_active = 1 - is_active WHERE id=?")->execute([$id]);
        $msg = '<div class="alert alert-info">Status pengumuman diperbarui.</div>';
    } elseif ($action === 'delete') {
        $conn->prepare("DELETE FROM announcements WHERE id=?")->execute([(int)$_POST['id']]);
        $msg = '<div class="alert alert-warning">Pengumuman dihapus.</div>';
    }
}

$list = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();
$type_colors = ['info'=>'primary','warning'=>'warning','danger'=>'danger','success'=>'success'];
?>

<?= $msg ?>

<div class="card mb-4">
    <div class="card-header fw-semibold">Tambah Pengumuman</div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="row g-3">
                <div class="col-md-5">
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
                <div class="col-md-5">
                    <label class="form-label">Isi Pengumuman</label>
                    <textarea name="message" class="form-control" rows="2" required></textarea>
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
    <table class="table table-hover small mb-0">
        <thead class="table-dark">
            <tr><th>Judul</th><th>Pesan</th><th>Tipe</th><th>Status</th><th>Dibuat</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php if (empty($list)): ?>
            <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada pengumuman.</td></tr>
        <?php else: foreach ($list as $a): ?>
        <tr>
            <td class="fw-semibold"><?= htmlspecialchars($a['title']) ?></td>
            <td><?= htmlspecialchars(mb_substr($a['message'], 0, 80)) ?>...</td>
            <td><span class="badge bg-<?= $type_colors[$a['type']] ?? 'secondary' ?>"><?= ucfirst($a['type']) ?></span></td>
            <td>
                <?php if ($a['is_active']): ?>
                    <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                <?php endif; ?>
            </td>
            <td><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
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
