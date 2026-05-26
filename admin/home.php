<?php
$jurusan_list = JURUSAN_LIST;
$short        = JURUSAN_SHORT;

$total       = $conn->query("SELECT COUNT(*) FROM pendaftar")->fetchColumn();
$diterima    = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='terima'")->fetchColumn();
$ditolak     = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='gugur'")->fetchColumn();
$pending     = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='diproses'")->fetchColumn();
$gugur_usia  = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE lolos_usia=0")->fetchColumn();

$per_jurusan = [];
foreach ($jurusan_list as $j) {
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE jurusan=?");
    $s->execute([$j]);
    $per_jurusan[$j] = $s->fetchColumn();
}

$glm = [];
for ($g = 1; $g <= 2; $g++) {
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=?");
    $s->execute([$g]);
    $glm[$g] = $s->fetchColumn();
}

$recent = $conn->query("SELECT no_pendaftaran, nama, jurusan, gelombang, nilai_akhir, status, created_at FROM pendaftar ORDER BY created_at DESC LIMIT 8")->fetchAll();
$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();
$persen_diterima = $total > 0 ? round(($diterima / $total) * 100, 1) : 0;
?>

<style>
.stat-card { border-radius: 14px; padding: 20px; color: #fff; position: relative; overflow: hidden; transition: transform .2s, box-shadow .2s; }
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,.1); }
.stat-card .stat-icon { position: absolute; right: -10px; top: -10px; font-size: 5rem; opacity: .15; }
.stat-card .stat-value { font-size: 2.2rem; font-weight: 700; line-height: 1; margin-bottom: 4px; }
.stat-card .stat-label { font-size: .82rem; opacity: .9; font-weight: 500; }
.stat-card .stat-sub { font-size: .72rem; opacity: .75; margin-top: 6px; }
.stat-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.stat-danger  { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
.stat-warning { background: linear-gradient(135deg, #f7b733 0%, #fc4a1a 100%); }
.stat-secondary { background: linear-gradient(135deg, #485563 0%, #29323c 100%); }

.gel-card { border-radius: 12px; transition: all .2s; border: 1px solid #eaedf0; }
.gel-card .gel-header { padding: 16px 20px; border-bottom: 1px solid #f0f3f5; display: flex; align-items: center; justify-content: space-between; }
.gel-number { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #198754, #20c997); color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.gel-info-row { display: flex; padding: 14px 20px; gap: 16px; border-bottom: 1px solid #f5f7f9; font-size: .85rem; }
.gel-info-row:last-child { border-bottom: 0; }
.gel-info-label { color: #718096; font-size: .75rem; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 2px; }
.gel-info-value { font-weight: 600; color: #2d3748; }

.recent-table { font-size: .88rem; }
.recent-table tbody tr { transition: background .15s; }
.no-pendaftaran { font-family: 'SF Mono', Menlo, monospace; font-size: .8rem; color: #4a5568; background: #f1f5f9; padding: 3px 8px; border-radius: 5px; display: inline-block; }
.progress-mini { height: 4px; border-radius: 2px; }
</style>

<!-- Welcome -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h2 class="mb-1" style="font-weight:700;color:#1a3c34;">Selamat datang, <?= htmlspecialchars($_SESSION['admin_name']) ?> 👋</h2>
        <p class="text-muted mb-0">Ringkasan SPMB SMK Laboratorium Jakarta hari ini.</p>
    </div>
    <a href="?page=pendaftar" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i> Tambah Pendaftar
    </a>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-primary">
            <i class="bi bi-people-fill stat-icon"></i>
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Pendaftar</div>
            <div class="stat-sub">G1: <?= $glm[1] ?? 0 ?> · G2: <?= $glm[2] ?? 0 ?></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-success">
            <i class="bi bi-check-circle-fill stat-icon"></i>
            <div class="stat-value"><?= $diterima ?></div>
            <div class="stat-label">Diterima</div>
            <div class="stat-sub"><?= $persen_diterima ?>% dari total</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-danger">
            <i class="bi bi-x-circle-fill stat-icon"></i>
            <div class="stat-value"><?= $ditolak ?></div>
            <div class="stat-label">Ditolak</div>
            <div class="stat-sub">Tidak lolos seleksi</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-warning">
            <i class="bi bi-hourglass-split stat-icon"></i>
            <div class="stat-value"><?= $pending ?></div>
            <div class="stat-label">Diproses</div>
            <div class="stat-sub">Menunggu seleksi</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="stat-card stat-secondary">
            <i class="bi bi-person-x-fill stat-icon"></i>
            <div class="stat-value"><?= $gugur_usia ?></div>
            <div class="stat-label">Gugur Usia</div>
            <div class="stat-sub">Lebih dari 21 tahun</div>
        </div>
    </div>
</div>

<!-- Gelombang Status -->
<div class="row g-3 mb-4">
    <?php foreach ($gel_rows as $g):
        $kuota_glm = (int)($g['kuota_glm'] ?? round($g['kuota_per_jurusan'] * $g['persen_gelombang'] / 100));
        $total_kuota = $kuota_glm * count(JURUSAN_LIST);
        $pct = $total_kuota > 0 ? min(100, round(($glm[$g['gelombang']] / $total_kuota) * 100)) : 0;
    ?>
    <div class="col-md-6">
        <div class="gel-card bg-white">
            <div class="gel-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="gel-number"><?= $g['gelombang'] ?></div>
                    <div>
                        <div class="fw-bold">Gelombang <?= $g['gelombang'] ?></div>
                        <div class="text-muted small">Ambil <?= $kuota_glm ?> terbaik per jurusan · total <?= $total_kuota ?> kursi</div>
                    </div>
                </div>
                <?php if ($g['is_published']): ?>
                    <span class="badge bg-success"><i class="bi bi-broadcast me-1"></i>Live</span>
                <?php else: ?>
                    <span class="badge bg-light text-dark border">Draft</span>
                <?php endif; ?>
            </div>
            <div class="gel-info-row">
                <div class="flex-fill">
                    <div class="gel-info-label">Pendaftaran</div>
                    <div class="gel-info-value"><?= date('d M', strtotime($g['tanggal_buka'])) ?> – <?= date('d M Y', strtotime($g['tanggal_tutup'])) ?></div>
                </div>
                <div class="flex-fill">
                    <div class="gel-info-label">Pengumuman</div>
                    <div class="gel-info-value"><?= date('d M Y', strtotime($g['tanggal_pengumuman'])) ?></div>
                </div>
            </div>
            <div class="px-3 pb-3 pt-2">
                <div class="d-flex justify-content-between mb-1">
                    <small class="text-muted">Pendaftar masuk</small>
                    <small class="fw-semibold"><?= $glm[$g['gelombang']] ?? 0 ?> / <?= $total_kuota ?></small>
                </div>
                <div class="progress progress-mini">
                    <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart-line me-2 text-success"></i>Pendaftar per Jurusan
            </div>
            <div class="card-body"><canvas id="chartJurusan" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2 text-success"></i>Status Penerimaan
            </div>
            <div class="card-body"><canvas id="chartStatus" height="180"></canvas></div>
        </div>
    </div>
</div>

<!-- Recent -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-success"></i>Pendaftar Terbaru</span>
        <a href="?page=pendaftar" class="btn btn-sm btn-outline-success">Lihat Semua <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover recent-table mb-0">
            <thead>
                <tr><th>No. Daftar</th><th>Nama</th><th>Jurusan</th><th class="text-center">Glm</th><th class="text-center">Nilai</th><th>Status</th><th>Waktu</th></tr>
            </thead>
            <tbody>
            <?php if (empty($recent)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                    Belum ada data pendaftar.
                </td></tr>
            <?php else: foreach ($recent as $r):
                $badge = match($r['status']) { 'terima'=>'bg-success', 'gugur'=>'bg-danger', default=>'bg-warning text-dark' };
            ?>
                <tr>
                    <td><span class="no-pendaftaran"><?= htmlspecialchars($r['no_pendaftaran']) ?></span></td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['nama']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= $short[$r['jurusan']] ?? $r['jurusan'] ?></span></td>
                    <td class="text-center"><?= $r['gelombang'] ?></td>
                    <td class="text-center fw-bold text-success"><?= number_format($r['nilai_akhir'], 2) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('chartJurusan'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_values($short)) ?>,
        datasets: [{
            label: 'Pendaftar',
            data: <?= json_encode(array_values($per_jurusan)) ?>,
            backgroundColor: ['#667eea','#11998e','#eb3349','#f7b733'],
            borderRadius: 8,
            borderSkipped: false,
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

new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Diterima', 'Ditolak', 'Diproses'],
        datasets: [{
            data: [<?= $diterima ?>, <?= $ditolak ?>, <?= $pending ?>],
            backgroundColor: ['#11998e','#eb3349','#f7b733'],
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { padding: 14, font: { size: 12 } } } },
        cutout: '68%'
    }
});
</script>
