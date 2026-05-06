<?php
require_once __DIR__ . '/conn.php';

// Auth check
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$page = $_GET['page'] ?? 'home';
$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');

$pages = [
    'home'           => ['label' => 'Dashboard',        'icon' => 'bi-speedometer2'],
    'pendaftar'      => ['label' => 'Data Pendaftar',   'icon' => 'bi-people'],
    'ranking'        => ['label' => 'Ranking & Hasil',  'icon' => 'bi-trophy'],
    'gelombang'      => ['label' => 'Pengaturan Gelombang', 'icon' => 'bi-calendar-week'],
    'announcements'  => ['label' => 'Pengumuman',       'icon' => 'bi-megaphone'],
    'backup'         => ['label' => 'Backup / Export',  'icon' => 'bi-download'],
    'change_password'=> ['label' => 'Ganti Password',   'icon' => 'bi-key'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — PPDB SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --sidebar-w: 240px; }
        body { background: #f0f2f5; }
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-w); background: #1a3c34;
            color: #fff; overflow-y: auto; z-index: 1040;
            transition: transform .25s;
        }
        .sidebar .nav-link { color: rgba(255,255,255,.75); border-radius: 8px; margin-bottom: 2px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.15); color: #fff; }
        .sidebar .nav-link.active { background: #198754; color: #fff; }
        .content { margin-left: var(--sidebar-w); min-height: 100vh; padding: 24px; }
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1039; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-backdrop.show { display: block; }
            .content { margin-left: 0; }
        }
        .page-title { font-size: 1.4rem; font-weight: 700; color: #1a3c34; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="sidebar p-3 d-flex flex-column">
    <div class="mb-4 mt-1">
        <div class="fw-bold fs-6 text-white">PPDB SMK Lab Jakarta</div>
        <small class="opacity-50">Panel Admin</small>
    </div>
    <ul class="nav flex-column flex-grow-1">
        <?php foreach ($pages as $key => $info): ?>
        <li class="nav-item">
            <a href="?page=<?= $key ?>" class="nav-link <?= $page === $key ? 'active' : '' ?>">
                <i class="bi <?= $info['icon'] ?> me-2"></i><?= $info['label'] ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <hr class="border-secondary">
    <div class="d-flex align-items-center gap-2 mb-2">
        <i class="bi bi-person-circle fs-5"></i>
        <span class="small"><?= $admin_name ?></span>
    </div>
    <a href="logout.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-box-arrow-right me-1"></i>Logout
    </a>
</div>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="content">
    <button class="btn btn-success d-md-none mb-3" id="sidebarToggle">
        <i class="bi bi-list"></i> Menu
    </button>
    <div class="page-title">
        <i class="bi <?= $pages[$page]['icon'] ?? 'bi-grid' ?> me-2"></i>
        <?= $pages[$page]['label'] ?? 'Dashboard' ?>
    </div>

    <?php
    $include_map = [
        'home'            => 'admin/home.php',
        'pendaftar'       => 'admin/pendaftar.php',
        'ranking'         => 'admin/ranking.php',
        'gelombang'       => 'admin/gelombang.php',
        'announcements'   => 'admin/announcements.php',
        'backup'          => 'admin/backup.php',
        'change_password' => 'admin/change_password.php',
    ];
    $file = $include_map[$page] ?? 'admin/home.php';
    if (file_exists(__DIR__ . '/' . $file)) {
        include __DIR__ . '/' . $file;
    } else {
        echo '<div class="alert alert-warning">Halaman tidak ditemukan.</div>';
    }
    ?>

    <footer class="text-center text-muted small mt-5 pb-3">
        &copy; <?= date('Y') ?> PPDB SMK Laboratorium Jakarta
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const sidebar   = document.querySelector('.sidebar');
const backdrop  = document.getElementById('sidebarBackdrop');
const toggleBtn = document.getElementById('sidebarToggle');
if (toggleBtn) {
    toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('show'); backdrop.classList.toggle('show'); });
    backdrop.addEventListener('click',  () => { sidebar.classList.remove('show'); backdrop.classList.remove('show'); });
}
</script>
</body>
</html>
