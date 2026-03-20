<?php
// admin_comparison.php

// Filter Tanggal
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$date_filter_sql = "";
$params = [];

if (!empty($start_date) && !empty($end_date)) {
    $date_filter_sql = " AND u.created_at BETWEEN ? AND ? ";
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
}

try {
    // 1. Data Perbandingan: Jurusan vs Jalur Daftar
    // Join dengan users untuk filter tanggal created_at
    $sql1 = "SELECT dp.jurusan, dp.jalur_daftar, COUNT(*) as total
             FROM data_peserta dp
             JOIN users u ON dp.id_pendaftar = u.id_pendaftar
             WHERE dp.jurusan IS NOT NULL AND dp.jurusan != '' $date_filter_sql
             GROUP BY dp.jurusan, dp.jalur_daftar";
    $stmt = $conn->prepare($sql1);
    $stmt->execute($params);
    $raw_jurusan_jalur = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Memproses data untuk Chart.js (Stacked Bar)
    $jurusan_list = array_unique(array_column($raw_jurusan_jalur, 'jurusan'));
    $jalur_list = array_unique(array_column($raw_jurusan_jalur, 'jalur_daftar'));
    
    // Jika jalur_daftar kosong (karena belum diisi), beri label default
    if (empty($jalur_list)) $jalur_list = ['Umum'];

    $chart_jurusan_jalur = [
        'labels' => array_values($jurusan_list),
        'datasets' => []
    ];

    $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
    $i = 0;

    foreach ($jalur_list as $jalur) {
        $label = empty($jalur) ? 'Umum/Lainnya' : $jalur;
        $data = [];
        foreach ($jurusan_list as $jurusan) {
            $count = 0;
            foreach ($raw_jurusan_jalur as $row) {
                $row_jalur = empty($row['jalur_daftar']) ? 'Umum' : $row['jalur_daftar'];
                // Match logic
                if ($row['jurusan'] == $jurusan && ($row['jalur_daftar'] == $jalur || (empty($jalur) && empty($row['jalur_daftar'])))) {
                    $count = $row['total'];
                    break;
                }
            }
            $data[] = $count;
        }
        $chart_jurusan_jalur['datasets'][] = [
            'label' => $label,
            'data' => $data,
            'backgroundColor' => $colors[$i % count($colors)]
        ];
        $i++;
    }

    // 2. Data Perbandingan: Top 10 Asal Sekolah
    $sql2 = "SELECT dp.asal_sekolah, COUNT(*) as total
             FROM data_peserta dp
             JOIN users u ON dp.id_pendaftar = u.id_pendaftar
             WHERE dp.asal_sekolah IS NOT NULL AND dp.asal_sekolah != '' $date_filter_sql
             GROUP BY dp.asal_sekolah
             ORDER BY total DESC
             LIMIT 10";
    $stmt = $conn->prepare($sql2);
    $stmt->execute($params);
    $top_schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Data Tren: Pendaftaran Harian (14 Hari Terakhir)
    // Jika filter tanggal aktif, gunakan rentang tersebut. Jika tidak, default 14 hari terakhir.
    if (!empty($start_date) && !empty($end_date)) {
        $sql3 = "SELECT DATE(created_at) as tgl, COUNT(*) as total FROM users u WHERE role = 'user' $date_filter_sql GROUP BY DATE(created_at) ORDER BY tgl ASC";
        $stmt = $conn->prepare($sql3);
        $stmt->execute($params);
    } else {
        $stmt = $conn->query("
            SELECT DATE(created_at) as tgl, COUNT(*) as total
            FROM users
            WHERE role = 'user' AND created_at >= DATE(NOW()) - INTERVAL 14 DAY
            GROUP BY DATE(created_at)
            ORDER BY tgl ASC
        ");
    }
    $daily_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $trend_labels = [];
    $trend_data = [];
    foreach ($daily_trend as $d) {
        $trend_labels[] = date('d M', strtotime($d['tgl']));
        $trend_data[] = $d['total'];
    }

    // 4. Data Perbandingan: Top 10 Asal Daerah (Kota)
    $sql4 = "SELECT dp.kota, COUNT(*) as total
             FROM data_peserta dp
             JOIN users u ON dp.id_pendaftar = u.id_pendaftar
             WHERE dp.kota IS NOT NULL AND dp.kota != '' $date_filter_sql
             GROUP BY dp.kota
             ORDER BY total DESC
             LIMIT 10";
    $stmt = $conn->prepare($sql4);
    $stmt->execute($params);
    $top_cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $city_labels = array_column($top_cities, 'kota');
    $city_data = array_column($top_cities, 'total');

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="card shadow-sm mb-4 border-0">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="comparison">
            <div class="col-auto"><label class="col-form-label fw-bold">Filter Tanggal:</label></div>
            <div class="col-auto"><input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($start_date); ?>"></div>
            <div class="col-auto"><span>s/d</span></div>
            <div class="col-auto"><input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($end_date); ?>"></div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Terapkan</button>
                <a href="?page=comparison" class="btn btn-secondary btn-sm">Reset</a>
            </div>
            <div class="col text-end">
                <a href="admin_export_comparison.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Export Excel</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <!-- Chart: Jurusan vs Jalur -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-bar-chart-steps me-2"></i>Perbandingan Jurusan & Jalur Masuk</h6>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="jurusanJalurChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Daily Trend -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-graph-up me-2"></i>Tren Pendaftaran (14 Hari)</h6>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Table: Top Schools -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-building me-2"></i>Top 10 Asal Sekolah Pendaftar</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" width="50">No</th>
                                <th>Nama Sekolah</th>
                                <th width="150" class="text-center">Jumlah Pendaftar</th>
                                <th style="width: 40%;">Persentase (Visual)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $max_school = !empty($top_schools) ? $top_schools[0]['total'] : 1;
                            $no = 1;
                            foreach ($top_schools as $school): 
                                $width = ($school['total'] / $max_school) * 100;
                            ?>
                            <tr>
                                <td class="ps-4"><?php echo $no++; ?></td>
                                <td class="fw-bold text-dark">
                                    <a href="#" class="text-decoration-none school-detail-link" data-school="<?php echo htmlspecialchars($school['asal_sekolah']); ?>">
                                        <?php echo htmlspecialchars($school['asal_sekolah']); ?>
                                    </a>
                                </td>
                                <td class="text-center fw-bold fs-5 text-primary"><?php echo $school['total']; ?></td>
                                <td class="pe-4">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $width; ?>%;" aria-valuenow="<?php echo $school['total']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $max_school; ?>">
                                            <?php echo $school['total']; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($top_schools)): ?>
                                <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada data sekolah.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart: Top Cities -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-primary"><i class="bi bi-geo-alt me-2"></i>Top 10 Asal Daerah (Kota)</h6>
            </div>
            <div class="card-body">
                <div style="height: 350px;">
                    <canvas id="cityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Siswa per Sekolah -->
<div class="modal fade" id="schoolDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Daftar Siswa: <span id="schoolNameTitle" class="fw-bold text-primary"></span></h5>
                <a href="#" id="btnDownloadSchoolPdf" target="_blank" class="btn btn-danger btn-sm ms-auto me-2"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light"><tr><th>Nama</th><th>NISN</th><th>Jurusan</th><th>Status</th></tr></thead>
                        <tbody id="schoolStudentList"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Jurusan vs Jalur Chart (Stacked Bar)
    const ctxJurusan = document.getElementById('jurusanJalurChart');
    if (ctxJurusan) {
        new Chart(ctxJurusan, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_jurusan_jalur['labels']); ?>,
                datasets: <?php echo json_encode($chart_jurusan_jalur['datasets']); ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true },
                    y: { 
                        stacked: true,
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Trend Chart (Line)
    const ctxTrend = document.getElementById('trendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Pendaftar Baru',
                    data: <?php echo json_encode($trend_data); ?>,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // City Chart (Horizontal Bar)
    const ctxCity = document.getElementById('cityChart');
    if (ctxCity) {
        new Chart(ctxCity, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($city_labels); ?>,
                datasets: [{
                    label: 'Jumlah Pendaftar',
                    data: <?php echo json_encode($city_data); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // Handle School Detail Click
    const schoolModal = new bootstrap.Modal(document.getElementById('schoolDetailModal'));
    document.querySelectorAll('.school-detail-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const schoolName = this.getAttribute('data-school');
            document.getElementById('schoolNameTitle').textContent = schoolName;
            
            // Update PDF Download Link
            document.getElementById('btnDownloadSchoolPdf').href = 'admin_print_school_detail.php?school=' + encodeURIComponent(schoolName);

            const tbody = document.getElementById('schoolStudentList');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">Loading...</td></tr>';
            
            schoolModal.show();

            const formData = new FormData();
            formData.append('school_name', schoolName);

            fetch('admin_fetch_school_data.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';
                if (data.success && data.data.length > 0) {
                    data.data.forEach(s => {
                        tbody.innerHTML += `<tr><td>${s.nama}</td><td>${s.nisn}</td><td>${s.jurusan}</td><td><span class="badge bg-secondary">${s.status_pendaftaran || 'Draft'}</span></td></tr>`;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Tidak ada data siswa.</td></tr>';
                }
            })
            .catch(err => {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Gagal memuat data.</td></tr>';
                console.error(err);
            });
        });
    });
});
</script>