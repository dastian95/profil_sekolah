<?php

/**
 * API Endpoints for Statistics & Data
 * Used by admin and user dashboards
 */

require_once __DIR__ . '/../src/conn.php';
require_once __DIR__ . '/../src/rate_limiter.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    $endpoint = $_GET['endpoint'] ?? null;

    if (!$endpoint) {
        throw new Exception('No endpoint specified', 400);
    }

    // Rate limiting
    $limiter = new RateLimiter($_SESSION['user_id'], 'api_' . $endpoint);
    if (!$limiter->isAllowed(60, 60)) { // 60 requests per minute
        throw new Exception('Rate limit exceeded', 429);
    }

    // ============ PUBLIC ENDPOINTS ============

    // 1. User Dashboard Statistics
    if ($endpoint === 'user_dashboard_stats' && $_SESSION['user_role'] === 'user') {
        $user_id = $_SESSION['user_id'];

        // Registration Status
        $stmt = $conn->prepare("SELECT status_pendaftaran FROM pendaftar WHERE id_pendaftar = ?");
        $stmt->execute([$user_id]);
        $status = $stmt->fetchColumn();

        // Email Verification Status
        $stmt = $conn->prepare("SELECT is_verified FROM users WHERE id_pendaftar = ?");
        $stmt->execute([$user_id]);
        $is_verified = $stmt->fetchColumn();

        // Document Upload Progress
        $stmt = $conn->prepare("SELECT COUNT(*) FROM jenis_dokumen");
        $stmt->execute();
        $total_docs = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(DISTINCT id_jenis) FROM unggah_dokumen WHERE id_pendaftar = ?");
        $stmt->execute([$user_id]);
        $uploaded_docs = $stmt->fetchColumn();

        $progress = $total_docs > 0 ? round(($uploaded_docs / $total_docs) * 100) : 0;

        // Exam Schedule
        $stmt = $conn->prepare("
            SELECT u.jurusan 
            FROM data_peserta u 
            WHERE u.id_pendaftar = ?
        ");
        $stmt->execute([$user_id]);
        $major = $stmt->fetchColumn();

        if ($major) {
            $stmt = $conn->prepare("
                SELECT * FROM jadwal_ujian 
                WHERE jurusan = ? 
                ORDER BY tanggal ASC 
                LIMIT 1
            ");
            $stmt->execute([$major]);
            $exam_schedule = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $exam_schedule = null;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'registration_status' => $status ?? 'Draft',
                'email_verified' => (bool)$is_verified,
                'document_progress' => $progress,
                'documents_uploaded' => $uploaded_docs,
                'documents_total' => $total_docs,
                'exam_schedule' => $exam_schedule,
                'major' => $major
            ]
        ]);
    }

    // 2. Admin Dashboard Statistics
    elseif ($endpoint === 'admin_dashboard_stats' && $_SESSION['user_role'] === 'admin') {
        // Total Users & Verified
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
        $stmt->execute();
        $total_users = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND is_verified = 1");
        $stmt->execute();
        $verified_users = $stmt->fetchColumn();

        // Registration Stats
        $stmt = $conn->prepare("SELECT COUNT(*) FROM pendaftar");
        $stmt->execute();
        $total_applications = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE status_pendaftaran = 'Terkirim'");
        $stmt->execute();
        $submitted = $stmt->fetchColumn();

        // Document Verification Stats
        $stmt = $conn->prepare("SELECT COUNT(*) FROM unggah_dokumen WHERE is_verified = 1");
        $stmt->execute();
        $verified_docs = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM unggah_dokumen WHERE is_verified = 0");
        $stmt->execute();
        $pending_docs = $stmt->fetchColumn();

        // Enrollment by Major
        $stmt = $conn->prepare("SELECT jurusan, COUNT(*) as total FROM data_peserta GROUP BY jurusan");
        $stmt->execute();
        $major_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrollment by Route
        $stmt = $conn->prepare("SELECT jalur_daftar, COUNT(*) as total FROM data_peserta GROUP BY jalur_daftar");
        $stmt->execute();
        $route_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'total_users' => $total_users,
                'verified_users' => $verified_users,
                'total_applications' => $total_applications,
                'submitted_applications' => $submitted,
                'verified_documents' => $verified_docs,
                'pending_documents' => $pending_docs,
                'enrollment_by_major' => $major_stats,
                'enrollment_by_route' => $route_stats
            ]
        ]);
    }

    // 3. User Notifications
    elseif ($endpoint === 'notifications' && $_SESSION['user_role'] === 'user') {
        $user_id = $_SESSION['user_id'];
        $limit = min((int)($_GET['limit'] ?? 10), 50); // Max 50

        $stmt = $conn->prepare(
            "SELECT id, message, created_at, is_read 
             FROM notifications 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ?"
        );
        $stmt->execute([$user_id, $limit]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $notifications
        ]);
    }

    // 4. Exam Schedule
    elseif ($endpoint === 'exam_schedule') {
        $stmt = $conn->prepare(
            "SELECT jurusan, tanggal, jam_mulai, jam_selesai, ruangan 
             FROM jadwal_ujian 
             ORDER BY tanggal ASC"
        );
        $stmt->execute();
        $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $schedule
        ]);
    }

    // 5. Document Types
    elseif ($endpoint === 'document_types') {
        $stmt = $conn->prepare(
            "SELECT id_jenis, kode_jenis, nama_jenis, deskripsi 
             FROM jenis_dokumen 
             ORDER BY id_jenis ASC"
        );
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $documents
        ]);
    }

    // 6. Recent Activities (Admin only)
    elseif ($endpoint === 'recent_activities' && $_SESSION['user_role'] === 'admin') {
        $limit = min((int)($_GET['limit'] ?? 10), 50);

        $stmt = $conn->prepare(
            "SELECT admin_id, action, details, timestamp 
             FROM admin_logs 
             ORDER BY timestamp DESC 
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $activities
        ]);
    }

    // Unknown endpoint
    else {
        throw new Exception('Unknown endpoint', 404);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
