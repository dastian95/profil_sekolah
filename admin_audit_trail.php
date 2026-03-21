<?php

/**
 * Admin Audit Trail Dashboard
 * View and analyze all system activity logs
 */

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/AuditLogger.php';
require_once __DIR__ . '/env_loader.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Create audit table if needed
createAuditTable($conn);

// Handle purge action
if (isset($_GET['action']) && $_GET['action'] === 'purge' && isset($_GET['days'])) {
    $days = intval($_GET['days']);
    if ($days > 0 && $days <= 365) {
        AuditLogger::init($conn, $_SESSION['user_id']);
        if (AuditLogger::purgeOldLogs($days)) {
            $purgeMessage = "✅ Purged logs older than $days days.";
        }
    }
}

// Setup AuditLogger
AuditLogger::init($conn, $_SESSION['user_id']);

// Get filter parameters
$filterAction = $_GET['action_filter'] ?? '';
$filterEntity = $_GET['entity_filter'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$filterDateTo = $_GET['date_to'] ?? date('Y-m-d');
$filterUserId = $_GET['user_id_filter'] ?? '';
$page = intval($_GET['p'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

// Get logs
$filters = [
    'action' => $filterAction,
    'entity' => $filterEntity,
    'date_from' => $filterDateFrom,
    'date_to' => $filterDateTo,
    'user_id' => $filterUserId
];

$logs = AuditLogger::getLog($filters, $limit, $offset);
$stats = AuditLogger::getStatistics($filterDateFrom, $filterDateTo);

// Get unique entities
$entitiesStmt = $conn->query("SELECT DISTINCT entity FROM audit_logs_enhanced WHERE entity IS NOT NULL ORDER BY entity");
$entities = $entitiesStmt->fetchAll(PDO::FETCH_COLUMN);

// Get total records
$countSql = "SELECT COUNT(*) FROM audit_logs_enhanced WHERE DATE(timestamp) BETWEEN ? AND ?";
$stmt = $conn->prepare($countSql);
$stmt->execute([$filterDateFrom, $filterDateTo]);
$totalRecords = $stmt->fetch(PDO::FETCH_ASSOC) ? $stmt->fetchColumn() : 0;
$totalPages = ceil($totalRecords / $limit);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Admin SMK Lab Jakarta</title>
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

        .card-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .filter-panel {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .log-entry {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 10px;
            background: white;
        }

        .action-badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 3px;
        }

        .action-create {
            background: #d4edda;
            color: #155724;
        }

        .action-update {
            background: #cfe2ff;
            color: #084298;
        }

        .action-delete {
            background: #f8d7da;
            color: #721c24;
        }

        .action-login {
            background: #d1ecf1;
            color: #0c5460;
        }

        .action-export {
            background: #fff3cd;
            color: #856404;
        }

        .stat-box {
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a href="admin_home.php" class="logo d-flex align-items-center">
                <h1>📋 Audit Trail</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="admin_home.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container-fluid">

            <!-- Message -->
            <?php if (!empty($purgeMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $purgeMessage; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ============== STATISTICS ============== -->
            <div class="card-section">
                <h2 class="section-title"><i class="bi bi-bar-chart"></i> Activity Statistics</h2>

                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $totalRecords; ?></div>
                            <div>Total Activities</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="stat-number"><?php echo count($entities); ?></div>
                            <div>Entity Types</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <div class="stat-number">
                                <?php
                                $stmt = $conn->query("SELECT COUNT(DISTINCT user_id) FROM audit_logs_enhanced WHERE DATE(timestamp) BETWEEN '$filterDateFrom' AND '$filterDateTo'");
                                echo $stmt->fetchColumn();
                                ?>
                            </div>
                            <div>Active Users</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <div class="stat-number"><?php echo count($stats); ?></div>
                            <div>Action Types</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============== ACTION BREAKDOWN ============== -->
            <div class="card-section">
                <h3 style="margin-bottom: 20px;"><i class="bi bi-pie-chart"></i> Action Breakdown</h3>

                <div class="row">
                    <?php foreach ($stats as $stat): ?>
                        <div class="col-md-4 mb-3">
                            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #0d6efd;">
                                <strong><?php echo htmlspecialchars($stat['action']); ?></strong>
                                <div style="font-size: 1.5rem; font-weight: bold; color: #0d6efd; margin-top: 5px;">
                                    <?php echo $stat['count']; ?>
                                </div>
                                <small class="text-muted"><?php echo $stat['unique_users']; ?> users</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ============== AUDIT LOGS ============== -->
            <div class="card-section">
                <h2 class="section-title"><i class="bi bi-list-check"></i> Audit Logs</h2>

                <!-- Filter Panel -->
                <div class="filter-panel">
                    <form class="row g-3" method="GET">
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select name="action_filter" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="CREATE" <?php echo $filterAction === 'CREATE' ? 'selected' : ''; ?>>Create</option>
                                <option value="UPDATE" <?php echo $filterAction === 'UPDATE' ? 'selected' : ''; ?>>Update</option>
                                <option value="DELETE" <?php echo $filterAction === 'DELETE' ? 'selected' : ''; ?>>Delete</option>
                                <option value="LOGIN" <?php echo $filterAction === 'LOGIN' ? 'selected' : ''; ?>>Login</option>
                                <option value="EXPORT" <?php echo $filterAction === 'EXPORT' ? 'selected' : ''; ?>>Export</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Entity</label>
                            <select name="entity_filter" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach ($entities as $entity): ?>
                                    <option value="<?php echo htmlspecialchars($entity); ?>" <?php echo $filterEntity === $entity ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($entity); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From</label>
                            <input type="date" name="date_from" value="<?php echo $filterDateFrom; ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To</label>
                            <input type="date" name="date_to" value="<?php echo $filterDateTo; ?>" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filter</button>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <a href="?" class="btn btn-secondary btn-sm w-100"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Logs Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>IP Address</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><small><?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?></small></td>
                                        <td>
                                            <?php
                                            if ($log['user_id']) {
                                                $stmt = $conn->prepare("SELECT name FROM users WHERE id_pendaftar = ?");
                                                $stmt->execute([$log['user_id']]);
                                                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                                                echo htmlspecialchars($user['name'] ?? 'Unknown');
                                            } else {
                                                echo '<em class="text-muted">System</em>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span class="action-badge action-<?php echo strtolower($log['action']); ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['entity']); ?></td>
                                        <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                                        <td>
                                            <?php
                                            $details = json_decode($log['details'], true);
                                            if (is_array($details)) {
                                                if (!empty($details['changes'])) {
                                                    echo '<small class="text-muted">' . count($details['changes']) . ' fields changed</small>';
                                                } else {
                                                    echo '<small class="text-muted">No details</small>';
                                                }
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted">No audit logs found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?p=<?php echo $page - 1; ?>&action_filter=<?php echo urlencode($filterAction); ?>&entity_filter=<?php echo urlencode($filterEntity); ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= min($totalPages, 5); $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?p=<?php echo $i; ?>&action_filter=<?php echo urlencode($filterAction); ?>&entity_filter=<?php echo urlencode($filterEntity); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?p=<?php echo $page + 1; ?>&action_filter=<?php echo urlencode($filterAction); ?>&entity_filter=<?php echo urlencode($filterEntity); ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <!-- ============== MAINTENANCE ============== -->
            <div class="card-section">
                <h3 style="margin-bottom: 15px;"><i class="bi bi-tools"></i> Maintenance</h3>

                <p class="text-muted">Purge old logs to manage database size</p>

                <div class="btn-group" role="group">
                    <a href="?action=purge&days=30" class="btn btn-outline-warning btn-sm" onclick="return confirm('Delete logs older than 30 days?')">
                        <i class="bi bi-trash"></i> Purge 30+ days
                    </a>
                    <a href="?action=purge&days=90" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete logs older than 90 days?')">
                        <i class="bi bi-trash"></i> Purge 90+ days
                    </a>
                </div>
            </div>

        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>