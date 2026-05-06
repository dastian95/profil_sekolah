<?php
require_once __DIR__ . '/conn.php';

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
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];

            // Log login
            $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (?, 'LOGIN', 'Admin login berhasil', ?)");
            $log->execute([$admin['id'], $_SERVER['REMOTE_ADDR']]);

            header('Location: admin_dashboard.php');
            exit;
        }
        $error = 'Email atau password salah.';
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
            max-width: 420px;
            margin: 100px auto;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
        }
        .login-header {
            background: #198754;
            color: #fff;
            border-radius: 12px 12px 0 0;
            padding: 28px 32px 20px;
        }
    </style>
</head>
<body>
<div class="login-card bg-white">
    <div class="login-header">
        <h4 class="mb-1"><i class="bi bi-shield-lock me-2"></i>Admin Panel</h4>
        <small class="opacity-75">PPDB SMK Laboratorium Jakarta</small>
    </div>
    <div class="p-4">
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="k" value="<?= htmlspecialchars($key) ?>">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@smklab.sch.id" required autofocus>
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
