<?php
if (empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>Akses ditolak.</div>';
    return;
}

// Info sistem
$db_size_row = $conn->query("SELECT
    SUM(data_length + index_length) AS size,
    COUNT(*) AS tables
    FROM information_schema.tables WHERE table_schema = DATABASE()")->fetch();

$mysql_ver = $conn->query("SELECT VERSION()")->fetchColumn();

$tables = $conn->query("SELECT table_name, table_rows, ROUND((data_length + index_length)/1024, 2) AS size_kb
    FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY (data_length + index_length) DESC")->fetchAll();

$counts = [
    'admin'         => (int)$conn->query("SELECT COUNT(*) FROM admins")->fetchColumn(),
    'pendaftar'     => (int)$conn->query("SELECT COUNT(*) FROM pendaftar")->fetchColumn(),
    'gelombang'     => (int)$conn->query("SELECT COUNT(*) FROM gelombang")->fetchColumn(),
    'announcements' => (int)$conn->query("SELECT COUNT(*) FROM announcements")->fetchColumn(),
    'admin_logs'    => (int)$conn->query("SELECT COUNT(*) FROM admin_logs")->fetchColumn(),
];

$disk_total = disk_total_space(__DIR__);
$disk_free  = disk_free_space(__DIR__);
$disk_used  = $disk_total - $disk_free;

function fmt_bytes($b) {
    if ($b < 1024) return "$b B";
    if ($b < 1024*1024) return number_format($b/1024, 1) . ' KB';
    if ($b < 1024*1024*1024) return number_format($b/(1024*1024), 1) . ' MB';
    return number_format($b/(1024*1024*1024), 2) . ' GB';
}
?>

<div class="alert alert-warning small mb-3">
    <i class="bi bi-shield-fill-check me-1"></i>
    <strong>System Info</strong> — Hanya superadmin yang dapat melihat info sistem & database.
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-server text-primary fs-1"></i>
            <div>
                <small class="text-muted">PHP Version</small>
                <div class="fw-bold"><?= PHP_VERSION ?></div>
            </div>
        </div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-database text-info fs-1"></i>
            <div>
                <small class="text-muted">MySQL/MariaDB</small>
                <div class="fw-bold"><?= htmlspecialchars($mysql_ver) ?></div>
            </div>
        </div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-hdd text-success fs-1"></i>
            <div>
                <small class="text-muted">Ukuran Database</small>
                <div class="fw-bold"><?= fmt_bytes((int)$db_size_row['size']) ?></div>
                <small class="text-muted"><?= $db_size_row['tables'] ?> tabel</small>
            </div>
        </div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-clock-history text-warning fs-1"></i>
            <div>
                <small class="text-muted">Server Time</small>
                <div class="fw-bold small"><?= date('d M Y H:i') ?></div>
                <small class="text-muted"><?= date_default_timezone_get() ?></small>
            </div>
        </div>
    </div></div></div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><i class="bi bi-table me-2"></i>Tabel Database</div>
            <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>Tabel</th><th class="text-end">Rows</th><th class="text-end">Ukuran</th></tr>
                </thead>
                <tbody>
                <?php foreach ($tables as $t): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($t['table_name']) ?></code></td>
                        <td class="text-end"><?= number_format((int)$t['table_rows']) ?></td>
                        <td class="text-end"><?= number_format((float)$t['size_kb'], 2) ?> KB</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Konfigurasi</div>
            <div class="card-body small">
                <div class="row mb-2"><div class="col-5 text-muted">App Name</div><div class="col-7 fw-semibold"><?= htmlspecialchars($_ENV['APP_NAME'] ?? '-') ?></div></div>
                <div class="row mb-2"><div class="col-5 text-muted">App URL</div><div class="col-7"><code><?= htmlspecialchars($_ENV['APP_URL'] ?? '-') ?></code></div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Admin Key</div><div class="col-7"><code><?= htmlspecialchars(substr($_ENV['ADMIN_KEY'] ?? '', 0, 3) . '****') ?></code></div></div>
                <div class="row mb-2"><div class="col-5 text-muted">Database</div><div class="col-7"><code><?= htmlspecialchars($_ENV['DB_NAME'] ?? '-') ?></code></div></div>
                <div class="row mb-2"><div class="col-5 text-muted">DB Host</div><div class="col-7"><code><?= htmlspecialchars($_ENV['DB_HOST'] ?? '-') ?></code></div></div>
                <div class="row"><div class="col-5 text-muted">Timezone</div><div class="col-7"><?= htmlspecialchars($_ENV['APP_TIMEZONE'] ?? '-') ?></div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-hdd me-2"></i>Disk Server</div>
            <div class="card-body">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Terpakai</span>
                    <span class="fw-semibold"><?= fmt_bytes($disk_used) ?> / <?= fmt_bytes($disk_total) ?></span>
                </div>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-success" style="width: <?= round(($disk_used/$disk_total)*100) ?>%"></div>
                </div>
                <small class="text-muted">Free: <?= fmt_bytes($disk_free) ?></small>
            </div>
        </div>
    </div>
</div>
