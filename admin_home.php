<?php
// Fetch stats
try {
    // Total Users
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stmt->execute();
    $total_users = $stmt->fetchColumn();

    // Verified Users
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND is_verified = 1");
    $stmt->execute();
    $verified_users = $stmt->fetchColumn();

    // Total Applications (Pendaftar)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pendaftar");
    $stmt->execute();
    $total_applications = $stmt->fetchColumn();

    // Applications Submitted
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE status_pendaftaran = 'Terkirim'");
    $stmt->execute();
    $submitted_applications = $stmt->fetchColumn();

    // Recent Registrations (Latest 5 Users)
    $stmt = $conn->prepare("SELECT name, email, created_at, is_verified FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // City Stats for Chart
    $stmt = $conn->prepare("SELECT kota, COUNT(*) as total FROM data_peserta WHERE kota IS NOT NULL AND kota != '' GROUP BY kota ORDER BY total DESC LIMIT 10");
    $stmt->execute();
    $city_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $chart_labels = [];
    $chart_data = [];
    foreach ($city_stats as $stat) {
        $chart_labels[] = $stat['kota'];
        $chart_data[] = $stat['total'];
    }

    // Jurusan Stats for Pie Chart
    $stmt = $conn->prepare("SELECT jurusan, COUNT(*) as total FROM data_peserta WHERE jurusan IS NOT NULL AND jurusan != '' GROUP BY jurusan");
    $stmt->execute();
    $jurusan_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pie_labels = [];
    $pie_data = [];
    foreach ($jurusan_stats as $stat) {
        $pie_labels[] = $stat['jurusan'];
        $pie_data[] = $stat['total'];
    }

} catch (PDOException $e) {
    $error_stats = "Database error: " . $e->getMessage();
}
// Content cleared
?>

<div class="row g-4 mb-4">
    <div class="col-12 text-end">
        <a href="admin_print_stats.php" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download Laporan Statistik
        </a>
    </div>

    <?php if (isset($error_stats)): ?>
        <div class="col-12">
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_stats); ?></div>
        </div>
    <?php endif; ?>

    <!-- Total Users Card -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-subtitle text-muted">Total Users</h6>
                    <div class="bg-primary bg-opacity-10 p-2 rounded">
                        <i class="bi bi-people text-primary"></i>
                    </div>
                </div>
                <h3 class="card-title mb-0"><?php echo $total_users; ?></h3>
            </div>
        </div>
    </div>

    <!-- Verified Users Card -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-subtitle text-muted">Verified Users</h6>
                    <div class="bg-success bg-opacity-10 p-2 rounded">
                        <i class="bi bi-person-check text-success"></i>
                    </div>
                </div>
                <h3 class="card-title mb-0"><?php echo $verified_users; ?></h3>
            </div>
        </div>
    </div>

    <!-- Applications Card -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-subtitle text-muted">Applications</h6>
                    <div class="bg-info bg-opacity-10 p-2 rounded">
                        <i class="bi bi-file-earmark-text text-info"></i>
                    </div>
                </div>
                <h3 class="card-title mb-0"><?php echo $total_applications; ?></h3>
            </div>
        </div>
    </div>

    <!-- Submitted Card -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-subtitle text-muted">Submitted</h6>
                    <div class="bg-warning bg-opacity-10 p-2 rounded">
                        <i class="bi bi-send text-warning"></i>
                    </div>
                </div>
                <h3 class="card-title mb-0"><?php echo $submitted_applications; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Statistik Pendaftar per Kota (Top 10)</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="cityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-pie-chart-fill me-2 text-success"></i>Peminatan Jurusan</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="jurusanChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-clock-history me-2"></i>Recent Registrations</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Name</th>
                                <th>Email</th>
                                <th>Date Registered</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_users) > 0): ?>
                                <?php foreach ($recent_users as $u): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold"><?php echo htmlspecialchars($u['name']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($u['created_at'])); ?></td>
                                        <td>
                                            <?php if ($u['is_verified']): ?>
                                                <span class="badge bg-success rounded-pill">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark rounded-pill">Unverified</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No recent registrations found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
{
    const ctx = document.getElementById('cityChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Jumlah Pendaftar',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    const ctxPie = document.getElementById('jurusanChart');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($pie_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($pie_data); ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}
</script>