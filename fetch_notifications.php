<?php
require_once __DIR__ . '/../src/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    // Ambil 5 notifikasi terbaru
    $stmt = $conn->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($notifs as $n) {
        $data[] = [
            'message' => $n['message'],
            'formatted_date' => date('d M Y H:i', strtotime($n['created_at']))
        ];
    }

    echo json_encode(['success' => true, 'notifications' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>