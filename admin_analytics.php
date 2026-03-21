<?php

/**
 * Admin Analytics Dashboard - Chart.js Integration
 * Statistics Visualization untuk Admin
 */

require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/QueryCache.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch statistics data with caching
$db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get registrations by status (cached for 1 hour)
$statusData = QueryCache::get('analytics_status_data', function () use ($db) {
    $stmtStatus = $db->query("
        SELECT hasil_daftar, COUNT(*) as count 
        FROM hasil_daftar 
        GROUP BY hasil_daftar
    ");
    return $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
}, 3600);

// Get registrations by major/jurusan (cached for 1 hour)
$majorData = QueryCache::get('analytics_major_data', function () use ($db) {
    $stmtMajor = $db->query("
        SELECT jenis_jurusan, COUNT(*) as count 
        FROM data_peserta 
        GROUP BY jenis_jurusan 
        LIMIT 10
    ");
    return $stmtMajor->fetchAll(PDO::FETCH_ASSOC);
}, 3600);

// Get monthly registrations trend (cached for 1 hour)
$monthlyData = QueryCache::get('analytics_monthly_data', function () use ($db) {
    $stmtMonthly = $db->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM data_peserta 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    return $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);
}, 3600);

// Get total statistics (cached for 30 minutes)
$totals = QueryCache::get('analytics_totals', function () use ($db) {
    $stmtTotals = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM data_peserta) as total_registrations,
            (SELECT COUNT(*) FROM hasil_daftar WHERE hasil_daftar = 'LULUS SELEKSI') as total_passed,
            (SELECT COUNT(*) FROM users) as total_users,
            (SELECT COUNT(*) FROM unggah_dokumen) as total_documents
    ");
    return $stmtTotals->fetch(PDO::FETCH_ASSOC);
}, 1800);

