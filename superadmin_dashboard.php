<?php
ob_start();
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

// Hanya superadmin
if (empty($_SESSION['is_super'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$page = $_GET['page'] ?? 'super_home';

// ── POST global: link/unlink pendaftar Fase 2 ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'f2_link') {
    $ant_id  = (int)($_POST['antrian_id'] ?? 0);
    $pend_id = (int)($_POST['pendaftar_id'] ?? 0);
    if ($ant_id) {
        try { $conn->prepare("UPDATE antrian SET pendaftar_id=? WHERE id=?")->execute([$pend_id ?: null, $ant_id]); } catch(Throwable) {}
    }
    header('Location: superadmin_dashboard.php?page=' . urlencode($page));
    exit;
}

// ── Sidebar Fase 2 global: muncul saat meja Fase 2 aktif ─────────────────────
$float_widget = null;
$fw_meja_id   = (int)($_SESSION['antrian_meja_id'] ?? 0);
$fw_meja_fase = (int)($_SESSION['antrian_meja_fase'] ?? 0);
if ($fw_meja_id && $fw_meja_fase === 2) {
    $today = date('Y-m-d');
    try {
        $fwm = $conn->prepare("SELECT * FROM meja WHERE id=?");
        $fwm->execute([$fw_meja_id]);
        $fw_meja = $fwm->fetch();
        if (!$fw_meja) {
            unset($_SESSION['antrian_meja_id'], $_SESSION['antrian_meja_fase']);
            throw new RuntimeException('meja_not_found');
        }
        $fwc = $conn->prepare("SELECT * FROM antrian WHERE tanggal=? AND meja_id=? AND fase=2 AND status='dipanggil' ORDER BY dipanggil_at DESC LIMIT 1");
        $fwc->execute([$today, $fw_meja_id]);
        $fw_current = $fwc->fetch() ?: null;
        $fw_pendaftar = null;
        if ($fw_current && !empty($fw_current['pendaftar_id'])) {
            $fwp = $conn->prepare("SELECT * FROM pendaftar WHERE id=?");
            $fwp->execute([$fw_current['pendaftar_id']]);
            $fw_pendaftar = $fwp->fetch() ?: null;
        }
        $fws = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=? AND meja_id=? AND fase=2 AND status='menunggu'");
        $fws->execute([$today, $fw_meja_id]);
        $fw_sisa = (int)$fws->fetchColumn();
        $float_widget = ['meja' => $fw_meja, 'current' => $fw_current, 'pendaftar' => $fw_pendaftar, 'sisa' => $fw_sisa];
    } catch(Throwable) {}
}
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
    'database_manager'    => ['label' => 'Database Manager',       'icon' => 'bi-database-fill',        'group' => 'Sistem'],
    'superadmin_profile'  => ['label' => 'Profil & Password',      'icon' => 'bi-person-circle',        'group' => 'Sistem'],
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
        'database_manager'   => 'admin/database_manager.php',
        'superadmin_profile' => 'admin/superadmin_profile.php',
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

<?php if (!$float_widget): ?>
<!-- Skeleton offcanvas — selalu ada di DOM agar tombol Fase 2 selalu bisa dibuka -->
<div class="offcanvas offcanvas-end" style="width:400px !important;" tabindex="-1" id="f2Sidebar">
    <div class="offcanvas-header" style="background:#ede9fe;border-bottom:1.5px solid #c4b5fd;">
        <h6 class="offcanvas-title mb-0 fw-bold" style="color:#6d28d9;"><i class="bi bi-grid-3x2-gap-fill me-2"></i>Panel Fase 2</h6>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3" style="background:#faf5ff;">
        <div class="text-center py-5 px-2">
            <i class="bi bi-info-circle d-block mb-2" style="font-size:2rem;color:#c4b5fd;"></i>
            <div class="small text-muted mb-3">Tidak ada meja Fase 2 aktif.<br>Pilih meja terlebih dahulu.</div>
            <a href="?page=antrian" class="btn btn-sm btn-outline-primary">Ke Halaman Antrian</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($float_widget): ?>
<!-- ══ SIDEBAR FASE 2 GLOBAL ══════════════════════════════════════════════════ -->
<style>
.f2-fab {
    position: fixed; bottom: 24px; right: 24px; z-index: 1045;
    background: linear-gradient(135deg,#7c3aed,#a855f7);
    color: #fff; border: 0; border-radius: 50px;
    padding: 12px 20px; font-weight: 700; font-size: .9rem;
    box-shadow: 0 4px 18px rgba(124,58,237,.4);
    display: flex; align-items: center; gap: 10px; cursor: pointer;
    transition: transform .2s, box-shadow .2s;
}
.f2-fab:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(124,58,237,.5); color: #fff; }
.f2-fab.linked { background: linear-gradient(135deg,#059669,#10b981); box-shadow: 0 4px 18px rgba(5,150,105,.35); }
.f2-fab.linked:hover { box-shadow: 0 8px 28px rgba(5,150,105,.45); }
.f2-fab .fab-nomor { background: rgba(255,255,255,.25); border-radius: 20px; padding: 2px 10px; font-size: .75rem; }
.f2-offcanvas { width: 400px !important; }
@media (max-width:480px) { .f2-offcanvas { width: 100vw !important; } }
.f2-nomor-big { font-size: 4rem; font-weight: 900; color: #7c3aed; line-height: 1; letter-spacing: -2px; }
.f2-berkas-row {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border: 1.5px solid #e5e7eb;
    border-radius: 10px; padding: 10px 12px; cursor: pointer;
    transition: border-color .15s, background .15s;
}
.f2-berkas-row.checked { border-color: #a855f7 !important; background: #f5f0ff !important; }
</style>

<!-- FAB Button -->
<button class="f2-fab <?= $float_widget['pendaftar'] ? 'linked' : '' ?>"
        data-bs-toggle="offcanvas" data-bs-target="#f2Sidebar">
    <i class="bi <?= $float_widget['pendaftar'] ? 'bi-person-check-fill' : 'bi-person-lines-fill' ?>"></i>
    <span>
        Fase 2 · Meja <?= $float_widget['meja']['nomor_meja'] ?>
        <?php if ($float_widget['meja']['nama']): ?>
        <span class="fw-normal opacity-75">— <?= htmlspecialchars($float_widget['meja']['nama']) ?></span>
        <?php endif; ?>
    </span>
    <?php if ($float_widget['current']): ?>
    <span class="fab-nomor">SSG<?= str_pad($float_widget['current']['nomor'], 3, '0', STR_PAD_LEFT) ?></span>
    <?php elseif ($float_widget['sisa'] > 0): ?>
    <span class="fab-nomor"><?= $float_widget['sisa'] ?> menunggu</span>
    <?php endif; ?>
</button>

<!-- Offcanvas Sidebar -->
<div class="offcanvas offcanvas-end f2-offcanvas" tabindex="-1" id="f2Sidebar">
    <div class="offcanvas-header" style="background:#ede9fe;border-bottom:1.5px solid #c4b5fd;">
        <div>
            <h6 class="offcanvas-title mb-0 fw-bold" style="color:#6d28d9;">
                <i class="bi bi-grid-3x2-gap-fill me-2"></i>Meja <?= $float_widget['meja']['nomor_meja'] ?> — Fase 2
                <?php if ($float_widget['meja']['nama']): ?>
                <small class="fw-normal text-muted"><?= htmlspecialchars($float_widget['meja']['nama']) ?></small>
                <?php endif; ?>
            </h6>
            <div class="text-muted" style="font-size:.72rem;">
                <?php if ($float_widget['current']): ?>
                Melayani SSG<?= str_pad($float_widget['current']['nomor'], 3, '0', STR_PAD_LEFT) ?>
                <?php elseif ($float_widget['sisa'] > 0): ?>
                <?= $float_widget['sisa'] ?> pendaftar menunggu
                <?php else: ?>
                Antrian kosong
                <?php endif; ?>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0" style="background:#faf5ff;overflow-y:auto;">

    <?php if ($float_widget['current']): ?>
    <?php $fw_cur = $float_widget['current']; $fw_pend = $float_widget['pendaftar']; ?>

    <!-- Nomor aktif -->
    <div class="text-center py-4 px-3" style="background:#fff;border-bottom:1px solid #e9d5ff;">
        <div class="small text-muted fw-semibold text-uppercase mb-1" style="letter-spacing:.5px;color:#7c3aed;">Sedang Dilayani</div>
        <div class="f2-nomor-big">SSG<?= str_pad($fw_cur['nomor'], 3, '0', STR_PAD_LEFT) ?></div>
        <div class="small text-muted mt-1">Dipanggil <?= date('H:i', strtotime($fw_cur['dipanggil_at'])) ?></div>
    </div>

    <div class="px-3 py-3">
    <?php if ($fw_pend): ?>
    <!-- Pendaftar sudah terhubung -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="fw-bold"><?= htmlspecialchars($fw_pend['nama']) ?></div>
            <?php if ($fw_pend['nisn']): ?><div class="text-muted small"><i class="bi bi-hash me-1"></i>NISN: <?= htmlspecialchars($fw_pend['nisn']) ?></div><?php endif; ?>
            <div class="text-muted small"><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($fw_pend['jurusan']) ?></div>
            <?php $sb=['diproses'=>'bg-warning text-dark','lengkap'=>'bg-info text-dark','gugur'=>'bg-danger','terima'=>'bg-success'];
                  $sl=['diproses'=>'Diproses','lengkap'=>'Lengkap','gugur'=>'Gugur','terima'=>'Terima']; ?>
            <div class="mt-2"><span class="badge <?= $sb[$fw_pend['status']] ?? 'bg-secondary' ?>"><?= $sl[$fw_pend['status']] ?? $fw_pend['status'] ?></span></div>
        </div>
    </div>

    <!-- Berkas checklist (localStorage) -->
    <div class="small fw-semibold text-uppercase mb-2" style="color:#7c3aed;letter-spacing:.3px;">
        <i class="bi bi-card-checklist me-1"></i>Cek Berkas
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
    <?php foreach ([
        'kk'         => ['Kartu Keluarga (KK)', 'bi-house-fill',        '#dbeafe','#1d4ed8'],
        'tka'        => ['Hasil Tes TKA',        'bi-file-earmark-text','#d1fae5','#065f46'],
        'akta'       => ['Akta Kelahiran',       'bi-calendar-event',   '#fef3c7','#92400e'],
        'buta_warna' => ['Tes Buta Warna',       'bi-eye',              '#fce7f3','#9d174d'],
    ] as $fwk => [$fwl, $fwi, $fwbg, $fwco]): ?>
    <label class="f2-berkas-row" id="f2BerkasRow_<?= $fwk ?>"
           onclick="f2ToggleBerkas(<?= (int)$fw_cur['id'] ?>, '<?= $fwk ?>', this)">
        <div style="width:32px;height:32px;border-radius:8px;background:<?= $fwbg ?>;color:<?= $fwco ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi <?= $fwi ?>"></i>
        </div>
        <span class="small flex-grow-1"><?= $fwl ?></span>
        <i class="bi bi-circle" id="f2BerkasIco_<?= $fwk ?>" style="color:#d1d5db;"></i>
    </label>
    <?php endforeach; ?>
    </div>

    <div class="d-grid gap-2 mb-3">
        <a href="?page=pendaftar" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit Data Pendaftar
        </a>
        <form method="POST" class="d-grid">
            <input type="hidden" name="action" value="f2_link">
            <input type="hidden" name="antrian_id" value="<?= $fw_cur['id'] ?>">
            <input type="hidden" name="pendaftar_id" value="0">
            <button type="submit" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left-right me-1"></i>Ganti Pendaftar
            </button>
        </form>
    </div>

    <?php else: ?>
    <!-- Belum terhubung: tombol daftar baru -->
    <div class="text-center py-3 px-2">
        <div style="width:60px;height:60px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <i class="bi bi-person-plus-fill" style="font-size:1.8rem;color:#7c3aed;"></i>
        </div>
        <div class="fw-semibold mb-1">Belum Ada Data</div>
        <div class="small text-muted mb-4">Pendaftar ini belum diinput datanya.<br>Gunakan halaman Data Pendaftar untuk mendaftarkannya.</div>
        <a href="superadmin_dashboard.php?page=pendaftar"
           class="btn btn-sm fw-semibold text-white px-4"
           style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
            <i class="bi bi-plus-lg me-1"></i>Daftarkan Sekarang
        </a>
    </div>
    <?php endif; ?>

    <!-- Tombol aksi utama -->
    <div class="d-grid gap-2 pt-3 border-top" style="border-color:#e9d5ff !important;">
        <form method="POST" action="superadmin_dashboard.php?page=antrian">
            <input type="hidden" name="action" value="selesai">
            <input type="hidden" name="antrian_id" value="<?= $fw_cur['id'] ?>">
            <input type="hidden" name="nomor" value="<?= $fw_cur['nomor'] ?>">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($page) ?>">
            <button type="submit" class="btn btn-sm w-100 fw-semibold text-white"
                    style="background:linear-gradient(135deg,#7c3aed,#a855f7);"
                    onclick="f2ClearBerkas(<?= $fw_cur['id'] ?>); return confirm('Selesai? Surat Tanda Daftar diterbitkan untuk nomor <?= $fw_cur['nomor'] ?>.')">
                <i class="bi bi-file-earmark-check me-1"></i>Selesai &amp; Terbitkan Surat
            </button>
        </form>
        <form method="POST" action="superadmin_dashboard.php?page=antrian">
            <input type="hidden" name="action" value="skip">
            <input type="hidden" name="antrian_id" value="<?= $fw_cur['id'] ?>">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($page) ?>">
            <button type="submit" class="btn btn-sm btn-outline-warning w-100"
                    onclick="return confirm('Lewati nomor <?= $fw_cur['nomor'] ?>? (Tidak hadir)')">
                <i class="bi bi-forward-fill me-1"></i>Skip (Tidak Hadir)
            </button>
        </form>
    </div>
    </div>

    <?php elseif ($float_widget['sisa'] > 0): ?>
    <!-- Belum ada nomor aktif, ada yang menunggu -->
    <div class="text-center py-5 px-3">
        <i class="bi bi-hourglass-split d-block mb-3" style="font-size:2.5rem;color:#a855f7;opacity:.6;"></i>
        <div class="fw-semibold mb-1"><?= $float_widget['sisa'] ?> pendaftar menunggu</div>
        <div class="small text-muted mb-4">Belum ada nomor yang dipanggil</div>
        <form method="POST" action="superadmin_dashboard.php?page=antrian">
            <input type="hidden" name="action" value="mulai">
            <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($page) ?>">
            <button type="submit" class="btn fw-semibold text-white px-4"
                    style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
                <i class="bi bi-play-fill me-1"></i>Panggil Nomor Berikutnya
            </button>
        </form>
    </div>

    <?php else: ?>
    <!-- Antrian kosong -->
    <div class="text-center py-5 px-3">
        <i class="bi bi-inbox d-block mb-3" style="font-size:2.5rem;color:#c4b5fd;"></i>
        <div class="small text-muted">Belum ada pendaftar di Fase 2.</div>
        <div class="small text-muted">Tunggu hasil Cek Berkas dari Fase 1.</div>
    </div>
    <?php endif; ?>

    <div class="border-top text-center py-2 mt-auto" style="background:#fff;">
        <a href="superadmin_dashboard.php?page=antrian" class="small text-decoration-none" style="color:#7c3aed;">
            <i class="bi bi-grid-3x2-gap-fill me-1"></i>Buka Halaman Meja Antrian
        </a>
    </div>
    </div>
</div>

<script>
function f2BerkasKey(id) { return 'berkas_antrian_' + id; }
function f2LoadBerkas(id) { try { return JSON.parse(localStorage.getItem(f2BerkasKey(id)) || '{}'); } catch { return {}; } }
function f2ToggleBerkas(antrianId, key, rowEl) {
    const data = f2LoadBerkas(antrianId);
    data[key] = !data[key];
    localStorage.setItem(f2BerkasKey(antrianId), JSON.stringify(data));
    f2ApplyBerkasRow(key, data[key]);
}
function f2ApplyBerkasRow(key, checked) {
    const row = document.getElementById('f2BerkasRow_' + key);
    const ico = document.getElementById('f2BerkasIco_' + key);
    if (row) row.classList.toggle('checked', !!checked);
    if (ico) { ico.className = checked ? 'bi bi-check-circle-fill text-success' : 'bi bi-circle'; ico.style.color = checked ? '' : '#d1d5db'; }
}
function f2ClearBerkas(id) { localStorage.removeItem(f2BerkasKey(id)); }

<?php if ($float_widget['current'] && $float_widget['pendaftar']): ?>
document.addEventListener('DOMContentLoaded', () => {
    const data = f2LoadBerkas(<?= $float_widget['current']['id'] ?>);
    ['kk','tka','akta','buta_warna'].forEach(k => f2ApplyBerkasRow(k, !!data[k]));
});
<?php endif; ?>

<?php if ($float_widget['current'] && !$float_widget['pendaftar']): ?>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('f2Sidebar');
    if (el) new bootstrap.Offcanvas(el).show();
});
<?php endif; ?>
</script>
<?php endif; ?>

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
