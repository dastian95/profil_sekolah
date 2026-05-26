<?php
if (empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>Akses ditolak. Halaman ini hanya untuk Super Admin.</div>';
    return;
}

// Hapus log lama (opsional)
if (($_POST['action'] ?? '') === 'clear_old') {
    $days = (int)($_POST['days'] ?? 30);
    $stmt = $conn->prepare("DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->execute([$days]);
    $deleted = $stmt->rowCount();
    $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'LOG_CLEAR', ?, ?)");
    $log->execute(["Superadmin hapus log lebih dari $days hari ($deleted rows)", $_SERVER['REMOTE_ADDR']]);
    $flash = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>'.$deleted.' log lama dihapus.</div>';
}

// Filter
$f_action = $_GET['f_action'] ?? '';
$f_admin  = $_GET['f_admin']  ?? '';
$f_search = trim($_GET['q']   ?? '');

$where = ['1=1']; $params = [];
if ($f_action) { $where[] = 'l.action=?'; $params[] = $f_action; }
if ($f_admin === 'super') {
    $where[] = 'l.admin_id IS NULL';
} elseif ($f_admin !== '') {
    $where[] = 'l.admin_id=?'; $params[] = (int)$f_admin;
}
if ($f_search) { $where[] = '(l.details LIKE ? OR l.ip_address LIKE ?)'; $params[] = "%$f_search%"; $params[] = "%$f_search%"; }

$where_sql = implode(' AND ', $where);

// Pagination
$page_num = max(1, (int)($_GET['p'] ?? 1));
$per = 30;
$offset = ($page_num - 1) * $per;

$total_stmt = $conn->prepare("SELECT COUNT(*) FROM admin_logs l WHERE $where_sql");
$total_stmt->execute($params);
$total = (int)$total_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));

$sql = "SELECT l.*, a.username, a.name AS admin_name
        FROM admin_logs l LEFT JOIN admins a ON l.admin_id=a.id
        WHERE $where_sql ORDER BY l.created_at DESC LIMIT $per OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Stats
$stats = $conn->query("SELECT action, COUNT(*) AS n FROM admin_logs GROUP BY action ORDER BY n DESC")->fetchAll();
$actions_list = array_column($stats, 'action');
$admins_list = $conn->query("SELECT id, username, name FROM admins ORDER BY username")->fetchAll();

$action_colors = [
    'LOGIN'           => 'success',
    'LOGIN_SUPER'     => 'warning',
    'LOGIN_FAILED'    => 'danger',
    'LOGOUT'          => 'secondary',
    'LOGOUT_SUPER'    => 'secondary',
    'CHANGE_PASSWORD' => 'info',
    'ADMIN_CREATE'    => 'primary',
    'ADMIN_EDIT'      => 'primary',
    'ADMIN_DELETE'    => 'danger',
    'ADMIN_RESET_PWD' => 'warning',
    'EXPORT_CSV'      => 'info',
    'UPDATE_GELOMBANG'=> 'info',
    'PUBLISH_PENGUMUMAN'  => 'success',
    'UNPUBLISH_PENGUMUMAN'=> 'warning',
    'PROSES_PENERIMAAN'   => 'success',
    'LOG_CLEAR'       => 'dark',
];
?>

<?= $flash ?? '' ?>

<div class="alert alert-warning small mb-3">
    <i class="bi bi-shield-fill-check me-1"></i>
    <strong>Audit Log</strong> — Semua aktivitas admin tercatat di sini. Hanya superadmin yang bisa lihat & hapus log.
</div>

