<?php

/**
 * Feature Status & Navigation Dashboard
 * Quick overview of all system features
 */

require_once __DIR__ . '/conn.php';

// Get system user
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$is_user = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
$is_logged_in = isset($_SESSION['user_id']);

// Quick statistics
$total_users = 0;
$total_registrations = 0;
$total_documents = 0;

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stmt->execute();
    $total_users = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM data_peserta");
    $stmt->execute();
    $total_registrations = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM unggah_dokumen");
    $stmt->execute();
    $total_documents = $stmt->fetchColumn();
} catch (Exception $e) {
}

$features = [
    'User Features' => [
        [
            'title' => 'User Dashboard',
            'icon' => 'speedometer2',
            'description' => 'View your application status, documents, and notifications',
            'link' => 'dashboard.php',
            'available' => $is_user,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Profile Management',
            'icon' => 'person-check',
            'description' => 'Complete your profile and upload profile picture',
            'link' => 'profile.php',
            'available' => $is_user,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Document Upload',
            'icon' => 'file-earmark-arrow-up',
            'description' => 'Upload required documents (5 types)',
            'link' => 'application.php',
            'available' => $is_user,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'School Information',
            'icon' => 'info-circle',
            'description' => 'Learn about programs, majors, and admission routes',
            'link' => 'about_school.php',
            'available' => true,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Notifications',
            'icon' => 'bell',
            'description' => 'Check your notifications and announcements',
            'link' => 'dashboard.php',
            'available' => $is_user,
            'status' => 'ACTIVE'
        ]
    ],
    'Admin Features' => [
        [
            'title' => 'Admin Dashboard',
            'icon' => 'graph-up',
            'description' => 'View system statistics and key metrics',
            'link' => 'admin_dashboard.php?page=home',
            'available' => $is_admin,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'User Management',
            'icon' => 'people',
            'description' => 'Manage user accounts and verify email',
            'link' => 'admin_manage_users.php',
            'available' => $is_admin,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Document Verification',
            'icon' => 'file-earmark-check',
            'description' => 'Review and verify uploaded documents',
            'link' => 'admin_document_users.php',
            'available' => $is_admin,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Announcements',
            'icon' => 'megaphone',
            'description' => 'Publish system announcements',
            'link' => 'admin_announcements.php',
            'available' => $is_admin,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Exam Schedules',
            'icon' => 'calendar-week',
            'description' => 'Configure exam schedules for majors',
            'link' => 'admin_dashboard.php?page=schedule',
            'available' => $is_admin,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Analytics & Reports',
            'icon' => 'bar-chart',
            'description' => 'View analytics and export reports',
            'link' => 'admin_comparison.php',
            'available' => $is_admin,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Activity Logs',
            'icon' => 'clock-history',
            'description' => 'View admin activity logs',
            'link' => 'admin_logs.php',
            'available' => $is_admin,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'System Verification',
            'icon' => 'check-circle',
            'description' => 'Test system health and infrastructure',
            'link' => 'admin_system_verification.php',
            'available' => $is_admin,
            'status' => 'ACTIVE'
        ]
    ],
    'Data & APIs' => [
        [
            'title' => 'Statistics API',
            'icon' => 'code-square',
            'description' => 'RESTful API for dashboard statistics',
            'link' => 'api_statistics.php',
            'available' => $is_logged_in,
            'status' => 'ACTIVE'
        ],
        [
            'title' => 'Notifications API',
            'icon' => 'bell-slash',
            'description' => 'Retrieve user notifications',
            'link' => 'fetch_notifications.php',
            'available' => true,
            'status' => 'ACTIVE'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features & Navigation - SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --success: #48bb78;
            --danger: #f56565;
        }

        body {
            background: linear-gradient(135deg, var(--primary) 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-custom .nav-link {
            color: #333 !important;
            font-weight: 500;
        }

        .navbar-custom .nav-link:hover {
            color: var(--primary) !important;
        }

        .navbar-custom .btn {
            margin-left: 10px;
        }

        .page-title {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-top: 100px;
        }

        .page-title h1 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .container {
            max-width: 1200px;
        }

        .feature-category {
            margin-bottom: 40px;
        }

        .category-title {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items-center;
        }

        .category-title .category-icon {
            margin-right: 15px;
            font-size: 2.2rem;
        }

        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .feature-card.disabled {
            opacity: 0.6;
            pointer-events: none;
            border-left-color: #ccc;
        }

        .feature-card .feature-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .feature-card .feature-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-right: 15px;
        }

        .feature-card .feature-title {
            font-weight: 600;
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .feature-card .feature-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }

        .feature-card .feature-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #d4edda;
            color: #155724;
        }

        .feature-card a {
            display: inline-block;
            margin-top: 10px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .feature-card a:hover {
            text-decoration: underline;
        }

        .statistics-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat {
            flex: 1;
            text-align: center;
            padding: 15px;
        }

        .stat .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat .stat-label {
            color: #666;
            font-size: 0.95rem;
            margin-top: 5px;
        }

        .login-prompt {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .login-prompt p {
            color: #666;
            margin-bottom: 15px;
        }

        .login-prompt .btn {
            margin: 5px;
        }

        .footer-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-top: 40px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .footer-section p {
            color: #666;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-speedometer2 me-2"></i><strong>SMK Lab Jakarta</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="ms-auto">
                    <?php if ($is_logged_in): ?>
                        <a class="nav-link d-inline" href="<?php echo $is_admin ? 'admin_dashboard.php' : 'dashboard.php'; ?>">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                        <a class="nav-link d-inline" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <a class="btn btn-sm btn-outline-primary me-2" href="login.php">Login</a>
                        <a class="btn btn-sm btn-primary" href="register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Title -->
        <div class="page-title">
            <h1><i class="bi bi-grid-3x3 me-2"></i>Features & Services</h1>
            <p class="text-muted">Complete guide to all available features in the registration system</p>
        </div>

        <!-- Statistics -->
        <div class="statistics-box">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat">
                        <div class="stat-number"><?php echo $total_users; ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat">
                        <div class="stat-number"><?php echo $total_registrations; ?></div>
                        <div class="stat-label">Registrations</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat">
                        <div class="stat-number"><?php echo $total_documents; ?></div>
                        <div class="stat-label">Documents Uploaded</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Prompt (if not logged in) -->
        <?php if (!$is_logged_in): ?>
            <div class="login-prompt">
                <h4>Welcome to SMK Laboratorium Jakarta Registration System</h4>
                <p>Please login or register to access user features</p>
                <a href="login.php" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a>
                <a href="register.php" class="btn btn-outline-primary"><i class="bi bi-person-plus me-2"></i>Register</a>
            </div>
        <?php endif; ?>

        <!-- Features by Category -->
        <?php foreach ($features as $category => $items): ?>
            <div class="feature-category">
                <?php
                $icon_map = [
                    'User Features' => 'person-circle',
                    'Admin Features' => 'shield-lock',
                    'Data & APIs' => 'code-square'
                ];
                $icon = $icon_map[$category] ?? 'star';
                ?>
                <div class="category-title">
                    <i class="bi bi-<?php echo $icon; ?> category-icon"></i>
                    <?php echo $category; ?>
                </div>

                <div class="row">
                    <?php foreach ($items as $feature): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="feature-card <?php echo !$feature['available'] ? 'disabled' : ''; ?>">
                                <div class="feature-header">
                                    <div>
                                        <i class="bi bi-<?php echo $feature['icon']; ?> feature-icon"></i>
                                        <div class="feature-title"><?php echo htmlspecialchars($feature['title']); ?></div>
                                        <div class="feature-description"><?php echo htmlspecialchars($feature['description']); ?></div>
                                    </div>
                                </div>

                                <span class="feature-status"><?php echo $feature['status']; ?></span>

                                <?php if ($feature['available']): ?>
                                    <br>
                                    <a href="<?php echo htmlspecialchars($feature['link']); ?>">
                                        Access <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                <?php else: ?>
                                    <br>
                                    <span style="color: #999; font-size: 0.9rem; cursor: not-allowed;">
                                        <i class="bi bi-lock me-1"></i>Login Required
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Footer -->
        <div class="footer-section">
            <h5>System Information</h5>
            <p><i class="bi bi-check-circle-fill text-success me-2"></i><strong>All Features Active</strong></p>
            <p class="text-muted small mb-3">The registration system is fully operational and ready for use</p>
            <a href="IMPLEMENTATION_COMPLETE.md" class="btn btn-sm btn-outline-primary" download>
                <i class="bi bi-download me-1"></i>Download Documentation
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>