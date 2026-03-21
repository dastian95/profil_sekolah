<?php
require_once __DIR__ . '/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'taken', 'message' => 'Email sudah terdaftar.']);
    } else {
        echo json_encode(['status' => 'available', 'message' => 'Email tersedia.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>