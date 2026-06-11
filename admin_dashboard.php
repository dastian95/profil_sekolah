<?php
ob_start();
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

// Auth check
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin.php');
    exit;
}
// Superadmin punya dashboard sendiri
if (!empty($_SESSION['is_super'])) {
    header('Location: superadmin_dashboard.php' . (isset($_GET['page']) ? '?page='.$_GET['page'] : ''));
    exit;
}

$page = $_GET['page'] ?? 'home';
$admin_name = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin');
$admin_id   = (int)($_SESSION['admin_id'] ?? 0);

// Semua halaman yang tersedia untuk admin biasa
$all_pages = [
    'home'           => ['label' => 'Dashboard',            'icon' => 'bi-speedometer2',         'group' => 'Utama'],
    'pendaftar'      => ['label' => 'Data Pendaftar',       'icon' => 'bi-people-fill',          'group' => 'Manajemen'],
    'antrian'          => ['label' => 'Meja Antrian',         'icon' => 'bi-grid-3x2-gap-fill',    'group' => 'Manajemen'],
    'antrian_display'  => ['label' => 'Display Antrian',     'icon' => 'bi-display',              'group' => 'Manajemen'],
    'ranking'        => ['label' => 'Ranking & Hasil',      'icon' => 'bi-trophy-fill',          'group' => 'Manajemen'],
    'announcements'  => ['label' => 'Pengumuman',           'icon' => 'bi-megaphone-fill',       'group' => 'Manajemen'],
    'pengaturan_ppdb'=> ['label' => 'Pengaturan Pendaftaran','icon' => 'bi-sliders',              'group' => 'Konfigurasi'],
    'meja'           => ['label' => 'Kelola Meja',          'icon' => 'bi-layout-split',         'group' => 'Konfigurasi'],
    'backup'         => ['label' => 'Backup / Export',      'icon' => 'bi-cloud-download',       'group' => 'Sistem'],
    'change_password'=> ['label' => 'Ganti Password',       'icon' => 'bi-shield-lock',          'group' => 'Sistem'],
];

// Mapping kode tahap → halaman yang boleh diakses
$tahap_pages = [
    'input_data'      => ['pendaftar', 'antrian'],
    'proses_berkas'   => ['antrian', 'pendaftar'],
    'ranking'         => ['ranking', 'pendaftar'],
    'pengumuman'      => ['announcements', 'pengaturan_ppdb'],
    'kelola_meja'     => ['meja', 'antrian', 'antrian_display'],
    'kelola_gelombang'=> ['pengaturan_ppdb'],
];

// Filter halaman berdasarkan tahapan yang di-assign ke admin ini
$allowed_pages = ['home', 'change_password']; // selalu ada
try {
    $tStmt = $conn->prepare("SELECT t.kode, t.halaman_key FROM tahapan t
        JOIN admin_tahapan at ON at.tahap_id = t.id
        WHERE at.admin_id = ? AND t.is_active = 1");
    $tStmt->execute([$admin_id]);
    foreach ($tStmt as $row) {
        // Prioritaskan mapping dari kode; fallback ke halaman_key jika kode tidak dikenal
        $pages_for_tahap = $tahap_pages[$row['kode']] ?? [$row['halaman_key']];
        foreach ($pages_for_tahap as $p) {
            if ($p && !in_array($p, $allowed_pages)) {
                $allowed_pages[] = $p;
            }
        }
    }
} catch(Throwable) {
    // Tabel tahapan belum ada (sebelum migration) → tampilkan semua
    $allowed_pages = array_keys($all_pages);
}

// Display antrian selalu muncul jika admin punya akses antrian
if (in_array('antrian', $allowed_pages)) {
    $allowed_pages[] = 'antrian_display';
}

// Kalau admin belum punya tahapan → tampilkan semua (supaya tidak blank)
if (count($allowed_pages) <= 2) {
    $allowed_pages = array_keys($all_pages);
}

// Direct nav: admin dengan tepat 1 tool langsung diarahkan ke halaman itu
$extra_pages = array_diff($allowed_pages, ['home', 'change_password']);
if ($page === 'home' && count($extra_pages) === 1) {
    $only_page = reset($extra_pages);
    header('Location: admin_dashboard.php?page=' . urlencode($only_page));
    exit;
}

$pages       = array_filter($all_pages, fn($k) => in_array($k, $allowed_pages), ARRAY_FILTER_USE_KEY);
$needs_chart = ($page === 'home');

// ── POST global: link/unlink pendaftar Fase 2 dari sidebar manapun ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'f2_link') {
    $ant_id  = (int)($_POST['antrian_id'] ?? 0);
    $pend_id = (int)($_POST['pendaftar_id'] ?? 0);
    if ($ant_id) {
        try { $conn->prepare("UPDATE antrian SET pendaftar_id=? WHERE id=?")->execute([$pend_id ?: null, $ant_id]); } catch(Throwable) {}
    }
    header('Location: admin_dashboard.php?page=' . urlencode($page));
    exit;
}

