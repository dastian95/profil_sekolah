<?php
// Mendapatkan nama file saat ini untuk menentukan menu aktif
$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Cek Status Kelulusan untuk Menu Daftar Ulang
$show_daftar_ulang = false;
if (isset($_SESSION['user_id']) && isset($conn)) {
if (!$is_admin && isset($_SESSION['user_id']) && isset($conn)) {
    $stmt_lulus = $conn->prepare("SELECT hasil FROM hasil_daftar WHERE id_pendaftar = ?");
    $stmt_lulus->execute([$_SESSION['user_id']]);
    $res_lulus = $stmt_lulus->fetch(PDO::FETCH_ASSOC);
    if ($res_lulus && $res_lulus['hasil'] == 'diterima') $show_daftar_ulang = true;
}
?>
<div class="sidebar d-flex flex-column flex-shrink-0 p-3">
    <div class="d-flex align-items-center justify-content-between sidebar-header mb-3">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none">
            <span class="fs-4">Student Panel</span>
            <span class="fs-4"><?php echo $is_admin ? 'Admin Panel' : 'Student Panel'; ?></span>
        </a>
        <button type="button" class="btn btn-link text-white d-none d-md-block p-0" id="desktopToggle"><i class="bi bi-chevron-left"></i></button>
    </div>
    <hr>
    <?php if (!$is_admin): ?>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" title="Dashboard" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="profile.php" class="nav-link <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>" title="Profile" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-person-circle me-2"></i> Profile
            </a>
        </li>
        <li>
            <a href="application.php" class="nav-link <?php echo ($current_page == 'application.php') ? 'active' : ''; ?>" title="My Application" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-file-earmark-text me-2"></i> My Application
            </a>
        </li>
        <li>
            <a href="jadwal_ujian.php" class="nav-link <?php echo ($current_page == 'jadwal_ujian.php') ? 'active' : ''; ?>" title="Jadwal Ujian" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-calendar-event me-2"></i> Jadwal Ujian
            </a>
        </li>
        <?php if ($show_daftar_ulang): ?>
        <li>
            <a href="daftar_ulang.php" class="nav-link <?php echo ($current_page == 'daftar_ulang.php') ? 'active' : ''; ?>" title="Daftar Ulang" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-check-circle-fill me-2"></i> Daftar Ulang
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="change_password.php" class="nav-link <?php echo ($current_page == 'change_password.php') ? 'active' : ''; ?>" title="Change Password" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-key me-2"></i> Change Password
            </a>
        </li>
        <li>
            <a href="#" class="nav-link" id="darkModeToggle" title="Dark Mode" data-bs-toggle="tooltip" data-bs-placement="right">
                <i class="bi bi-moon-stars me-2"></i> Dark Mode
            </a>
        </li>
    </ul>
    <?php else: ?>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="admin_dashboard.php" class="nav-link <?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Admin Dashboard
            </a>
        </li>
        <li>
            <a href="admin_manage_users.php" class="nav-link <?php echo ($current_page == 'admin_manage_users.php') ? 'active' : ''; ?>">
                <i class="bi bi-people me-2"></i> Manage Users
            </a>
        </li>
    </ul>
    <?php endif; ?>
    <hr>
    <div class="dropdown dropup">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">Sign out</a></li>
        </ul>
    </div>
</div>