// Get document upload status (cached for 1 hour)
$docsData = QueryCache::get('analytics_docs_data', function () use ($db) {
    $stmtDocs = $db->query("
        SELECT jenis_dokumen, COUNT(*) as count 
        FROM unggah_dokumen 
        GROUP BY jenis_dokumen
    ");
    return $stmtDocs->fetchAll(PDO::FETCH_ASSOC);
}, 3600);

// Prepare status labels dan colors
$statusMap = [
    'PROSES SELEKSI' => ['label' => 'Proses Seleksi', 'color' => '#FFC107'],
    'LULUS SELEKSI' => ['label' => 'Lulus Seleksi', 'color' => '#28A745'],
    'TIDAK LULUS' => ['label' => 'Tidak Lulus', 'color' => '#DC3545'],
    'DAFTAR ULANG' => ['label' => 'Daftar Ulang', 'color' => '#17A2B8']
];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Analytics Dashboard - SMK Lab Jakarta</title>


    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* INLINE ENHANCEMENTS STYLES FOR ADMIN ANALYTICS */

        /* Dark Mode */
        [data-theme="dark"] {
            --background-color: #1a1a1a;
            --default-color: #e0e0e0;
            --heading-color: #ffffff;
            --accent-color: #66bb6a;
            --surface-color: #2d2d2d;
        }

        body[data-theme="dark"] {
            background-color: var(--background-color);
            color: var(--default-color);
        }

        body[data-theme="dark"] .chart-container {
            background: var(--surface-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        body[data-theme="dark"] .stat-card {
            background: #2d2d2d;
            border-color: #3a3a3a;
        }

        /* Dark Mode Toggle Button */
        .dark-mode-toggle {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: white;
            transition: color 0.3s ease;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .dark-mode-toggle:hover {
            color: var(--accent-color);
        }

        /* Chart Container Styling */
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--surface-color);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            background: var(--surface-color);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--accent-color);
            margin: 10px 0;
        }

        .stat-card .stat-label {
            font-size: 0.9rem;
            color: var(--default-color);
            opacity: 0.8;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
            }

            .stat-card .stat-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body class="admin-analytics-page">
    <?php include 'sidebar.php'; ?>

    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="admin_home.php" class="logo d-flex align-items-center">
                <h1>📊 Analytics Dashboard</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="admin_home.php">Dashboard</a></li>
                    <li><a href="admin_manage_users.php">Manage Users</a></li>
                    <li><a href="admin_bulk_operations.php">Bulk Operations</a></li>
                    <li><a href="admin_advanced_export.php">Advanced Export</a></li>
                    <li><a href="admin_cache_manager.php">Cache Manager</a></li>
                    <li><a href="admin_query_optimization.php">Optimization</a></li>
                    <li><a href="admin_audit_trail.php">Audit Trail</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main" style="padding-top: 120px;">
        <div class="container">

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2>📈 Statistics & Analytics</h2>
                    <p class="text-muted">Real-time data visualization untuk monitoring sistem</p>
                </div>
                <button class="btn export-btn" onclick="exportCharts()">
                    <i class="bi bi-download"></i> Export
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-number"><?php echo number_format($totals['total_registrations']); ?></div>
                        <div class="stat-label">Total Pendaftar</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="stat-number"><?php echo number_format($totals['total_passed']); ?></div>
                        <div class="stat-label">Lulus Seleksi</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <div class="stat-number"><?php echo number_format($totals['total_documents']); ?></div>
                        <div class="stat-label">Dokumen Upload</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                        <div class="stat-number"><?php echo number_format($totals['total_users']); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row">

                <!-- Status Distribution Chart -->
                <div class="col-lg-6">
                    <div class="chart-title">📊 Distribusi Status Pendaftaran</div>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Trend Chart -->
                <div class="col-lg-6">
                    <div class="chart-title">📈 Tren Pendaftaran Bulanan</div>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

            </div>

            <div class="row mt-4">

                <!-- Major Distribution Chart -->
                <div class="col-lg-8">
                    <div class="chart-title">🎓 Distribusi Jurusan Pilihan</div>
                    <div class="chart-container" style="height: 400px;">
                        <canvas id="majorChart"></canvas>
                    </div>
                </div>

                <!-- Documents Chart -->
                <div class="col-lg-4">
                    <div class="chart-title">📄 Status Dokumen</div>
                    <div class="chart-container">
                        <canvas id="docsChart"></canvas>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // INLINE ENHANCEMENTS FOR ADMIN ANALYTICS
        // Chart colors tema-aware
        const getChartTheme = () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            return {
                textColor: isDark ? '#e0e0e0' : '#333',
                gridColor: isDark ? '#3a3a3a' : '#e0e0e0',
                backgroundColor: isDark ? 'rgba(102, 187, 106, 0.1)' : 'rgba(102, 187, 106, 0.05)'
            };
        };

        // Helper untuk update chart saat theme berubah
        const updateChartsTheme = () => {
            const theme = getChartTheme();
            // Update semua chart dengan theme baru (implementasi di bawah)
        };

        // Status Distribution Chart (Pie Chart)
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($statusData as $item): ?> '<?php echo isset($statusMap[$item['hasil_daftar']]) ? $statusMap[$item['hasil_daftar']]['label'] : $item['hasil_daftar']; ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [<?php echo implode(',', array_column($statusData, 'count')); ?>],
                    backgroundColor: [
                        <?php foreach ($statusData as $item): ?> '<?php echo isset($statusMap[$item['hasil_daftar']]) ? $statusMap[$item['hasil_daftar']]['color'] : '#999'; ?>',
                        <?php endforeach; ?>
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: getChartTheme().textColor,
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Monthly Trend Chart (Line Chart)
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach ($monthlyData as $item): ?> '<?php echo $item['month']; ?>', <?php endforeach; ?>],
                datasets: [{
                    label: 'Pendaftaran',
                    data: [<?php foreach ($monthlyData as $item): ?><?php echo $item['count']; ?>, <?php endforeach; ?>],
                    borderColor: 'var(--accent-color, #66bb6a)',
                    backgroundColor: 'rgba(102, 187, 106, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: 'var(--accent-color, #66bb6a)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: getChartTheme().textColor
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: getChartTheme().textColor
                        },
                        grid: {
                            color: getChartTheme().gridColor
                        }
                    },
                    x: {
                        ticks: {
                            color: getChartTheme().textColor
                        },
                        grid: {
                            color: getChartTheme().gridColor
                        }
                    }
                }
            }
        });

        // Major Distribution Chart (Bar Chart)
        const majorCtx = document.getElementById('majorChart').getContext('2d');
        const majorChart = new Chart(majorCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($majorData as $item): ?> '<?php echo $item['jenis_jurusan']; ?>', <?php endforeach; ?>],
                datasets: [{
                    label: 'Pendaftar',
                    data: [<?php foreach ($majorData as $item): ?><?php echo $item['count']; ?>, <?php endforeach; ?>],
                    backgroundColor: 'rgba(102, 187, 106, 0.7)',
                    borderColor: 'var(--accent-color, #66bb6a)',
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: getChartTheme().textColor
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: getChartTheme().textColor
                        },
                        grid: {
                            color: getChartTheme().gridColor
                        },
                        beginAtZero: true
                    },
                    y: {
                        ticks: {
                            color: getChartTheme().textColor
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Documents Chart
        const docsCtx = document.getElementById('docsChart').getContext('2d');
        const docsChart = new Chart(docsCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($docsData as $item): ?> '<?php echo $item['jenis_dokumen']; ?>', <?php endforeach; ?>],
                datasets: [{
                    label: 'Uploads',
                    data: [<?php foreach ($docsData as $item): ?><?php echo $item['count']; ?>, <?php endforeach; ?>],
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ],
                    borderRadius: 5
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: getChartTheme().textColor
                        },
                        grid: {
                            color: getChartTheme().gridColor
                        },
                        beginAtZero: true
                    },
                    y: {
                        ticks: {
                            color: getChartTheme().textColor
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Export function
        function exportCharts() {
            alert('📊 Export functionality akan diimplementasikan\n\nOpsi: PDF, PNG, CSV');
        }

        // Update charts saat tema berubah
        document.addEventListener('themechange', updateChartsTheme);

        // DARK MODE MANAGER (INLINE)
        class DarkModeManager {
            constructor() {
                this.themeKey = 'theme-preference';
                this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                this.init();
            }

            init() {
                const savedTheme = localStorage.getItem(this.themeKey);
                if (savedTheme) {
                    this.setTheme(savedTheme);
                } else {
                    const systemTheme = this.mediaQuery.matches ? 'dark' : 'light';
                    this.setTheme(systemTheme);
                }

                this.mediaQuery.addEventListener('change', (e) => {
                    const newTheme = e.matches ? 'dark' : 'light';
                    this.setTheme(newTheme);
                });

                this.createToggleButton();
            }

            setTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem(this.themeKey, theme);
                this.updateToggleButtonIcon(theme);
            }

            getTheme() {
                return document.documentElement.getAttribute('data-theme') || 'light';
            }

            toggleTheme() {
                const currentTheme = this.getTheme();
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                this.setTheme(newTheme);
            }

            createToggleButton() {
                const nav = document.querySelector('nav.navmenu');
                if (!nav) return;

                const toggleButton = document.createElement('button');
                toggleButton.className = 'dark-mode-toggle';
                toggleButton.type = 'button';
                toggleButton.setAttribute('aria-label', 'Toggle dark mode');
                toggleButton.title = 'Toggle Theme';

                this.updateToggleButtonIcon(this.getTheme());
                toggleButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleTheme();
                });

                const navbarEnd = nav.querySelector('ul');
                if (navbarEnd) {
                    const li = document.createElement('li');
                    li.appendChild(toggleButton);
                    navbarEnd.appendChild(li);
                }
            }

            updateToggleButtonIcon(theme) {
                const button = document.querySelector('.dark-mode-toggle');
                if (!button) return;

                if (theme === 'dark') {
                    button.innerHTML = '<i class="bi bi-sun-fill"></i>';
                } else {
                    button.innerHTML = '<i class="bi bi-moon-stars"></i>';
                }
            }
        }

        // Initialize dark mode on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                new DarkModeManager();
            });
        } else {
            new DarkModeManager();
        }
    </script>

</body>

</html>