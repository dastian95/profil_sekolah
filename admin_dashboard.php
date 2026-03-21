<?php
// Check for "Remember Me" cookie if session is not set
require_once __DIR__ . '/conn.php'; // Includes session_start()
require_once __DIR__ . '/check_remember_me.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$page = $_GET['page'] ?? 'home';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>

<body class="admin-dashboard">

    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column flex-shrink-0 p-3">
        <div class="d-flex align-items-center justify-content-between sidebar-header mb-3">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none">
                <span class="fs-4">Admin Panel</span>
            </a>
            <button type="button" class="btn btn-link text-white d-none d-md-block p-0" id="desktopToggle"><i class="bi bi-chevron-left"></i></button>
        </div>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto" id="sidebarMenu">
            <li class="nav-item" data-id="dashboard">
                <a href="?page=home" class="nav-link <?php echo $page === 'home' ? 'active' : ''; ?>" aria-current="page" title="Dashboard" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item" data-id="users">
                <a href="?page=users" class="nav-link <?php echo $page === 'users' ? 'active' : ''; ?>" title="Manage Users" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-people me-2"></i>
                    Manage Users
                </a>
            </li>
            <li class="nav-item" data-id="documents">
                <a href="?page=documents" class="nav-link <?php echo $page === 'documents' ? 'active' : ''; ?>" title="Document Users" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    Document Users
                </a>
            </li>
            <li class="nav-item" data-id="announcements">
                <a href="?page=announcements" class="nav-link <?php echo $page === 'announcements' ? 'active' : ''; ?>" title="Announcements" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-megaphone me-2"></i> Announcements
                </a>
            </li>
            <li class="nav-item" data-id="schedule">
                <a href="?page=schedule" class="nav-link <?php echo $page === 'schedule' ? 'active' : ''; ?>" title="Atur Jadwal Ujian" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-calendar-week me-2"></i> Atur Jadwal
                </a>
            </li>
            <li class="nav-item" data-id="graduation">
                <a href="?page=graduation" class="nav-link <?php echo $page === 'graduation' ? 'active' : ''; ?>" title="Data Kelulusan" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-mortarboard me-2"></i> Data Kelulusan
                </a>
            </li>
            <li class="nav-item" data-id="comparison">
                <a href="?page=comparison" class="nav-link <?php echo $page === 'comparison' ? 'active' : ''; ?>" title="Analytics" data-bs-toggle="tooltip" data-bs-placement="right">
                    <i class="bi bi-graph-up-arrow me-2"></i> Analytics & Comparison
                </a>
            </li>
            <li class="nav-item" data-id="change_password">
                <a href="?page=change_password" class="nav-link <?php echo $page === 'change_password' ? 'active' : ''; ?>" title="Change Password" data-bs-toggle="tooltip" data-bs-placement="right"><i class="bi bi-key me-2"></i> Change Password</a>
            </li>
            <li class="nav-item" data-id="dark_mode">
                <a href="#" class="nav-link" id="darkModeToggle" title="Dark Mode" data-bs-toggle="tooltip" data-bs-placement="right"><i class="bi bi-moon-stars me-2"></i> Dark Mode</a>
            </li>
        </ul>
        <hr>
        <div class="dropdown dropup">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2"></i>
                <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Sign out</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid">
            <button class="btn btn-primary d-md-none mb-3" id="sidebarToggle">
                <i class="bi bi-list"></i> Menu
            </button>
            <h2 class="mb-4 text-dark">
                <?php
                if ($page === 'documents') echo 'Document Verification';
                elseif ($page === 'users') echo 'User Management';
                elseif ($page === 'logs') echo 'Activity Logs';
                elseif ($page === 'announcements') echo 'Manage Announcements';
                elseif ($page === 'schedule') echo 'Pengaturan Jadwal Ujian';
                elseif ($page === 'graduation') echo 'Input Data Kelulusan';
                elseif ($page === 'comparison') echo 'Analytics & Data Comparison';
                elseif ($page === 'change_password') echo 'Security Settings';
                else echo 'Dashboard Overview';
                ?>
            </h2>

            <?php
            if ($page === 'documents') {
                include 'admin/admin_document_users.php';
            } elseif ($page === 'users') {
                include 'admin/admin_manage_users.php';
            } elseif ($page === 'logs') {
                include 'admin/admin_logs.php';
            } elseif ($page === 'announcements') {
                include 'admin/admin_announcements.php';
            } elseif ($page === 'schedule') {
                include 'admin/admin_manage_schedule.php';
            } elseif ($page === 'graduation') {
                include 'admin/admin_manage_graduation.php';
            } elseif ($page === 'comparison') {
                include 'admin/admin_comparison.php';
            } elseif ($page === 'change_password') {
                include 'admin/admin_change_password.php';
            } else {
                include 'admin/admin_home.php';
            }
            ?>

        </div>
        <footer class="dashboard-footer">
            Copyright &copy; <?php echo date('Y'); ?> <strong>SMK Laboratorium Jakarta</strong>. All Rights Reserved.
        </footer>
    </div>

    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Ready to Leave?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });
            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            });
        }

        // Dark Mode Logic
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;

        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
            if (darkModeToggle) darkModeToggle.innerHTML = '<i class="bi bi-sun me-2"></i> Light Mode';
        }

        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', (e) => {
                e.preventDefault();
                body.classList.toggle('dark-mode');
                if (body.classList.contains('dark-mode')) {
                    localStorage.setItem('darkMode', 'enabled');
                    darkModeToggle.innerHTML = '<i class="bi bi-sun me-2"></i> Light Mode';
                } else {
                    localStorage.setItem('darkMode', 'disabled');
                    darkModeToggle.innerHTML = '<i class="bi bi-moon-stars me-2"></i> Dark Mode';
                }
            });
        }

        // Swipe to close Sidebar (Mobile)
        let touchStartX = 0;
        let touchEndX = 0;

        sidebar.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, {
            passive: true
        });

        sidebar.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchEndX < touchStartX && (touchStartX - touchEndX > 50)) {
                if (window.innerWidth <= 768 && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                }
            }
        }, {
            passive: true
        });

        // Desktop Sidebar Collapse
        const desktopToggle = document.getElementById('desktopToggle');

        // Initialize Tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        function toggleTooltips() {
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            tooltipList.forEach(t => isCollapsed ? t.enable() : t.disable());
        }

        if (desktopToggle) {
            // Load preference
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
                desktopToggle.innerHTML = '<i class="bi bi-chevron-right"></i>';
            }

            // Initial tooltip state
            toggleTooltips();

            desktopToggle.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
                desktopToggle.innerHTML = isCollapsed ? '<i class="bi bi-chevron-right"></i>' : '<i class="bi bi-chevron-left"></i>';
                toggleTooltips();
            });
        }

        // Sidebar Drag and Drop
        const sidebarMenu = document.getElementById('sidebarMenu');
        if (sidebarMenu) {
            Sortable.create(sidebarMenu, {
                animation: 150,
                ghostClass: 'bg-secondary',
                dataIdAttr: 'data-id',
                store: {
                    get: function(sortable) {
                        const order = localStorage.getItem('sidebarOrder_admin');
                        return order ? order.split('|') : [];
                    },
                    set: function(sortable) {
                        const order = sortable.toArray();
                        localStorage.setItem('sidebarOrder_admin', order.join('|'));
                    }
                }
            });
        }
    </script>
</body>

</html>