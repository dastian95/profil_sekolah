<?php
// admin_logs.php - Display Admin Activity Logs

// Pagination Setup
$limit = 15;
$page_num = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page_num < 1) $page_num = 1;
$offset = ($page_num - 1) * $limit;

try {
    // Check if table exists first to avoid fatal error
    $check = $conn->query("SHOW TABLES LIKE 'admin_logs'");
    if ($check->rowCount() == 0) {
        // Create table if not exists
        $conn->exec("CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // Count Total Logs
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_logs");
    $stmt->execute();
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Fetch Logs
    $stmt = $conn->prepare("
        SELECT l.*, u.name as admin_name 
        FROM admin_logs l 
        LEFT JOIN users u ON l.admin_id = u.id_pendaftar 
        ORDER BY l.created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-activity me-2 text-info"></i>System Activity Logs</h5>
    </div>
    <div class="card-body p-0">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger m-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Time</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="ps-4 text-nowrap text-muted small"><?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td class="fw-bold text-primary"><?php echo htmlspecialchars($log['admin_name'] ?? 'Unknown'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No activity logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>