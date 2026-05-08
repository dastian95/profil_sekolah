<?php
require_once __DIR__ . '/conn.php';

// =========================================================
//  SUPERADMIN — HARDCODED (RAHASIA, TIDAK ADA DI DATABASE)
//  Username : superadmin
//  Password : SuperRahasia2026!
//  Akun ini hanya bisa diubah lewat code (file ini).
// =========================================================
const SUPER_ADMIN_USERNAME = 'superadmin';
const SUPER_ADMIN_HASH     = '$2y$12$rv40eZ5YsYmGZ4W5O44g4OxDkl99fcmcB9JVbKRta/esl2wiKw96S';
const SUPER_ADMIN_NAME     = 'Super Admin';

// Secret link check — redirect ke index jika key salah/tidak ada
$key = $_GET['k'] ?? '';
if ($key !== $_ENV['ADMIN_KEY']) {
    header('Location: index.php');
    exit;
}

// Sudah login → langsung ke dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // 1. Cek SUPERADMIN dulu (hardcoded, tidak dari database)
        if ($username === SUPER_ADMIN_USERNAME && password_verify($password, SUPER_ADMIN_HASH)) {
            $_SESSION['admin_id']   = 0;                  // 0 = superadmin (tidak ada ID di tabel admins)
            $_SESSION['admin_name'] = SUPER_ADMIN_NAME;
            $_SESSION['is_super']   = true;

            // Log login superadmin (admin_id = NULL karena tidak ada di tabel admins)
            try {
                $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', ?)");
                $log->execute([$_SERVER['REMOTE_ADDR']]);
            } catch (Throwable $e) { /* abaikan jika FK strict */ }

            header('Location: admin_dashboard.php');
            exit;
        }

        // 2. Cek admin biasa di database (cari pakai username)
        $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['is_super']   = false;

            $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'LOGIN', 'Admin login berhasil', ?)");
            $log->execute([$admin['id'], $_SERVER['REMOTE_ADDR']]);

            header('Location: admin_dashboard.php');
            exit;
        }
        $error = 'Username atau password salah.';
    } else {
        $error = 'Semua field wajib diisi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — PPDB SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }
        .login-card {
            max-width: 560px;
            margin: 80px auto;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
        }
        .login-header {
            background: #198754;
            color: #fff;
            border-radius: 12px 12px 0 0;
            padding: 32px 40px 24px;
        }
        .login-body { padding: 32px 40px; }
        .login-card .form-control, .login-card .btn { padding: .65rem .9rem; font-size: 1rem; }
    </style>
</head>
<body>
<div class="login-card bg-white">
    <div class="login-header">
        <h4 class="mb-1"><i class="bi bi-shield-lock me-2"></i>Admin Panel</h4>
        <small class="opacity-75">PPDB SMK Laboratorium Jakarta</small>
    </div>
    <div class="login-body">
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="k" value="<?= htmlspecialchars($key) ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="masukkan username" autocomplete="username" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="pwd" class="form-control" placeholder="Password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()">
                        <i class="bi bi-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-success w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
            </button>
        </form>
        <div class="mt-3 text-center">
            <a href="index.php" class="text-muted small">← Kembali ke halaman utama</a>
        </div>
    </div>
</div>
<script>
function togglePwd() {
    const p = document.getElementById('pwd');
    const i = document.getElementById('eye-icon');
    if (p.type === 'password') { p.type = 'text'; i.className = 'bi bi-eye-slash'; }
    else { p.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