<!-- Stats summary -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card"><div class="card-body text-center">
            <i class="bi bi-journal-text text-primary fs-2"></i>
            <div class="fs-3 fw-bold"><?= number_format(array_sum(array_column($stats,'n'))) ?></div>
            <small class="text-muted">Total Log</small>
        </div></div>
    </div>
    <?php
    $login_count  = (int)$conn->query("SELECT COUNT(*) FROM admin_logs WHERE action IN ('LOGIN','LOGIN_SUPER')")->fetchColumn();
    $failed_count = (int)$conn->query("SELECT COUNT(*) FROM admin_logs WHERE action='LOGIN_FAILED'")->fetchColumn();
    $today_count  = (int)$conn->query("SELECT COUNT(*) FROM admin_logs WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    ?>
    <div class="col-md-3"><div class="card"><div class="card-body text-center">
        <i class="bi bi-box-arrow-in-right text-success fs-2"></i>
        <div class="fs-3 fw-bold"><?= $login_count ?></div>
        <small class="text-muted">Login Sukses</small>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center">
        <i class="bi bi-x-octagon text-danger fs-2"></i>
        <div class="fs-3 fw-bold"><?= $failed_count ?></div>
        <small class="text-muted">Login Gagal</small>
    </div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center">
        <i class="bi bi-calendar-day text-info fs-2"></i>
        <div class="fs-3 fw-bold"><?= $today_count ?></div>
        <small class="text-muted">Aktivitas Hari Ini</small>
    </div></div></div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <input type="hidden" name="page" value="audit_log">
            <div class="col-md-3">
                <select name="f_admin" class="form-select form-select-sm">
                    <option value="">Semua Admin</option>
                    <option value="super" <?= $f_admin==='super'?'selected':'' ?>>👑 Superadmin (hardcoded)</option>
                    <?php foreach ($admins_list as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $f_admin==(string)$a['id']?'selected':'' ?>><?= htmlspecialchars($a['username']) ?> — <?= htmlspecialchars($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="f_action" class="form-select form-select-sm">
                    <option value="">Semua Aksi</option>
                    <?php foreach ($actions_list as $act): ?>
                        <option value="<?= $act ?>" <?= $f_action===$act?'selected':'' ?>><?= $act ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="text" name="q" value="<?= htmlspecialchars($f_search) ?>" class="form-control form-control-sm" placeholder="Cari detail / IP...">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Log Aktivitas (<?= $total ?> total)</span>
        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalClear">
            <i class="bi bi-trash me-1"></i>Hapus Log Lama
        </button>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover small mb-0">
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Pelaku</th>
                <th>Aksi</th>
                <th>Detail</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($logs)): ?>
            <tr><td colspan="5" class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>Tidak ada log.
            </td></tr>
        <?php else: foreach ($logs as $l):
            $color = $action_colors[$l['action']] ?? 'secondary';
            $is_super_log = empty($l['admin_id']);
        ?>
            <tr>
                <td class="text-muted"><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
                <td>
                    <?php if ($is_super_log): ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-shield-fill-check me-1"></i>Superadmin</span>
                    <?php else: ?>
                        <strong><?= htmlspecialchars($l['username'] ?? '?') ?></strong>
                        <small class="text-muted d-block"><?= htmlspecialchars($l['admin_name'] ?? '') ?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> border border-<?= $color ?>-subtle"><?= htmlspecialchars($l['action']) ?></span></td>
                <td><?= htmlspecialchars($l['details']) ?></td>
                <td><code class="small"><?= htmlspecialchars($l['ip_address']) ?></code></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer">
        <nav><ul class="pagination pagination-sm justify-content-center mb-0">
            <?php for ($i = 1; $i <= min($pages, 10); $i++):
                $qs = $_GET; $qs['p'] = $i; ?>
                <li class="page-item <?= $i==$page_num?'active':'' ?>">
                    <a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal hapus log lama -->
<div class="modal fade" id="modalClear" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Hapus Log Lama</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="clear_old">
          <p class="small text-muted">Hapus semua log yang lebih lama dari periode tertentu untuk menghemat space database.</p>
          <label class="form-label">Hapus log yang lebih lama dari:</label>
          <select name="days" class="form-select">
            <option value="7">7 hari</option>
            <option value="30" selected>30 hari</option>
            <option value="60">60 hari</option>
            <option value="90">90 hari</option>
            <option value="180">6 bulan</option>
            <option value="365">1 tahun</option>
          </select>
          <div class="alert alert-warning small mt-3 mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>Aksi ini tidak bisa di-undo.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin hapus log lama?')">
            <i class="bi bi-trash me-1"></i>Hapus
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
