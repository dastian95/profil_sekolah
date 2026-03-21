<?php
/**
 * Database Query Optimization
 * Provides SQL optimizations and creates necessary indexes for performance
 */

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/env_loader.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$current_indexes = [];
$recommended_indexes = [];
$slow_queries = [];

// ============================================================
// OPTIMIZATION ACTIONS
// ============================================================

if (isset($_GET['action']) && $_GET['action'] === 'apply_indexes') {
    try {
        // Indexes to create for performance optimization
        $indexes = [
            'ALTER TABLE users ADD INDEX idx_email (email)',
            'ALTER TABLE users ADD INDEX idx_nisn (nisn)',
            'ALTER TABLE users ADD INDEX idx_role (role)',
            'ALTER TABLE users ADD INDEX idx_is_verified (is_verified)',
            'ALTER TABLE data_peserta ADD INDEX idx_jenis_jurusan (jenis_jurusan)',
            'ALTER TABLE data_peserta ADD INDEX idx_kota (kota)',
            'ALTER TABLE data_peserta ADD INDEX idx_id_pendaftar (id_pendaftar)',
            'ALTER TABLE pendaftar ADD INDEX idx_id_pendaftar (id_pendaftar)',
            'ALTER TABLE pendaftar ADD INDEX idx_status_pendaftaran (status_pendaftaran)',
            'ALTER TABLE hasil_daftar ADD INDEX idx_id_pendaftar (id_pendaftar)',
            'ALTER TABLE hasil_daftar ADD INDEX idx_hasil_daftar (hasil_daftar)',
            'ALTER TABLE unggah_dokumen ADD INDEX idx_id_pendaftar (id_pendaftar)',
            'ALTER TABLE unggah_dokumen ADD INDEX idx_jenis_dokumen (jenis_dokumen)',
        ];
        
        $added = 0;
        $skipped = 0;
        
        foreach ($indexes as $index) {
            try {
                $conn->exec($index);
                $added++;
            } catch (Exception $e) {
                // Index might already exist
                $skipped++;
            }
        }
        
        $message = ['type' => 'success', 'text' => "✅ Optimisasi database selesai! Added: $added indexes, Skipped: $skipped (already exist)"];
    } catch (Exception $e) {
        $message = ['type' => 'danger', 'text' => '❌ Error: ' . $e->getMessage()];
    }
}

// Get current indexes
try {
    $indexQuery = "
        SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = ?
        ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
    ";
    
    $stmt = $conn->prepare($indexQuery);
    $stmt->execute([DB_NAME]);
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by table
    $current_indexes = [];
    foreach ($indexes as $idx) {
        $table = $idx['TABLE_NAME'];
        if (!isset($current_indexes[$table])) {
            $current_indexes[$table] = [];
        }
        if (!isset($current_indexes[$table][$idx['INDEX_NAME']])) {
            $current_indexes[$table][$idx['INDEX_NAME']] = [];
        }
        $current_indexes[$table][$idx['INDEX_NAME']][] = $idx['COLUMN_NAME'];
    }
} catch (Exception $e) {
    // Handle error
}

