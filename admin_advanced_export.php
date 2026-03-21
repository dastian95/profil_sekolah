<?php
/**
 * Admin Advanced Export Features
 * Export users, applications, and statistics in multiple formats
 */

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/env_loader.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// ============================================================
// HANDLE EXPORTS
// ============================================================

// Export Users to CSV
if ($_GET['action'] === 'export_users_csv') {
    $filter_status = $_GET['filter_status'] ?? '';
    $filter_jurusan = $_GET['filter_jurusan'] ?? '';
    
    $query = "
        SELECT 
            u.id_pendaftar,
            u.nisn,
            u.name,
            u.email,
            u.is_verified,
            u.is_banned,
            u.created_at,
            dp.jenis_jurusan,
            dp.kota,
            dp.asal_sekolah
        FROM users u
        LEFT JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
        WHERE u.role = 'user'
    ";
    
    $conditions = [];
    if ($filter_status !== '') {
        $conditions[] = "u.is_verified = " . intval($filter_status);
    }
    if ($filter_jurusan !== '') {
        $conditions[] = "dp.jenis_jurusan = '" . $conn->quote($filter_jurusan) . "'";
    }
    
    if (!empty($conditions)) {
        $query .= " AND " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY u.created_at DESC";
    
    $stmt = $conn->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'users_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, [
        'ID', 'NISN', 'Nama', 'Email', 'Status Verifikasi', 'Status Ban', 'Tanggal Daftar', 'Jurusan', 'Kota', 'Asal Sekolah'
    ]);
    
    // Data
    foreach ($users as $user) {
        fputcsv($output, [
            $user['id_pendaftar'],
            $user['nisn'] ?? '',
            $user['name'],
            $user['email'],
            $user['is_verified'] ? 'Verified' : 'Unverified',
            $user['is_banned'] ? 'Banned' : 'Active',
            $user['created_at'],
            $user['jenis_jurusan'] ?? '',
            $user['kota'] ?? '',
            $user['asal_sekolah'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

// Export Applications to CSV
if ($_GET['action'] === 'export_applications_csv') {
    $filter_status = $_GET['filter_status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    $query = "
        SELECT 
            p.id_pendaftar,
            u.nisn,
            u.name,
            u.email,
            dp.jenis_jurusan,
            p.status_pendaftaran,
            hd.hasil_daftar,
            p.created_at,
            p.updated_at
        FROM pendaftar p
        LEFT JOIN users u ON p.id_pendaftar = u.id_pendaftar
        LEFT JOIN data_peserta dp ON p.id_pendaftar = dp.id_pendaftar
        LEFT JOIN hasil_daftar hd ON p.id_pendaftar = hd.id_pendaftar
        WHERE 1=1
    ";
    
    if (!empty($filter_status)) {
        $query .= " AND p.status_pendaftaran = " . $conn->quote($filter_status);
    }
    
    if (!empty($date_from)) {
        $query .= " AND DATE(p.created_at) >= " . $conn->quote($date_from);
    }
    
    if (!empty($date_to)) {
        $query .= " AND DATE(p.created_at) <= " . $conn->quote($date_to);
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $conn->query($query);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'applications_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $output = fopen('php://output', 'w');
    
    // Headers
    fputcsv($output, [
        'ID Pendaftar', 'NISN', 'Nama', 'Email', 'Jurusan', 'Status Dokumen', 'Hasil', 'Tanggal Daftar'
    ]);
    
    // Data
    foreach ($applications as $app) {
        fputcsv($output, [
            $app['id_pendaftar'],
            $app['nisn'] ?? '',
            $app['name'],
            $app['email'],
            $app['jenis_jurusan'] ?? '',
            $app['status_pendaftaran'] ?? '',
            $app['hasil_daftar'] ?? '',
            $app['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}

// Export Statistics Summary
if ($_GET['action'] === 'export_statistics_csv') {
    $stats = [];
    
    // Total and Verified Users
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND is_verified = 1");
    $verified_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // By Jurusan
    $stmt = $conn->query("SELECT jenis_jurusan, COUNT(*) as total FROM data_peserta WHERE jenis_jurusan IS NOT NULL GROUP BY jenis_jurusan");
    $by_jurusan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // By City
    $stmt = $conn->query("SELECT kota, COUNT(*) as total FROM data_peserta WHERE kota IS NOT NULL GROUP BY kota ORDER BY total DESC LIMIT 20");
    $by_city = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // By Status
    $stmt = $conn->query("SELECT hasil_daftar, COUNT(*) as total FROM hasil_daftar GROUP BY hasil_daftar");
    $by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'statistics_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $output = fopen('php://output', 'w');
    
    // Summary
    fputcsv($output, ['RINGKASAN STATISTIK', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['Total Users', $total_users]);
    fputcsv($output, ['Verified Users', $verified_users]);
    fputcsv($output, ['Unverified Users', $total_users - $verified_users]);
    fputcsv($output, []);
    
    // By Jurusan
    fputcsv($output, ['STATISTIK BERDASARKAN JURUSAN']);
    fputcsv($output, ['Jurusan', 'Jumlah']);
    foreach ($by_jurusan as $item) {
        fputcsv($output, [$item['jenis_jurusan'], $item['total']]);
    }
    fputcsv($output, []);
    
    // By City
    fputcsv($output, ['STATISTIK BERDASARKAN KOTA (TOP 20)']);
    fputcsv($output, ['Kota', 'Jumlah']);
    foreach ($by_city as $item) {
        fputcsv($output, [$item['kota'], $item['total']]);
    }
    fputcsv($output, []);
    
    // By Status
    fputcsv($output, ['STATISTIK BERDASARKAN STATUS HASIL']);
    fputcsv($output, ['Status', 'Jumlah']);
    foreach ($by_status as $item) {
        fputcsv($output, [$item['hasil_daftar'], $item['total']]);
    }
    
    fclose($output);
    exit;
}

// Fetch filter options
$jurusans = [];
$stmt = $conn->query("SELECT DISTINCT jenis_jurusan FROM data_peserta WHERE jenis_jurusan IS NOT NULL ORDER BY jenis_jurusan");
$jurusans = $stmt->fetchAll(PDO::FETCH_COLUMN);

$statuses = [];
$stmt = $conn->query("SELECT DISTINCT status_pendaftaran FROM pendaftar WHERE status_pendaftaran IS NOT NULL ORDER BY status_pendaftaran");
$statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Export - Admin SMK Lab Jakarta</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .main { padding-top: 120px; padding-bottom: 40px; }
        .export-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .export-card h3 { color: #333; margin-bottom: 20px; font-weight: 600; }
        .format-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .format-btn { 
            padding: 15px 25px; 
            border-radius: 8px; 
            color: white; 
            text-decoration: none; 
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .format-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .format-csv { background-color: #17a2b8; }
        .format-excel { background-color: #28a745; }
        .format-pdf { background-color: #dc3545; }
        .filter-section { background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0d6efd; }
        .section-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 25px; }
        @media (max-width: 768px) {
            .export-card { padding: 15px; }
            .format-btn { padding: 10px 15px; font-size: 0.9rem; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="admin_home.php" class="logo d-flex align-items-center">
                <h1>📊 Advanced Export</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="admin_home.php">Dashboard</a></li>
                    <li><a href="admin_manage_users.php">Manage Users</a></li>
                    <li><a href="admin_bulk_operations.php">Bulk Operations</a></li>
                    <li><a href="admin_advanced_export.php">Advanced Export</a></li>
                    <li><a href="admin_cache_manager.php">Cache Manager</a></li>
                    <li><a href="admin_query_optimization.php">Optimization</a></li>
                    <li><a href="admin_audit_trail.php">Audit Trail</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            
            <!-- ============== EXPORT USERS ============== -->
            <div class="export-card">
                <h3><i class="bi bi-people"></i> Export Users Data</h3>
                
                <div class="filter-section">
                    <form class="row g-3" id="usersFilterForm">
                        <div class="col-md-4">
                            <label class="form-label">Status Verifikasi</label>
                            <select name="filter_status" class="form-select">
                                <option value="">Semua</option>
                                <option value="1">Verified</option>
                                <option value="0">Unverified</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jurusan</label>
                            <select name="filter_jurusan" class="form-select">
                                <option value="">Semua Jurusan</option>
                                <?php foreach ($jurusans as $jurusan): ?>
                                    <option value="<?php echo htmlspecialchars($jurusan); ?>">
                                        <?php echo htmlspecialchars($jurusan); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Reset Filter
                            </button>
                        </div>
                    </form>
                </div>

                <div class="format-buttons">
                    <button type="button" class="format-btn format-csv" onclick="exportUsers('csv')">
                        <i class="bi bi-filetype-csv me-2"></i> Export CSV
                    </button>
                    <button type="button" class="format-btn format-excel" onclick="exportUsers('excel')">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i> Export Excel
                    </button>
                </div>
            </div>

            <!-- ============== EXPORT APPLICATIONS ============== -->
            <div class="export-card">
                <h3><i class="bi bi-file-earmark-text"></i> Export Applications Data</h3>
                
                <div class="filter-section">
                    <form class="row g-3" id="appsFilterForm">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="filter_status" class="form-select">
                                <option value="">Semua Status</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hingga Tanggal</label>
                            <input type="date" name="date_to" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <div class="format-buttons">
                    <button type="button" class="format-btn format-csv" onclick="exportApplications('csv')">
                        <i class="bi bi-filetype-csv me-2"></i> Export CSV
                    </button>
                    <button type="button" class="format-btn format-excel" onclick="exportApplications('excel')">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i> Export Excel
                    </button>
                </div>
            </div>

            <!-- ============== EXPORT STATISTICS ============== -->
            <div class="export-card">
                <h3><i class="bi bi-bar-chart"></i> Export Statistics & Summary Reports</h3>
                
                <p class="text-muted mb-3">Export comprehensive statistics including user demographics, registration trends, and selection results</p>

                <div class="format-buttons">
                    <button type="button" class="format-btn format-csv" onclick="exportStatistics()">
                        <i class="bi bi-filetype-csv me-2"></i> Export Statistics CSV
                    </button>
                </div>
            </div>

            <!-- ============== BATCH EXPORT ============== -->
            <div class="export-card">
                <h3><i class="bi bi-box-arrow-down"></i> Batch Export All Data</h3>
                
                <p class="text-muted mb-3">Download complete system data with all users, applications, and statistics in one batch</p>

                <button type="button" class="btn btn-lg btn-primary" onclick="batchExport()">
                    <i class="bi bi-download me-2"></i> Download All Data (Batch Export)
                </button>
            </div>

        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportUsers(format) {
            const form = document.getElementById('usersFilterForm');
            const params = new FormData(form);
            const queryString = new URLSearchParams(params).toString();
            window.location.href = '?action=export_users_csv&' + queryString;
        }

        function exportApplications(format) {
            const form = document.getElementById('appsFilterForm');
            const params = new FormData(form);
            const queryString = new URLSearchParams(params).toString();
            window.location.href = '?action=export_applications_csv&' + queryString;
        }

        function exportStatistics() {
            window.location.href = '?action=export_statistics_csv';
        }

        function batchExport() {
            if (confirm('Download semua data sistem? File akan berisi users, applications, dan statistics.')) {
                // Create a zip or multiple downloads
                const exports = [
                    '?action=export_users_csv',
                    '?action=export_applications_csv',
                    '?action=export_statistics_csv'
                ];
                
                let count = 0;
                exports.forEach((url, index) => {
                    setTimeout(() => {
                        const link = document.createElement('a');
                        link.href = url;
                        link.click();
                    }, index * 500); // Stagger downloads
                });
                
                alert('✅ Dimulai batch export. Periksa folder downloads Anda.');
            }
        }

        // Reset button handlers
        document.querySelectorAll('.btn-outline-secondary').forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                form.querySelectorAll('input, select').forEach(field => {
                    if (field.type !== 'button') {
                        field.value = '';
                    }
                });
            });
        });
    </script>
</body>
</html>
