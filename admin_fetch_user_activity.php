<?php
require_once __DIR__ . '/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

$uid = $_POST['uid'] ?? 0;

try {
    $stmt = $conn->prepare("SELECT action, ip_address, created_at FROM user_activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$uid]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($logs)) {
        echo json_encode(['success' => true, 'data' => []]);
    } else {
        foreach ($logs as &$log) {
            $log['formatted_date'] = date('d M Y H:i', strtotime($log['created_at']));
        }
        echo json_encode(['success' => true, 'data' => $logs]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
