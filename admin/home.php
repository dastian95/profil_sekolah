<?php
$jurusan_list = [
    'Rekayasa Perangkat Lunak (RPL)',
    'Teknik Komputer dan Jaringan (TKJ)',
    'Asisten Keperawatan (AP)',
    'Tata Kecantikan Kulit dan Rambut (TKKR)',
];

// Statistik ringkas
$total       = $conn->query("SELECT COUNT(*) FROM pendaftar")->fetchColumn();
$diterima    = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='diterima'")->fetchColumn();
$ditolak     = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='ditolak'")->fetchColumn();
$pending     = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='pending'")->fetchColumn();
$gugur_usia  = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE lolos_usia=0")->fetchColumn();

// Per jurusan
$per_jurusan = [];
foreach ($jurusan_list as $j) {
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE jurusan=?");
    $s->execute([$j]);
    $per_jurusan[$j] = $s->fetchColumn();
}

// Per gelombang
$glm = [];
for ($g = 1; $g <= 2; $g++) {
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=?");
    $s->execute([$g]);
    $glm[$g] = $s->fetchColumn();
}

// Pendaftar terbaru
$recent = $conn->query("SELECT no_pendaftaran, nama, jurusan, gelombang, nilai_akhir, status, created_at FROM pendaftar ORDER BY created_at DESC LIMIT 8")->fetchAll();

// Gelombang info
$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();

$short = ['Rekayasa Perangkat Lunak (RPL)'=>'RPL','Teknik Komputer dan Jaringan (TKJ)'=>'TKJ','Asisten Keperawatan (AP)'=>'AP','Tata Kecantikan Kulit dan Rambut (TKKR)'=>'TKKR'];
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Pendaftar',  $total,    'bg-primary',   'bi-people-fill'],
        ['Diterima',         $diterima, 'bg-success',   'bi-check-circle-fill'],
        ['Ditolak',          $ditolak,  'bg-danger',    'bi-x-circle-fill'],
        ['Belum Diproses',   $pending,  'bg-warning',   'bi-hourglass-split'],
        ['Gugur (Usia >21)', $gugur_usia,'bg-secondary','bi-person-x-fill'],
    ];
    foreach ($cards as [$label, $val, $bg, $icon]):
    ?>
    <div class="col-6 col-md-4 col-lg">
        <div class="card text-white <?= $bg ?> h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi <?= $icon ?> fs-2 opacity-75"></i>
                <div>
                    <div class="fw-bold fs-4"><?= $val ?></div>
                    <small><?= $label ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Gelombang Status -->
<div class="row g-3 mb-4">
    <?php foreach ($gel_rows as $g): ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                Gelombang <?= $g['gelombang'] ?>
                <?php if ($g['is_published']): ?>
                    <span class="badge bg-success ms-2">Pengumuman Sudah Publish</span>
                <?php else: ?>
                    <span class="badge bg-secondary ms-2">Belum Dipublish</span>
                <?php endif; ?>
            </div>
            <div class="card-body small">
                <div class="row">
                    <div class="col-6">
                        <div class="text-muted">Pendaftaran</div>
                        <div><?= date('d M Y', strtotime($g['tanggal_buka'])) ?> – <?= date('d M Y', strtotime($g['tanggal_tutup'])) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Pengumuman</div>
                        <div><?= date('d M Y', strtotime($g['tanggal_pengumuman'])) ?></div>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-muted">Kuota per jurusan:</span>
                    <strong><?= $g['kuota_per_jurusan'] ?></strong>
                    <span class="text-muted ms-2">Porsi gelombang:</span>
                    <strong><?= $g['persen_gelombang'] ?>%</strong>
                    <?php
                    $kuota_glm = (int)round($g['kuota_per_jurusan'] * $g['persen_gelombang'] / 100);
                    ?>
                    <span class="text-muted ms-2">→ Ambil:</span>
                    <strong class="text-success"><?= $kuota_glm ?> per jurusan</strong>
                </div>
                <div class="mt-1">
                    <span class="text-muted">Pendaftar di gelombang ini:</span>
                    <strong><?= $glm[$g['gelombang']] ?? 0 ?></strong>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Chart per Jurusan -->
<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header fw-semibold">Jumlah Pendaftar per Jurusan</div>
            <div class="card-body"><canvas id="chartJurusan" height="140"></canvas></div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header fw-semibold">Status Penerimaan</div>
            <div class="card-body"><canvas id="chartStatus" height="200"></canvas></div>
        </div>
    </div>
</div>

<!-- Tabel Pendaftar Terbaru -->
<div class="card">
    <div class="card-header fw-semibold">8 Pendaftar Terbaru</div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead class="table-light">
                <tr><th>No. Daftar</th><th>Nama</th><th>Jurusan</th><th>Glm</th><th>Nilai Akhir</th><th>Status</th><th>Waktu</th></tr>
            </thead>
            <tbody>
            <?php if (empty($recent)): ?>
                <tr><td colspan="7" class="text-center py-3 text-muted">Belum ada data pendaftar.</td></tr>
            <?php else: foreach ($recent as $r): ?>
                <?php
                $badge = match($r['status']) { 'diterima'=>'bg-success', 'ditolak'=>'bg-danger', default=>'bg-warning text-dark' };
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                    <td><?= htmlspecialchars($r['nama']) ?></td>
                    <td><?= $short[$r['jurusan']] ?? $r['jurusan'] ?></td>
                    <td class="text-center"><?= $r['gelombang'] ?></td>
                    <td class="text-center fw-semibold"><?= number_format($r['nilai_akhir'], 2) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
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
            backgroundColor: ['#0d6efd','#198754','#dc3545','#fd7e14'],
            borderRadius: 6,
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Diterima', 'Ditolak', 'Pending'],
        datasets: [{ data: [<?= $diterima ?>, <?= $ditolak ?>, <?= $pending ?>], backgroundColor: ['#198754','#dc3545','#ffc107'], borderWidth: 0 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});
</script>
