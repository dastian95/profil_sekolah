<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

// Hanya superadmin
if (empty($_SESSION['is_super'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$page = $_GET['page'] ?? 'super_home';
$admin_name = 'Super Admin';

$pages = [
    'super_home'      => ['label' => 'Dashboard',              'icon' => 'bi-speedometer2',         'group' => 'Utama'],
    'alur'            => ['label' => 'Alur Pendaftaran',       'icon' => 'bi-diagram-3-fill',       'group' => 'Pengaturan SPMB'],
    'pengaturan_ppdb' => ['label' => 'Pengaturan Pendaftaran', 'icon' => 'bi-sliders',              'group' => 'Pengaturan SPMB'],
    'meja'            => ['label' => 'Kelola Meja Antrian',    'icon' => 'bi-grid-3x2-gap-fill',    'group' => 'Pengaturan SPMB'],
    'antrian_display' => ['label' => 'Display Antrian',        'icon' => 'bi-display',              'group' => 'Pengaturan SPMB'],
    'kelola_admin'    => ['label' => 'Kelola Admin',           'icon' => 'bi-person-gear',          'group' => 'Manajemen'],
    'pendaftar'       => ['label' => 'Data Pendaftar',         'icon' => 'bi-people-fill',          'group' => 'Manajemen'],
    'antrian'         => ['label' => 'Meja Antrian',           'icon' => 'bi-list-ol',              'group' => 'Manajemen'],
    'ranking'         => ['label' => 'Ranking & Hasil',        'icon' => 'bi-trophy-fill',          'group' => 'Manajemen'],
    'announcements'   => ['label' => 'Pengumuman',             'icon' => 'bi-megaphone-fill',       'group' => 'Manajemen'],
    'site_content'    => ['label' => 'Konten Website',         'icon' => 'bi-layout-text-window-reverse', 'group' => 'Konten'],
    'audit_log'        => ['label' => 'Audit Log',              'icon' => 'bi-journal-text',         'group' => 'Sistem'],
    'system_info'      => ['label' => 'System Info',            'icon' => 'bi-info-square',          'group' => 'Sistem'],
    'backup'           => ['label' => 'Backup / Export',        'icon' => 'bi-cloud-download',       'group' => 'Sistem'],
    'database_manager' => ['label' => 'Database Manager',       'icon' => 'bi-database-fill',        'group' => 'Sistem'],
];

if (!array_key_exists($page, $pages)) $page = 'super_home';
$needs_chart = ($page === 'super_home');

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
    <title>Super Admin — SPMB SMKS Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-w: 270px;
            --primary: #7c3aed;
            --primary-dark: #5b21b6;
            --primary-light: #ede9fe;
            --sidebar-bg: linear-gradient(180deg, #1e1b4b 0%, #0f0c29 100%);
            --bg: #f4f6f9;
            --card-shadow: 0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.06);
        }
        * { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif; }
        body { background: var(--bg); color: #2d3748; }

        .sidebar {
            position: fixed; top: 0; left: 0; height: 100vh;
            width: var(--sidebar-w); background: var(--sidebar-bg);
            color: #fff; overflow-y: auto; z-index: 1040;
            transition: transform .25s ease;
            box-shadow: 2px 0 12px rgba(0,0,0,.15);
        }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 8px; }

        .sidebar-brand {
            padding: 20px 20px 18px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; gap: 12px;
        }
        .sidebar-brand .brand-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, #7c3aed, #c026d3);
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; box-shadow: 0 4px 14px rgba(124,58,237,.4);
        }
        .sidebar-brand .brand-text strong { font-size: .95rem; display: block; }
        .sidebar-brand .brand-text small { font-size: .68rem; opacity: .55; letter-spacing: .5px; text-transform: uppercase; }
        .super-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: linear-gradient(135deg, #f7b733, #fc4a1a);
            color: #fff; font-size: .65rem; font-weight: 700;
            padding: 2px 8px; border-radius: 20px; letter-spacing: .3px;
            text-transform: uppercase; margin-top: 3px;
        }

        .nav-group-label {
            font-size: .65rem; text-transform: uppercase; letter-spacing: 1.2px;
            opacity: .4; padding: 16px 24px 6px; font-weight: 700;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.65);
            padding: 10px 16px; margin: 2px 12px; border-radius: 8px;
            font-size: .88rem; font-weight: 500;
            display: flex; align-items: center; gap: 12px;
            transition: all .15s ease;
        }
        .sidebar .nav-link i { font-size: 1rem; width: 20px; text-align: center; }
        .sidebar .nav-link:hover { background: rgba(255,255,255,.1); color: #fff; }
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: #fff; box-shadow: 0 4px 12px rgba(124,58,237,.4);
        }

        .sidebar-footer {
            position: sticky; bottom: 0;
            padding: 16px;
            background: linear-gradient(180deg, transparent 0%, #0f0c29 35%);
            border-top: 1px solid rgba(255,255,255,.07);
        }
        .user-card {
            background: rgba(255,255,255,.06); border-radius: 10px;
            padding: 10px 12px; display: flex; align-items: center; gap: 10px; margin-bottom: 10px;
        }
        .user-avatar {
            width: 38px; height: 38px; border-radius: 50%;
            background: linear-gradient(135deg, #f7b733, #fc4a1a);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: #fff;
        }
        .user-info .name { font-size: .85rem; font-weight: 600; line-height: 1.2; }
        .user-info .role { font-size: .68rem; color: #fbbf24; font-weight: 600; }

        .btn-logout {
            width: 100%; background: rgba(220,53,69,.15);
            color: #ff8a95; border: 1px solid rgba(220,53,69,.25);
            padding: 8px 12px; border-radius: 8px; font-size: .85rem;
            font-weight: 500; transition: all .15s; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-logout:hover { background: #dc3545; color: #fff; border-color: #dc3545; }

        .topbar {
            position: sticky; top: 0; z-index: 1030;
            background: #fff; height: 64px;
            border-bottom: 1px solid #e8ecf0;
            display: flex; align-items: center; padding: 0 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,.03);
        }
        .topbar h1 { font-size: 1.1rem; font-weight: 600; margin: 0; color: #1e1b4b; display: flex; align-items: center; gap: 10px; }
        .topbar .breadcrumb-trail { color: #6c757d; font-size: .8rem; }
        .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }
        .topbar-right .badge-super {
            background: linear-gradient(135deg, #7c3aed, #a855f7);
            color: #fff; padding: 6px 14px; border-radius: 8px; font-size: .78rem; font-weight: 600;
        }

        .content-wrap { margin-left: var(--sidebar-w); min-height: 100vh; }
        .content { padding: 28px; }

        .card { border: 1px solid #eaedf0; border-radius: 12px; box-shadow: var(--card-shadow); will-change: auto; }
        .card-header { background: #fff; border-bottom: 1px solid #f0f3f5; font-weight: 600; padding: 14px 18px; border-radius: 12px 12px 0 0 !important; }
        .card-body { padding: 18px; }
        .btn { font-weight: 500; border-radius: 8px; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .form-control, .form-select { border-radius: 8px; border-color: #dde2e7; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(124,58,237,.15); }
        .form-label { font-weight: 500; font-size: .88rem; color: #4a5568; margin-bottom: 6px; }
        .table { font-size: .88rem; }
        .table thead th { background: #f8fafc; color: #4a5568; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .3px; border-bottom: 2px solid #e2e8f0; padding: 12px 14px; }
        .table tbody td { padding: 11px 14px; vertical-align: middle; }
        .badge { font-weight: 500; padding: .4em .7em; }

        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1039; }
        .mobile-toggle { display: none; background: var(--primary); color: #fff; border: 0; padding: 8px 12px; border-radius: 8px; margin-right: 14px; }
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-backdrop.show { display: block; }
            .content-wrap { margin-left: 0; }
            .mobile-toggle { display: inline-flex; align-items: center; }
            .topbar { padding: 0 16px; }
            .content { padding: 16px; }
        }
        @media (max-width: 575px) {
            .topbar { padding: 0 10px; min-height: 52px; }
            .topbar h2, .topbar .fs-5 { font-size: .88rem !important; }
            .content { padding: 10px !important; }
            .card { border-radius: 8px; }
            .stat-card .stat-value { font-size: 1.5rem; }
            .stat-card { padding: 14px; }
            .config-card { padding: 12px 12px; gap: 8px; }
            .config-card .cc-value { font-size: 1.2rem; }
            .table { font-size: .78rem; }
            .btn-sm { padding: .22rem .48rem; font-size: .76rem; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-shield-fill-check"></i></div>
        <div class="brand-text">
            <strong>SMKS Lab Jakarta</strong>
            <small>Panel SPMB</small>
            <div class="super-badge"><i class="bi bi-star-fill me-1"></i>Super Admin</div>
        </div>
    </div>

    <nav class="mt-2">
        <?php foreach ($grouped as $group => $items): ?>
            <div class="nav-group-label"><?= $group ?></div>
            <?php foreach ($items as $key => $info): ?>
                <?php if ($key === 'antrian_display'): ?>
                <a href="antrian_display.php" target="_blank" class="nav-link">
                    <i class="bi <?= $info['icon'] ?>"></i>
                    <span><?= $info['label'] ?> <i class="bi bi-box-arrow-up-right ms-1" style="font-size:.65rem;opacity:.5;"></i></span>
                </a>
                <?php else: ?>
                <a href="?page=<?= $key ?>" class="nav-link <?= $page === $key ? 'active' : '' ?>">
                    <i class="bi <?= $info['icon'] ?>"></i>
                    <span><?= $info['label'] ?></span>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer mt-4">
        <div class="user-card">
            <div class="user-avatar"><i class="bi bi-shield-fill-check"></i></div>
            <div class="user-info flex-grow-1">
                <div class="name">Super Admin</div>
                <div class="role"><i class="bi bi-star-fill me-1"></i>Full Access</div>
            </div>
        </div>
        <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar dari Super Admin?')"><i class="bi bi-box-arrow-right"></i> Logout</a>
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
            <span class="badge-super d-none d-md-inline-flex align-items-center gap-2">
                <i class="bi bi-shield-fill-check me-1"></i>Super Admin Mode
            </span>
            <span class="text-muted d-none d-md-inline small"><?= date('d M Y') ?></span>
        </div>
    </header>

    <main class="content fade-in">
    <?php
    $include_map = [
        'super_home'      => 'admin/super_home.php',
        'alur'            => 'admin/alur.php',
        'pengaturan_ppdb' => 'admin/pengaturan_ppdb.php',
        'meja'            => 'admin/meja.php',
        'antrian_display' => 'antrian_display.php',
        'kelola_admin'    => 'admin/kelola_admin.php',
        'pendaftar'       => 'admin/pendaftar.php',
        'antrian'         => 'admin/antrian.php',
        'ranking'         => 'admin/ranking.php',
        'announcements'   => 'admin/announcements.php',
        'site_content'    => 'admin/site_content.php',
        'audit_log'       => 'admin/audit_log.php',
        'system_info'     => 'admin/system_info.php',
        'backup'           => 'admin/backup.php',
        'database_manager' => 'admin/database_manager.php',
    ];
    $file = $include_map[$page] ?? 'admin/super_home.php';
    if (file_exists(__DIR__ . '/' . $file)) {
        include __DIR__ . '/' . $file;
    } else {
        echo '<div class="alert alert-warning">Halaman tidak ditemukan: ' . htmlspecialchars($file) . '</div>';
    }
    ?>
    <footer class="text-center text-muted small mt-5 pb-3">
        &copy; <?= date('Y') ?> SPMB SMK Laboratorium Jakarta · Super Admin Panel v2
    </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($needs_chart): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>
<script>
const sidebar  = document.querySelector('.sidebar');
const backdrop = document.getElementById('sidebarBackdrop');
const toggle   = document.getElementById('sidebarToggle');
if (toggle) {
    toggle.addEventListener('click', () => { sidebar.classList.toggle('show'); backdrop.classList.toggle('show'); });
    backdrop.addEventListener('click', () => { sidebar.classList.remove('show'); backdrop.classList.remove('show'); });
}
</script>
</body>
</html>
