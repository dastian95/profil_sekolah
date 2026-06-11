<?php
$msg = '';
if (!empty($_SESSION['flash_change_password'])) {
    $msg = $_SESSION['flash_change_password'];
    unset($_SESSION['flash_change_password']);
}
$is_super = !empty($_SESSION['is_super']);

if ($is_super) {
    // Superadmin tidak bisa ganti password lewat panel — datanya hardcoded di admin.php
    ?>
    <div class="card" style="max-width:560px">
        <div class="card-header fw-semibold">
            <i class="bi bi-shield-fill-check text-warning me-2"></i>Akun Superadmin
        </div>
        <div class="card-body">
            <div class="alert alert-warning mb-3">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Password Superadmin tidak bisa diubah lewat panel.</strong>
            </div>
            <p class="small text-muted mb-2">
                Akun Superadmin disimpan secara hardcoded di file <code>admin.php</code>
                (rahasia, tidak ada di database). Untuk mengubah password, edit langsung file
                tersebut dan ganti konstanta <code>SUPER_ADMIN_HASH</code> dengan hash baru.
            </p>
            <p class="small text-muted mb-0">
                Hash baru bisa dibuat lewat PHP CLI:<br>
                <code>php -r "echo password_hash('PasswordBaru', PASSWORD_DEFAULT);"</code>
            </p>
        </div>
    </div>
    <?php
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old  = $_POST['old_password']  ?? '';
    $new  = $_POST['new_password']  ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM admins WHERE id=?");
    $stmt->execute([$_SESSION['admin_id']]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($old, $hash)) {
        $msg = '<div class="alert alert-danger">Password lama salah.</div>';
    } elseif (strlen($new) < 6) {
        $msg = '<div class="alert alert-danger">Password baru minimal 6 karakter.</div>';
    } elseif ($new !== $conf) {
        $msg = '<div class="alert alert-danger">Konfirmasi password tidak cocok.</div>';
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        // password_plain di-NULL-kan: admin sudah ganti sendiri, plaintext lama tidak berlaku lagi
        $conn->prepare("UPDATE admins SET password=?, password_plain=NULL WHERE id=?")->execute([$newHash, $_SESSION['admin_id']]);

        $log = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
        $log->execute([$_SESSION['admin_id'], 'CHANGE_PASSWORD', 'Admin ganti password', $_SERVER['REMOTE_ADDR']]);
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Password berhasil diubah.</div>';
    }

    // PRG: redirect setelah POST agar refresh tidak mengulang aksi
    $_SESSION['flash_change_password'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: admin_dashboard.php?page=change_password');
    exit;
}
?>

<?= $msg ?>

<div class="card" style="max-width:480px">
    <div class="card-header fw-semibold">Ganti Password Admin</div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Password Lama</label>
                <input type="password" name="old_password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru <small class="text-muted">(min 6 karakter)</small></label>
                <input type="password" name="new_password" class="form-control" minlength="6" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-key me-1"></i>Ubah Password
            </button>
        </form>
    </div>
</div>
