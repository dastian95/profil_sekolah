<?php

/**
 * Admin Cache Management Dashboard
 * Monitor and manage query cache performance
 */

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/QueryCache.php';
require_once __DIR__ . '/env_loader.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? '';
$message = '';

// Handle cache actions
if ($action === 'clear_all') {
    QueryCache::clearAll();
    $message = ['type' => 'success', 'text' => '✅ Cache berhasil dihapus semua!'];
}

if ($action === 'clear_pattern' && isset($_POST['pattern'])) {
    QueryCache::invalidatePattern($_POST['pattern']);
    $message = ['type' => 'success', 'text' => '✅ Cache pattern berhasil dihapus!'];
}

// Get cache statistics
$stats = QueryCache::getStats();
$cacheSize = QueryCache::getCacheSize();

// Database stats
$dbStats = [];
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
    $dbStats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM data_peserta");
    $dbStats['total_data'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM pendaftar");
    $dbStats['total_apps'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM unggah_dokumen");
    $dbStats['total_docs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    $dbStats = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Management - Admin SMK Lab Jakarta</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .main {
            padding-top: 120px;
            padding-bottom: 40px;
        }

        .card-stat {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }

        .card-stat .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
        }

        .card-stat .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .cache-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            margin-bottom: 20px;
        }

        .action-button {
            margin: 5px;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="admin_home.php" class="logo d-flex align-items-center">
                <h1>⚡ Cache Manager</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="admin_home.php">Dashboard</a></li>
                    <li><a href="admin_manage_users.php">Manage Users</a></li>
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

            <!-- Message Alert -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ============== CACHE STATISTICS ============== -->
            <div style="margin-bottom: 30px;">
                <h2 style="margin-bottom: 20px; font-weight: 600;"><i class="bi bi-bar-chart"></i> Cache Performance</h2>

                <div class="stat-grid">
                    <div class="card-stat">
                        <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    <div class="card-stat">
                        <div class="stat-number" style="color: #28a745;"><?php echo $stats['hits']; ?></div>
                        <div class="stat-label">Cache Hits ✓</div>
                    </div>
                    <div class="card-stat">
                        <div class="stat-number" style="color: #dc3545;"><?php echo $stats['misses']; ?></div>
                        <div class="stat-label">Cache Misses ✗</div>
                    </div>
                    <div class="card-stat">
                        <div class="stat-number" style="color: #0d6efd;"><?php echo $stats['hit_rate']; ?></div>
                        <div class="stat-label">Hit Rate</div>
                    </div>
                </div>
            </div>

            <!-- ============== CACHE INFO ============== -->
            <div class="cache-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Cache Status:</strong>
                Cache size: <strong><?php echo $cacheSize; ?> MB</strong> |
                Invalidations: <strong><?php echo $stats['invalidations']; ?></strong> |
                Entries: <strong><?php echo count(glob(__DIR__ . '/cache/queries/*/*.json')); ?></strong>
            </div>

            <!-- ============== DATABASE STATS ============== -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px; font-weight: 600;"><i class="bi bi-database"></i> Database Statistics</h3>

                <div class="stat-grid">
                    <div class="card-stat">
                        <div class="stat-number"><?php echo $dbStats['total_users'] ?? 0; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="card-stat">
                        <div class="stat-number"><?php echo $dbStats['total_data'] ?? 0; ?></div>
                        <div class="stat-label">Student Data</div>
                    </div>
                    <div class="card-stat">
                        <div class="stat-number"><?php echo $dbStats['total_apps'] ?? 0; ?></div>
                        <div class="stat-label">Applications</div>
                    </div>
                    <div class="card-stat">
                        <div class="stat-number"><?php echo $dbStats['total_docs'] ?? 0; ?></div>
                        <div class="stat-label">Documents Uploaded</div>
                    </div>
                </div>
            </div>

            <!-- ============== CACHE CONTROLS ============== -->
            <div class="chart-container">
                <h3 style="margin-bottom: 20px; font-weight: 600;"><i class="bi bi-sliders"></i> Cache Management</h3>

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">Query Cache Controls</h5>
                        <p class="text-muted small">Use these controls to manage and clear the query result cache</p>

                        <div class="d-grid gap-2">
                            <a href="?action=clear_all" class="btn btn-danger action-button" onclick="return confirm('Hapus semua cache? Ini akan membuat query menjadi lebih lambat sementara!');">
                                <i class="bi bi-trash"></i> Clear All Cache
                            </a>

                            <form method="POST" class="mt-2">
                                <div class="input-group">
                                    <input type="text" name="pattern" class="form-control" placeholder="e.g., users_*, stats_*" required>
                                    <button class="btn btn-warning" type="submit" name="action" value="clear_pattern">
                                        <i class="bi bi-funnel"></i> Clear by Pattern
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-3">Cache Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Cache System:</strong></td>
                                <td><?php echo function_exists('apcu_fetch') ? '✅ APCu + File' : '📁 File-based'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Cache Directory:</strong></td>
                                <td><code>/cache/queries/</code></td>
                            </tr>
                            <tr>
                                <td><strong>Default TTL:</strong></td>
                                <td>1 hour (3600s)</td>
                            </tr>
                            <tr>
                                <td><strong>Last Action:</strong></td>
                                <td>
                                    <?php
                                    $statsFile = __DIR__ . '/cache/cache_stats.json';
                                    echo file_exists($statsFile) ?
                                        date('Y-m-d H:i:s', filemtime($statsFile)) : 'N/A';
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============== CACHE RECOMMENDATIONS ============== -->
            <div class="chart-container" style="background-color: #f0f7ff; border-left: 4px solid #0d6efd;">
                <h4 style="margin-bottom: 15px;"><i class="bi bi-lightbulb"></i> Cache Optimization Tips</h4>
                <ul class="list-unstyled">
                    <li>✓ Cache hit rate above 70% is excellent</li>
                    <li>✓ Admin queries have higher cache hit rates</li>
                    <li>✓ Clear cache after bulk data imports</li>
                    <li>✓ Monitor cache size regularly</li>
                    <li>✓ Consider enabling APCu for even better performance</li>
                </ul>
            </div>

            <!-- ============== CACHE PERFORMANCE CHART ============== -->
            <div class="chart-container" style="margin-top: 30px;">
                <h4 style="margin-bottom: 20px;"><i class="bi bi-graph-up"></i> Hit vs Miss Ratio</h4>
                <div style="display: flex; align-items: center; justify-content: center; gap: 40px;">
                    <div style="text-align: center;">
                        <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(to right, #28a745 0%, #28a745 <?php echo $stats['hits'] > 0 ? ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100 : 0; ?>%, #dc3545 <?php echo $stats['hits'] > 0 ? ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100 : 0; ?>%, #dc3545 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <span style="font-size: 2rem; font-weight: bold; color: white;"><?php echo $stats['hit_rate']; ?></span>
                        </div>
                        <p style="margin-top: 10px; font-weight: 600;">Cache Efficiency</p>
                    </div>

                    <div>
                        <p><span style="display: inline-block; width: 15px; height: 15px; background: #28a745; border-radius: 3px; margin-right: 10px;"></span> Hits: <?php echo $stats['hits']; ?></p>
                        <p><span style="display: inline-block; width: 15px; height: 15px; background: #dc3545; border-radius: 3px; margin-right: 10px;"></span> Misses: <?php echo $stats['misses']; ?></p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>