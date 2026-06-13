<?php
$jurusan_list = JURUSAN_LIST;
$short        = JURUSAN_SHORT;

// ── Stats utama ───────────────────────────────────────────────────────────────
$total      = $conn->query("SELECT COUNT(*) FROM pendaftar")->fetchColumn();
$diterima   = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='terima'")->fetchColumn();
$ditolak    = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='gugur'")->fetchColumn();
$pending    = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='diproses'")->fetchColumn();
$total_admin = $conn->query("SELECT COUNT(*) FROM admins")->fetchColumn();

// ── Konfigurasi status ────────────────────────────────────────────────────────
$total_tahapan = 0;
$total_meja    = 0;
try { $total_tahapan = $conn->query("SELECT COUNT(*) FROM tahapan WHERE is_active=1")->fetchColumn(); } catch(Throwable) {}
try { $total_meja = $conn->query("SELECT COUNT(*) FROM meja WHERE is_active=1")->fetchColumn(); } catch(Throwable) {}

// ── Per jurusan ───────────────────────────────────────────────────────────────
$per_jurusan = [];
foreach ($jurusan_list as $j) {
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE jurusan=?");
    $s->execute([$j]);
    $per_jurusan[$j] = $s->fetchColumn();
}

$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();

// ── Recent admin activity ─────────────────────────────────────────────────────
$recent_logs = $conn->query("SELECT l.*, a.name AS admin_name, a.username
    FROM admin_logs l
    LEFT JOIN admins a ON l.admin_id = a.id
    ORDER BY l.created_at DESC LIMIT 12")->fetchAll();

// ── Admin login terakhir ──────────────────────────────────────────────────────
$admins = $conn->query("SELECT a.id, a.name, a.username,
    (SELECT MAX(created_at) FROM admin_logs WHERE admin_id=a.id AND action='LOGIN') AS last_login,
    (SELECT COUNT(*) FROM admin_tahapan WHERE admin_id=a.id) AS tahapan_count
    FROM admins a ORDER BY a.name")->fetchAll();

$persen_diterima = $total > 0 ? round(($diterima / $total) * 100, 1) : 0;
?>

<style>
.stat-card { border-radius: 14px; padding: 20px; color: #fff; position: relative; overflow: hidden; transition: transform .2s; cursor: default; }
.stat-card:hover { transform: translateY(-3px); }
.stat-card .stat-icon { position: absolute; right: -10px; top: -10px; font-size: 5rem; opacity: .13; }
.stat-card .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; margin-bottom: 4px; }
.stat-card .stat-label { font-size: .82rem; opacity: .9; font-weight: 500; }
.stat-card .stat-sub { font-size: .72rem; opacity: .72; margin-top: 5px; }
.sc-purple  { background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); }
.sc-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.sc-danger  { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
.sc-warning { background: linear-gradient(135deg, #f7b733 0%, #fc4a1a 100%); }
.sc-blue    { background: linear-gradient(135deg, #2196f3 0%, #21cbf3 100%); }
.sc-dark    { background: linear-gradient(135deg, #485563 0%, #29323c 100%); }

.config-card { border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; border: 1px solid #eaedf0; transition: all .2s; text-decoration: none; color: inherit; }
.config-card:hover { border-color: #7c3aed; box-shadow: 0 4px 16px rgba(124,58,237,.1); transform: translateY(-2px); color: inherit; }
.config-card .cc-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.config-card .cc-value { font-size: 1.5rem; font-weight: 700; line-height: 1; }
.config-card .cc-label { font-size: .8rem; color: #718096; margin-top: 2px; }

.activity-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f5f7f9; font-size: .82rem; }
.activity-item:last-child { border-bottom: 0; }
.activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
.activity-time { color: #a0aec0; white-space: nowrap; font-size: .75rem; }

.admin-status-row td { padding: 10px 12px; vertical-align: middle; }
</style>

<!-- Header -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1 fw-bold" style="color:#1e1b4b;">
            <i class="bi bi-shield-fill-check me-2" style="color:#7c3aed;"></i>Super Admin Dashboard
        </h2>
        <p class="text-muted mb-0 small">Panel kontrol penuh SPMB SMK Laboratorium Jakarta</p>
    </div>
    <span class="badge" style="background:linear-gradient(135deg,#f7b733,#fc4a1a);font-size:.82rem;padding:.5em 1em;">
        <i class="bi bi-star-fill me-1"></i>Super Admin · <?= date('d M Y') ?>
    </span>
</div>

<?php include __DIR__ . '/_announcements_widget.php'; ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card sc-purple">
            <i class="bi bi-people-fill stat-icon"></i>
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Pendaftar</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card sc-success">
            <i class="bi bi-check-circle-fill stat-icon"></i>
            <div class="stat-value"><?= $diterima ?></div>
            <div class="stat-label">Diterima</div>
            <div class="stat-sub"><?= $persen_diterima ?>%</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card sc-danger">
            <i class="bi bi-x-circle-fill stat-icon"></i>
            <div class="stat-value"><?= $ditolak ?></div>
            <div class="stat-label">Ditolak</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card sc-warning">
            <i class="bi bi-hourglass-split stat-icon"></i>
            <div class="stat-value"><?= $pending ?></div>
            <div class="stat-label">Diproses</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card sc-blue">
            <i class="bi bi-person-badge-fill stat-icon"></i>
            <div class="stat-value"><?= $total_admin ?></div>
            <div class="stat-label">Admin Aktif</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card sc-dark">
            <i class="bi bi-diagram-3-fill stat-icon"></i>
            <div class="stat-value"><?= $total_tahapan ?></div>
            <div class="stat-label">Tahapan Aktif</div>
        </div>
    </div>
</div>

<!-- Quick Config Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="?page=alur" class="config-card bg-white d-block">
            <div class="cc-icon" style="background:#ede9fe;color:#7c3aed;"><i class="bi bi-diagram-3-fill"></i></div>
            <div>
                <div class="cc-value"><?= $total_tahapan ?></div>
                <div class="cc-label">Tahapan Pendaftaran</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?page=meja" class="config-card bg-white d-block">
            <div class="cc-icon" style="background:#fef3c7;color:#d97706;"><i class="bi bi-grid-3x2-gap-fill"></i></div>
            <div>
                <div class="cc-value"><?= $total_meja ?></div>
                <div class="cc-label">Meja Antrian Aktif</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?page=kelola_admin" class="config-card bg-white d-block">
            <div class="cc-icon" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="cc-value"><?= $total_admin ?></div>
                <div class="cc-label">Admin Terdaftar</div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?page=gelombang" class="config-card bg-white d-block">
            <div class="cc-icon" style="background:#d1fae5;color:#059669;"><i class="bi bi-calendar-week"></i></div>
            <div>
                <div class="cc-value"><?= count($gel_rows) ?></div>
                <div class="cc-label">Gelombang Dikonfigurasi</div>
            </div>
        </a>
    </div>
</div>

<!-- Grafik + Aktivitas -->
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart-line me-2" style="color:#7c3aed;"></i>Pendaftar per Jurusan</div>
            <div class="card-body"><canvas id="chartJurusan" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-activity me-2" style="color:#7c3aed;"></i>Aktivitas Admin Terbaru</span>
                <a href="?page=audit_log" class="btn btn-sm btn-outline-secondary">Semua Log</a>
            </div>
            <div class="card-body p-3" style="overflow-y:auto;max-height:280px;">
                <?php if (empty($recent_logs)): ?>
                    <div class="text-center text-muted py-3"><i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>Belum ada aktivitas.</div>
                <?php else: foreach ($recent_logs as $log):
                    $dotColor = match($log['action']) {
                        'LOGIN','LOGIN_SUPER' => '#22c55e',
                        'LOGIN_FAILED' => '#ef4444',
                        'ADMIN_CREATE','ADMIN_EDIT','ADMIN_DELETE' => '#f59e0b',
                        'PROSES_RANKING' => '#7c3aed',
                        default => '#94a3b8'
                    };
                    $actorName = $log['admin_id'] === null ? 'Superadmin' : (htmlspecialchars($log['admin_name'] ?? '?'));
                ?>
                    <div class="activity-item">
                        <div class="activity-dot" style="background:<?= $dotColor ?>;"></div>
                        <div class="flex-grow-1">
                            <span class="fw-semibold"><?= $actorName ?></span>
                            <span class="text-muted ms-1">—</span>
                            <span class="ms-1"><?= htmlspecialchars($log['action']) ?></span>
                            <?php if ($log['details']): ?>
                                <div class="text-muted small mt-1"><?= htmlspecialchars(mb_strimwidth($log['details'], 0, 80, '…')) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="activity-time"><?= date('d/m H:i', strtotime($log['created_at'])) ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Status Admin -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2" style="color:#7c3aed;"></i>Status Admin</span>
        <a href="?page=kelola_admin" class="btn btn-sm btn-outline-primary">Kelola Admin</a>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Username</th>
                <th>Tahapan di-assign</th>
                <th>Login Terakhir</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $a): ?>
        <tr class="admin-status-row">
            <td class="fw-semibold"><?= htmlspecialchars($a['name']) ?></td>
            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($a['username']) ?></span></td>
            <td>
                <?php if ($a['tahapan_count'] > 0):
                    $tStmt = $conn->prepare("SELECT t.nama, t.icon FROM tahapan t
                        JOIN admin_tahapan at ON at.tahap_id = t.id
                        WHERE at.admin_id = ? ORDER BY t.urutan");
                    $tStmt->execute([$a['id']]);
                    foreach ($tStmt as $t): ?>
                        <span class="badge me-1" style="background:#ede9fe;color:#7c3aed;font-weight:500;">
                            <i class="bi <?= htmlspecialchars($t['icon']) ?> me-1"></i><?= htmlspecialchars($t['nama']) ?>
                        </span>
                    <?php endforeach;
                else: ?>
                    <span class="text-muted small fst-italic">Belum ada tahapan</span>
                <?php endif; ?>
            </td>
            <td class="text-muted small">
                <?= $a['last_login'] ? date('d M Y, H:i', strtotime($a['last_login'])) : '<em>Belum pernah</em>' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>

<!-- Gelombang info -->
<div class="row g-3 mb-2">
<?php foreach ($gel_rows as $g):
    $kuota_glm = (int)($g['kuota_glm'] ?? round($g['kuota_per_jurusan'] * $g['persen_gelombang'] / 100));
    $s2 = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=?");
    $s2->execute([$g['gelombang']]);
    $total_g = (int)$s2->fetchColumn();
    $total_kuota = $kuota_glm * count(JURUSAN_LIST);
    $pct = $total_kuota > 0 ? min(100, round(($total_g / $total_kuota) * 100)) : 0;
?>
<div class="col-md-6">
    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="fw-semibold">Gelombang <?= $g['gelombang'] ?></div>
                <?= $g['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Draft</span>' ?>
            </div>
            <div class="small text-muted mb-2">
                <?= date('d M', strtotime($g['tanggal_buka'])) ?> – <?= date('d M Y', strtotime($g['tanggal_tutup'])) ?> ·
                Kuota <?= $kuota_glm ?>/jurusan · <?= $total_g ?>/<?= $total_kuota ?> pendaftar
            </div>
            <div class="progress" style="height:6px;border-radius:3px;">
                <div class="progress-bar" style="width:<?= $pct ?>%;background:linear-gradient(90deg,#7c3aed,#a855f7);"></div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
new Chart(document.getElementById('chartJurusan'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_values($short)) ?>,
        datasets: [{
            label: 'Pendaftar',
            data: <?= json_encode(array_values($per_jurusan)) ?>,
            backgroundColor: ['#7c3aed','#a855f7','#c084fc','#ddd6fe'],
            borderRadius: 8, borderSkipped: false,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: '#f0f3f5' } },
            x: { ticks: { font: { size: 11 } }, grid: { display: false } }
        }
    }
});
</script>