// ── Sidebar Fase 2 global: muncul di semua halaman saat meja Fase 2 aktif ──
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
            // Meja sudah tidak ada — bersihkan session agar tidak loop
            unset($_SESSION['antrian_meja_id'], $_SESSION['antrian_meja_fase']);
            throw new RuntimeException('meja_not_found');
        }

        $fwc = $conn->prepare("SELECT * FROM antrian
            WHERE tanggal=? AND meja_id=? AND fase=2 AND status='dipanggil'
            ORDER BY dipanggil_at DESC LIMIT 1");
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

// Redirect ke home jika halaman yang diminta tidak diizinkan
if (!array_key_exists($page, $pages)) {
    $page = 'home';
}

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
    <title>Admin Panel — SPMB SMKS Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
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
        * { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif; }
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
            background: rgba(255,255,255,.1); color: #fff;
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
        .card { border: 1px solid #eaedf0; border-radius: 12px; box-shadow: var(--card-shadow); }
        .card-header { background: #fff; border-bottom: 1px solid #f0f3f5; font-weight: 600; padding: 14px 18px; border-radius: 12px 12px 0 0 !important; }
        .card-body { padding: 18px; }

        /* Buttons */
        .btn { font-weight: 500; border-radius: 8px; }
        .btn-success { background: var(--primary); border-color: var(--primary); }
        .btn-success:hover { background: var(--primary-dark); border-color: var(--primary-dark); }

        /* Forms */
        .form-control, .form-select { border-radius: 8px; border-color: #dde2e7; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(25,135,84,.15); }
        .form-label { font-weight: 500; font-size: .88rem; color: #4a5568; margin-bottom: 6px; }

        /* Tables */
        .table { font-size: .88rem; }
        .table thead th { background: #f8fafc; color: #4a5568; font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .3px; border-bottom: 2px solid #e2e8f0; padding: 12px 14px; }
        .table tbody td { padding: 12px 14px; vertical-align: middle; }
        .table-hover tbody tr:hover { background: #f8fafc; }

        /* Mobile */
        .sidebar-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1039; }
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
            .topbar h1, .topbar .fs-5 { font-size: .88rem !important; }
            .content { padding: 10px !important; }
            .card { border-radius: 8px; }
            .table { font-size: .78rem; }
            .btn-sm { padding: .22rem .48rem; font-size: .76rem; }
            /* Tabel aksi — tampilkan tombol secara vertikal */
            .table td.text-end { white-space: normal; display: flex; flex-wrap: wrap; gap: 4px; justify-content: flex-end; }
        }

        /* Badge polish */
        .badge { font-weight: 500; padding: .4em .7em; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
        <div class="brand-text">
            <strong>SMKS Lab Jakarta</strong>
            <small>Panel SPMB</small>
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
        <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar dari panel admin?')"><i class="bi bi-box-arrow-right"></i> Logout</a>
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
        'antrian'         => 'admin/antrian.php',
        'ranking'         => 'admin/ranking.php',
        'announcements'   => 'admin/announcements.php',
        'pengaturan_ppdb' => 'admin/pengaturan_ppdb.php',
        'backup'          => 'admin/backup.php',
        'change_password' => 'admin/change_password.php',
        'kelola_admin'    => 'admin/kelola_admin.php',
        'audit_log'       => 'admin/audit_log.php',
        'system_info'     => 'admin/system_info.php',
    ];
    $file = $include_map[$page] ?? 'admin/home.php';
    if (file_exists(__DIR__ . '/' . $file)) {
        include __DIR__ . '/' . $file;
    } else {
        echo '<div class="alert alert-warning">Halaman tidak ditemukan.</div>';
    }
    ?>

    <footer class="text-center text-muted small mt-5 pb-3">
        &copy; <?= date('Y') ?> SPMB SMK Laboratorium Jakarta · Panel Admin v2
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
        <a href="admin_dashboard.php?page=pendaftar"
           class="btn btn-sm fw-semibold text-white px-4"
           style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
            <i class="bi bi-plus-lg me-1"></i>Daftarkan Sekarang
        </a>
    </div>
    <?php endif; ?>

    <!-- Tombol aksi utama -->
    <div class="d-grid gap-2 pt-3 border-top" style="border-color:#e9d5ff !important;">
        <form method="POST" action="admin_dashboard.php?page=antrian">
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
        <form method="POST" action="admin_dashboard.php?page=antrian">
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
        <form method="POST" action="admin_dashboard.php?page=antrian">
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
        <a href="admin_dashboard.php?page=antrian" class="small text-decoration-none" style="color:#7c3aed;">
            <i class="bi bi-grid-3x2-gap-fill me-1"></i>Buka Halaman Meja Antrian
        </a>
    </div>
    </div>
</div>

<script>
// Berkas checklist di sidebar global
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
// Auto-buka sidebar saat ada nomor aktif tapi belum ada data pendaftar
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
