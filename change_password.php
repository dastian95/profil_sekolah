<?php
require_once __DIR__ . '/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    header('Content-Type: application/json');

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => '<div class="alert alert-danger">All fields are required.</div>']);
        exit;
    } elseif ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => '<div class="alert alert-danger">New passwords do not match.</div>']);
        exit;
    } elseif (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => '<div class="alert alert-danger">Password must be at least 8 characters long.</div>']);
        exit;
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id_pendaftar = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current_password, $user['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id_pendaftar = ?");
            if ($update->execute([$new_hash, $_SESSION['user_id']])) {
                echo json_encode(['success' => true, 'message' => '<div class="alert alert-success">Password updated successfully.</div>']);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => '<div class="alert alert-danger">Failed to update password.</div>']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => '<div class="alert alert-danger">Current password is incorrect.</div>']);
            exit;
        }
    }
}
?>

<?php
// Detect if request is from AJAX navigation
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                 strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Only output full HTML structure for direct page loads
if (!$isAjaxRequest) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/dashboard.css" rel="stylesheet">
</head>
<body class="user-dashboard">
    <?php include 'sidebar.php'; ?>
<?php
} // End of full HTML structure - for AJAX requests, we skip to here
?>

    <div class="content">
        <div class="container-fluid">
            <button class="btn btn-primary d-md-none mb-3" id="sidebarToggle">
                <i class="bi bi-list"></i> Menu
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Change Password</li>
                </ol>
            </nav>
            <h2 class="mb-4 text-dark">Change Password</h2>
            <div id="alertContainer"></div>
            
            <div class="card shadow-sm border-0" style="max-width: 600px;">
                <div class="card-body">
                    <form id="changePasswordForm" action="change_password.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="current_password" class="form-control" required>
                                <button class="btn btn-outline-secondary" type="button" data-toggle-for="current_password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="new_password" class="form-control" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" data-toggle-for="new_password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            <div class="form-text">Minimum 8 characters.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" data-toggle-for="confirm_password">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Update Password</button>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
            document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
                
                fetch('change_password.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('alertContainer').innerHTML = data.message;
                    if(data.success) this.reset();
                })
                .catch(err => console.error(err))
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            });

            // Password toggle logic
            document.querySelectorAll('[data-toggle-for]').forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-toggle-for');
                    const passwordInput = document.getElementById(targetId);
                    if (!passwordInput) return;

                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    const icon = this.querySelector('i');
                    if (type === 'password') {
                        icon.classList.replace('bi-eye', 'bi-eye-slash');
                    } else {
                        icon.classList.replace('bi-eye-slash', 'bi-eye');
                    }
                });
            });
        </script>
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

    <!-- JS Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Global Dashboard Script -->
    <script src="assets/js/dashboard.js"></script>
    </div> <!-- End of .content div (for AJAX requests) -->
<?php
if (!$isAjaxRequest) {
?>
</body>
</html>
<?php
} // End of conditional HTML closing
?>