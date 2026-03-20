<?php
// Handle Password Update
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $msg = '<div class="alert alert-danger">All fields are required.</div>';
    } elseif ($new !== $confirm) {
        $msg = '<div class="alert alert-danger">New passwords do not match.</div>';
    } elseif (strlen($new) < 8) {
        $msg = '<div class="alert alert-danger">Password must be at least 8 characters.</div>';
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id_pendaftar = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current, $user['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id_pendaftar = ?");
            if ($stmt->execute([$hash, $_SESSION['user_id']])) {
                $msg = '<div class="alert alert-success">Password updated successfully.</div>';
            } else {
                $msg = '<div class="alert alert-danger">Failed to update password.</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">Current password is incorrect.</div>';
        }
    }
}
?>

<div class="card shadow-sm" style="max-width: 600px;">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-key me-2 text-primary"></i>Change Password</h5>
    </div>
    <div class="card-body">
        <?php echo $msg; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <div class="input-group">
                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)"><i class="bi bi-eye-slash"></i></button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="input-group">
                    <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)"><i class="bi bi-eye-slash"></i></button>
                </div>
                <div class="form-text">Minimum 8 characters.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)"><i class="bi bi-eye-slash"></i></button>
                </div>
            </div>
            <button type="submit" name="change_password" class="btn btn-primary"><i class="bi bi-save me-2"></i>Update Password</button>
        </form>
    </div>
</div>

<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    }
}
</script>