// Analyze table sizes
$table_stats = [];
try {
    $statsQuery = "
        SELECT 
            TABLE_NAME,
            ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
            TABLE_ROWS as row_count,
            ROUND((DATA_LENGTH / 1024 / 1024), 2) as data_mb,
            ROUND((INDEX_LENGTH / 1024 / 1024), 2) as index_mb
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = ?
        ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
    ";
    
    $stmt = $conn->prepare($statsQuery);
    $stmt->execute([DB_NAME]);
    $table_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query Optimization - Admin SMK Lab Jakarta</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .main { padding-top: 120px; padding-bottom: 40px; }
        .card-section { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .section-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 20px; }
        .optimization-status { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .status-good { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .status-warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .status-danger { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .table-responsive { overflow-x: auto; }
        .index-item { padding: 10px 15px; background: #f9f9f9; border-radius: 5px; margin: 5px 0; }
        .recommendation { background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; border-radius: 5px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="admin_home.php" class="logo d-flex align-items-center">
                <h1>⚙️ Query Optimization</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="admin_home.php">Dashboard</a></li>
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
            
            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show">
                    <?php echo $message['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ============== OPTIMIZATION STATUS ============== -->
            <div class="card-section">
                <h2 class="section-title"><i class="bi bi-speedometer2"></i> Database Optimization Status</h2>
                
                <div class="optimization-status status-good">
                    <i class="bi bi-check-circle-fill"></i> <strong>Database Status:</strong> Ready for optimization
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold;"><?php echo count($table_stats); ?></div>
                            <div>Total Tables</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold;">
                                <?php 
                                $total_size = array_sum(array_map(fn($t) => $t['size_mb'], $table_stats));
                                echo round($total_size, 1) . ' MB';
                                ?>
                            </div>
                            <div>Total Database Size</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold;"><?php echo count($current_indexes); ?></div>
                            <div>Indexed Tables</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; padding: 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold;">
                                <?php 
                                $index_size = array_sum(array_map(fn($t) => $t['index_mb'] ?? 0, $table_stats));
                                echo round($index_size, 1) . ' MB';
                                ?>
                            </div>
                            <div>Total Index Size</div>
                        </div>
                    </div>
                </div>

                <a href="?action=apply_indexes" class="btn btn-lg btn-primary" onclick="return confirm('Apply database optimizations? This may take a moment...')">
                    <i class="bi bi-lightning-charge"></i> Apply Optimization
                </a>
            </div>

            <!-- ============== TABLE STATISTICS ============== -->
            <div class="card-section">
                <h2 class="section-title"><i class="bi bi-table"></i> Table Statistics</h2>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Table</th>
                                <th>Rows</th>
                                <th>Size (MB)</th>
                                <th>Data (MB)</th>
                                <th>Index (MB)</th>
                                <th>Optimization</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_stats as $stat): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($stat['TABLE_NAME']); ?></strong></td>
                                    <td><?php echo number_format($stat['row_count']); ?></td>
                                    <td><?php echo $stat['size_mb']; ?></td>
                                    <td><?php echo $stat['data_mb']; ?></td>
                                    <td><?php echo $stat['index_mb']; ?></td>
                                    <td>
                                        <?php 
                                        $ratio = $stat['index_mb'] / max($stat['data_mb'], 0.01);
                                        if ($ratio >= 0.3) {
                                            echo '<span class="badge bg-success">Good</span>';
                                        } else if ($ratio >= 0.1) {
                                            echo '<span class="badge bg-warning">Fair</span>';
                                        } else {
                                            echo '<span class="badge bg-danger">Add Indexes</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ============== CURRENT INDEXES ============== -->
            <div class="card-section">
                <h2 class="section-title"><i class="bi bi-diagram-3"></i> Current Indexes</h2>
                
                <?php if (empty($current_indexes)): ?>
                    <div class="alert alert-warning">No indexes found. Run optimization to create recommended indexes.</div>
                <?php else: ?>
                    <?php foreach ($current_indexes as $table => $indexes): ?>
                        <div style="margin-bottom: 25px;">
                            <h5 style="margin-bottom: 15px; color: #0d6efd;"><i class="bi bi-table"></i> <?php echo htmlspecialchars($table); ?></h5>
                            <div>
                                <?php foreach ($indexes as $indexName => $columns): ?>
                                    <div class="index-item">
                                        <strong><?php echo htmlspecialchars($indexName); ?></strong>
                                        <br>
                                        <small class="text-muted">Columns: <?php echo implode(', ', array_map('htmlspecialchars', $columns)); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ============== OPTIMIZATION RECOMMENDATIONS ============== -->
            <div class="card-section">
                <h2 class="section-title"><i class="bi bi-lightbulb"></i> Optimization Recommendations</h2>
                
                <div class="recommendation">
                    <strong>✅ Query Caching Enabled</strong><br>
                    Analytics queries are cached for 1 hour to reduce database load. Cache is automatically invalidated on data modifications.
                </div>

                <div class="recommendation">
                    <strong>🔧 Database Indexes</strong><br>
                    The application uses optimized indexes on frequently queried columns (email, nisn, status, jurusan, etc.). This significantly improves query performance.
                </div>

                <div class="recommendation">
                    <strong>📊 Analytics Optimization</strong><br>
                    Complex analytics queries use pre-calculated aggregations and caching to provide instant dashboard loads.
                </div>

                <div class="recommendation">
                    <strong>🎯 Key Column Indexes</strong><br>
                    - <code>users.email</code> - Used in login and user lookups<br>
                    - <code>users.nisn</code> - Used in registration and duplicate checking<br>
                    - <code>data_peserta.jenis_jurusan</code> - Used in filtering and analytics<br>
                    - <code>data_peserta.kota</code> - Used in statistics and city-based queries<br>
                    - <code>pendaftar.status_pendaftaran</code> - Used in status filtering<br>
                    - <code>hasil_daftar.hasil_daftar</code> - Used in results filtering
                </div>
            </div>

            <!-- ============== QUERY PERFORMANCE TIPS ============== -->
            <div class="card-section" style="background: linear-gradient(to right, #e0f2f7 0%, #f0f7f7 100%); border-left: 4px solid #0d6efd;">
                <h3 style="margin-bottom: 15px;"><i class="bi bi-info-circle"></i> Performance Best Practices</h3>
                
                <ul class="list-unstyled">
                    <li>✓ Use exact column matches in WHERE clauses</li>
                    <li>✓ Limit query results with LIMIT clause</li>
                    <li>✓ Use JOIN instead of multiple queries</li>
                    <li>✓ Avoid SELECT * - specify needed columns</li>
                    <li>✓ Use database caching for expensive queries</li>
                    <li>✓ Monitor slow query logs regularly</li>
                    <li>✓ Add composite indexes for multi-column filters</li>
                    <li>✓ Consider pagination for large result sets</li>
                </ul>
            </div>

        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
