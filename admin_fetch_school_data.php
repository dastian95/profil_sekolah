<?php
require_once __DIR__ . '/conn.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_POST['school_name'])) {
    $school = $_POST['school_name'];
    try {
        $stmt = $conn->prepare("
            SELECT dp.nama, dp.nisn, dp.jurusan, p.status_pendaftaran 
            FROM data_peserta dp 
            LEFT JOIN pendaftar p ON dp.id_pendaftar = p.id_pendaftar 
            WHERE dp.asal_sekolah = ?
            ORDER BY dp.nama ASC
        ");
        $stmt->execute([$school]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $students]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
