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
    'home'           => ['label' => 'Dashboard',            'icon' => 'bi-speedometer2',    'group' => 'Utama'],
    'pendaftar'      => ['label' => 'Data Pendaftar',       'icon' => 'bi-people-fill',     'group' => 'Manajemen'],
    'ranking'        => ['label' => 'Ranking & Hasil',      'icon' => 'bi-trophy-fill',     'group' => 'Manajemen'],
    'gelombang'      => ['label' => 'Pengaturan Gelombang', 'icon' => 'bi-calendar-week',   'group' => 'Pengaturan'],
    'announcements'  => ['label' => 'Pengumuman',           'icon' => 'bi-megaphone-fill',  'group' => 'Pengaturan'],
    'backup'         => ['label' => 'Backup / Export',      'icon' => 'bi-cloud-download',  'group' => 'Sistem'],
    'change_password'=> ['label' => 'Ganti Password',       'icon' => 'bi-shield-lock',     'group' => 'Sistem'],
];

// Group menu by section
$grouped = [];
foreach ($pages as $key => $info) {
    $grouped[$info['group']][$key] = $info;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — PPDB SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 260px;
            --primary: #198754;
            --primary-dark: #146c43;
            --primary-light: #d1e7dd;
            --sidebar-bg: linear-gradient(180deg, #1a3c34 0%, #0f2620 100%);
            --bg: #f4f6f9;
            --card-shadow: 0 1px 3px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.06);
            --card-shadow-hover: 0 4px 12px rgba(0,0,0,.08), 0 2px 4px rgba(0,0,0,.06);
        }
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        body { background: var(--bg); color: #2d3748; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-w); background: var(--sidebar-bg);
            color: #fff; overflow-y: auto; z-index: 1040;
            transition: transform .25s ease;
            box-shadow: 2px 0 8px rgba(0,0,0,.08);
        }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 8px; }

        .sidebar-brand {
            padding: 20px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-brand .brand-icon {
            width: 40px; height: 40px; background: var(--primary);
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; box-shadow: 0 4px 10px rgba(25,135,84,.3);
        }
        .sidebar-brand .brand-text { line-height: 1.2; }
        .sidebar-brand .brand-text strong { font-size: .95rem; display: block; }
        .sidebar-brand .brand-text small { font-size: .7rem; opacity: .65; letter-spacing: .5px; text-transform: uppercase; }

        .nav-group-label {
            font-size: .68rem; text-transform: uppercase; letter-spacing: 1.2px;
            opacity: .45; padding: 16px 24px 6px; font-weight: 600;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.72);
            padding: 10px 16px; margin: 2px 12px; border-radius: 8px;
            font-size: .9rem; font-weight: 500;
            display: flex; align-items: center; gap: 12px;
            transition: all .15s ease;
        }
        .sidebar .nav-link i { font-size: 1.05rem; width: 20px; text-align: center; }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.07); color: #fff;
            transform: translateX(2px);
        }
        .sidebar .nav-link.active {
            background: var(--primary); color: #fff;
            box-shadow: 0 4px 12px rgba(25,135,84,.35);
        }
        .sidebar .nav-link.active i { color: #fff; }

        .sidebar-footer {
            position: sticky; bottom: 0; padding: 16px;
            background: linear-gradient(180deg, transparent 0%, #0f2620 30%);
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .user-card {
            background: rgba(255,255,255,.06); border-radius: 10px;
            padding: 10px 12px; display: flex; align-items: center; gap: 10px;
            margin-bottom: 10px;
        }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #20c997);
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: .9rem; color: #fff;
        }
        .user-info .name { font-size: .85rem; font-weight: 600; line-height: 1.2; }
        .user-info .role { font-size: .68rem; opacity: .6; }
        .btn-logout {
            width: 100%; background: rgba(220,53,69,.15);
            color: #ff8a95; border: 1px solid rgba(220,53,69,.25);
            padding: 8px 12px; border-radius: 8px; font-size: .85rem;
            font-weight: 500; transition: all .15s; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-logout:hover { background: #dc3545; color: #fff; border-color: #dc3545; }

        /* Top Bar */
        .topbar {
            position: sticky; top: 0; z-index: 1030;
            background: #fff; height: 64px;
            border-bottom: 1px solid #e8ecf0;
            display: flex; align-items: center; padding: 0 28px;
            box-shadow: 0 1px 3px rgba(0,0,0,.02);
        }
        .topbar h1 {
            font-size: 1.15rem; font-weight: 600; margin: 0;
            color: #1a3c34; display: flex; align-items: center; gap: 10px;
        }
        .topbar .breadcrumb-trail { color: #6c757d; font-size: .82rem; }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }
        .topbar-right .badge-time {
            background: var(--primary-light); color: var(--primary-dark);
            padding: 6px 12px; border-radius: 8px; font-size: .8rem; font-weight: 500;
        }

        /* Content */
        .content-wrap { margin-left: var(--sidebar-w); min-height: 100vh; }
        .content { padding: 28px; }

        /* Cards */
        .card { border: 1px solid #eaedf0; border-radius: 12px; box-shadow: var(--card-shadow); transition: box-shadow .2s; }
        .card-header { background: #fff; border-bottom: 1px solid #f0f3f5; font-weight: 600; padding: 14px 18px; border-radius: 12px 12px 0 0 !important; }
        .card-body { padding: 18px; }

        /* Buttons */
        .btn { font-weight: 500; border-radius: 8px; transition: all .15s; }
        .btn-success { background: var(--primary); border-color: var(--primary); }
        .btn-success:hover { background: var(--primary-dark); border-color: var(--primary-dark); }

        /* Forms */
        .form-control, .form-select { border-radius: 8px; border-color: #dde2e7; transition: all .15s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(25,135,84,.15); }
        .form-label { font-weight: 500; font-size: .88rem; color: #4a5568; margin-bottom: 6px; }

        /* Tables */
        .table { font-size: .88rem; }
        .table thead th { background: #f8fafc; color: #4a5568; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .3px; border-bottom: 2px solid #e2e8f0; padding: 12px 14px; }
        .table tbody td { padding: 12px 14px; vertical-align: middle; }
        .table-hover tbody tr:hover { background: #f8fafc; }

        /* Mobile */
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1039; backdrop-filter: blur(2px); }
        .mobile-toggle { display: none; background: var(--primary); color: #fff; border: 0; padding: 8px 12px; border-radius: 8px; margin-right: 14px; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-backdrop.show { display: block; }
            .content-wrap { margin-left: 0; }
            .mobile-toggle { display: inline-flex; align-items: center; }
            .topbar { padding: 0 16px; }
            .content { padding: 18px; }
        }

        /* Animations */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeUp .3s ease-out; }

        /* Badge polish */
        .badge { font-weight: 500; padding: .4em .7em; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <div class="brand-text">
            <strong>SMK Lab Jakarta</strong>
            <small>Panel PPDB</small>
        </div>
    </div>

    <nav class="mt-2">
        <?php foreach ($grouped as $group => $items): ?>
            <div class="nav-group-label"><?= $group ?></div>
            <?php foreach ($items as $key => $info): ?>
                <a href="?page=<?= $key ?>" class="nav-link <?= $page === $key ? 'active' : '' ?>">
                    <i class="bi <?= $info['icon'] ?>"></i>
                    <span><?= $info['label'] ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer mt-4">
        <?php $is_super = !empty($_SESSION['is_super']); ?>
        <div class="user-card">
            <div class="user-avatar" style="<?= $is_super ? 'background: linear-gradient(135deg,#f7b733,#fc4a1a);' : '' ?>">
                <?= $is_super ? '<i class="bi bi-shield-fill-check"></i>' : strtoupper(substr($admin_name, 0, 1)) ?>
            </div>
            <div class="user-info flex-grow-1 overflow-hidden">
                <div class="name text-truncate"><?= $admin_name ?></div>
                <div class="role">
                    <?= $is_super ? '<span style="color:#ffc107;font-weight:600;"><i class="bi bi-star-fill"></i> Super Admin</span>' : 'Administrator' ?>
                </div>
            </div>
        </div>
        <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="content-wrap">
    <header class="topbar">
        <button class="mobile-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <div>
            <h1><i class="bi <?= $pages[$page]['icon'] ?? 'bi-grid' ?>"></i><?= $pages[$page]['label'] ?? 'Dashboard' ?></h1>
            <div class="breadcrumb-trail"><?= $pages[$page]['group'] ?? '' ?> / <?= $pages[$page]['label'] ?? 'Dashboard' ?></div>
        </div>
        <div class="topbar-right">
            <span class="badge-time d-none d-md-inline-flex align-items-center gap-2">
                <i class="bi bi-calendar3"></i> <?= date('d M Y') ?>
            </span>
        </div>
    </header>

    <main class="content fade-in">
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
        &copy; <?= date('Y') ?> PPDB SMK Laboratorium Jakarta · Panel Admin v2
    </footer>
    </main>
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
