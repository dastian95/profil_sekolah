<?php
$msg = '';

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
        $conn->prepare("UPDATE admins SET password=? WHERE id=?")->execute([$newHash, $_SESSION['admin_id']]);

        $log = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
        $log->execute([$_SESSION['admin_id'], 'CHANGE_PASSWORD', 'Admin ganti password', $_SERVER['REMOTE_ADDR']]);
        $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Password berhasil diubah.</div>';
    }
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
