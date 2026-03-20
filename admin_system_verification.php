<?php

/**
 * System Verification & Testing Page
 * Tests all major features and displays system health
 */

require_once __DIR__ . '/../src/conn.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$tests = [];

// Test 1: Database Connection
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    $tests['database_connection'] = ['status' => 'PASS', 'message' => "$count users found in database"];
} catch (Exception $e) {
    $tests['database_connection'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 2: Users Table
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE is_verified = 1 AND role = 'user'");
    $stmt->execute();
    $verified = $stmt->fetchColumn();
    $tests['users_table'] = ['status' => 'PASS', 'message' => "Users: $count Total | Verified: $verified"];
} catch (Exception $e) {
    $tests['users_table'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 3: Registration Data
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM data_peserta");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT jurusan) FROM data_peserta");
    $stmt->execute();
    $majors = $stmt->fetchColumn();
    $tests['registration_data'] = ['status' => 'PASS', 'message' => "Registrations: $total | Majors: $majors"];
} catch (Exception $e) {
    $tests['registration_data'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 4: Document Management
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jenis_dokumen");
    $stmt->execute();
    $doc_types = $stmt->fetchColumn();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM unggah_dokumen");
    $stmt->execute();
    $uploads = $stmt->fetchColumn();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM unggah_dokumen WHERE is_verified = 1");
    $stmt->execute();
    $verified_docs = $stmt->fetchColumn();
    $tests['document_management'] = ['status' => 'PASS', 'message' => "Doc Types: $doc_types | Uploads: $uploads | Verified: $verified_docs"];
} catch (Exception $e) {
    $tests['document_management'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 5: Exam Schedules
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jadwal_ujian");
    $stmt->execute();
    $schedules = $stmt->fetchColumn();
    $tests['exam_schedules'] = ['status' => 'PASS', 'message' => "Schedules: $schedules"];
} catch (Exception $e) {
    $tests['exam_schedules'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 6: Notifications System
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    $tests['notifications_system'] = ['status' => 'PASS', 'message' => "Notifications: $count"];
} catch (Exception $e) {
    $tests['notifications_system'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 7: Admin Logs
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin_logs");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    $tests['admin_logs'] = ['status' => 'PASS', 'message' => "Log Entries: $count"];
} catch (Exception $e) {
    $tests['admin_logs'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 8: Announcements
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM announcements");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    $tests['announcements'] = ['status' => 'PASS', 'message' => "Announcements: $count"];
} catch (Exception $e) {
    $tests['announcements'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 9: File System
try {
    $upload_dir = 'uploads';
    if (is_dir($upload_dir)) {
        $files = count(array_diff(scandir($upload_dir), ['.', '..']));
        $tests['file_system'] = ['status' => 'PASS', 'message' => "Upload Dir Exists | Files: $files"];
    } else {
        $tests['file_system'] = ['status' => 'WARN', 'message' => 'Upload directory does not exist (can be created on demand)'];
    }
} catch (Exception $e) {
    $tests['file_system'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 10: Environment Configuration
try {
    if (!empty($_ENV['DB_HOST'] ?? null)) {
        $tests['environment_config'] = ['status' => 'PASS', 'message' => 'Environment variables loaded'];
    } else {
        $tests['environment_config'] = ['status' => 'WARN', 'message' => 'Environment variables not fully configured'];
    }
} catch (Exception $e) {
    $tests['environment_config'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Test 11: Page Files
try {
    $required_pages = [
        'dashboard.php' => 'User Dashboard',
        'profile.php' => 'User Profile',
        'application.php' => 'Document Upload',
        'about_school.php' => 'School Information',
        'admin_dashboard.php' => 'Admin Dashboard',
        'admin_manage_users.php' => 'User Management',
        'admin_document_users.php' => 'Document Verification',
        'admin_announcements.php' => 'Announcements'
    ];

    $missing = [];
    foreach ($required_pages as $file => $label) {
        if (!file_exists($file)) {
            $missing[] = $label;
        }
    }

    if (empty($missing)) {
        $tests['page_files'] = ['status' => 'PASS', 'message' => 'All required pages present'];
    } else {
        $tests['page_files'] = ['status' => 'WARN', 'message' => 'Missing: ' . implode(', ', $missing)];
    }
} catch (Exception $e) {
    $tests['page_files'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
}

// Calculate overall health
$pass_count = count(array_filter($tests, fn($t) => $t['status'] === 'PASS'));
$fail_count = count(array_filter($tests, fn($t) => $t['status'] === 'FAIL'));
$warn_count = count(array_filter($tests, fn($t) => $t['status'] === 'WARN'));
$total_tests = count($tests);
$health_percentage = round(($pass_count / $total_tests) * 100);

// Determine health status color
if ($fail_count > 0) {
    $health_color = 'danger';
    $health_label = 'CRITICAL';
} elseif ($warn_count > 0) {
    $health_color = 'warning';
    $health_label = 'CAUTION';
} else {
    $health_color = 'success';
    $health_label = 'HEALTHY';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Verification - SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .container {
            max-width: 900px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .health-badge {
            font-size: 0.9rem;
            padding: 8px 16px;
        }

        .test-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .test-card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .test-card .test-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .test-card .test-message {
            font-size: 0.9rem;
            color: #666;
        }

        .test-badge {
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .test-badge.pass {
            background: #d4edda;
            color: #155724;
        }

        .test-badge.fail {
            background: #f8d7da;
            color: #721c24;
        }

        .test-badge.warn {
            background: #fff3cd;
            color: #856404;
        }

        .summary-box {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .summary-stat {
            text-align: center;
            padding: 15px;
        }

        .summary-stat .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .summary-stat .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .progress-ring {
            transform: rotate(-90deg);
            origin: 50% 50%;
        }

        .progress-ring-circle {
            transition: stroke-dashoffset 0.35s;
            transform-origin: 50% 50%;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1><i class="bi bi-check-circle me-2"></i>System Verification</h1>
                    <p class="text-muted mb-0">Testing all features and infrastructure</p>
                </div>
                <div class="text-end">
                    <div class="badge bg-<?php echo $health_color; ?> health-badge mb-2 d-block">
                        <?php echo $health_label; ?>
                    </div>
                    <div class="text-muted small">Health: <strong><?php echo $health_percentage; ?>%</strong></div>
                </div>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="summary-box">
            <div class="row">
                <div class="col-md-3">
                    <div class="summary-stat">
                        <div class="stat-number text-success"><?php echo $pass_count; ?></div>
                        <div class="stat-label">Passed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-stat">
                        <div class="stat-number text-warning"><?php echo $warn_count; ?></div>
                        <div class="stat-label">Warnings</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-stat">
                        <div class="stat-number text-danger"><?php echo $fail_count; ?></div>
                        <div class="stat-label">Failed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-stat">
                        <div class="stat-number text-primary"><?php echo $total_tests; ?></div>
                        <div class="stat-label">Total Tests</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <div id="testResults">
            <?php foreach ($tests as $test_name => $test_result): ?>
                <div class="test-card">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1">
                            <div class="test-name">
                                <?php
                                // Convert test_name to readable format
                                $readable_name = str_replace('_', ' ', $test_name);
                                $readable_name = ucwords($readable_name);
                                echo htmlspecialchars($readable_name);
                                ?>
                            </div>
                            <div class="test-message"><?php echo htmlspecialchars($test_result['message']); ?></div>
                        </div>
                        <span class="test-badge <?php echo strtolower($test_result['status']); ?>">
                            <?php echo $test_result['status']; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Actions -->
        <div class="summary-box">
            <h5 class="mb-3"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
            <div class="btn-group-vertical w-100" role="group">
                <a href="dashboard.php" class="btn btn-outline-primary text-start">
                    <i class="bi bi-speedometer2 me-2"></i>View Admin Dashboard
                </a>
                <a href="admin_manage_users.php" class="btn btn-outline-primary text-start">
                    <i class="bi bi-people me-2"></i>Manage Users
                </a>
                <a href="admin_document_users.php" class="btn btn-outline-primary text-start">
                    <i class="bi bi-file-earmark-check me-2"></i>Verify Documents
                </a>
                <a href="admin_announcements.php" class="btn btn-outline-primary text-start">
                    <i class="bi bi-megaphone me-2"></i>Manage Announcements
                </a>
                <a href="about_school.php" class="btn btn-outline-primary text-start">
                    <i class="bi bi-info-circle me-2"></i>View School Info
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center" style="color: white; margin-top: 40px;">
            <p>SMK Laboratorium Jakarta Registration System</p>
            <small>System running on PHP <?php echo phpversion(); ?></small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>