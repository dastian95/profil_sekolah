<?php
require_once __DIR__ . '/conn.php';

// Rate limit config
const MAX_FAIL_ATTEMPTS = 999;
const LOCKOUT_MINUTES   = 10;

// Secret link check — redirect ke index jika key salah/tidak ada
$key = $_GET['k'] ?? '';
if ($key !== $_ENV['ADMIN_KEY']) {
    header('Location: index.php');
    exit;
}

// Sudah login → langsung ke dashboard yang sesuai
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php'));
    exit;
}

$error = '';
$locked = false;
$lockout_remaining = 0;
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Cek rate limit: hitung failed login dari IP ini dalam X menit terakhir
$check = $conn->prepare("SELECT COUNT(*) FROM admin_logs
    WHERE action='LOGIN_FAILED' AND ip_address=?
    AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)");
$check->execute([$ip, LOCKOUT_MINUTES]);
$fail_count = (int)$check->fetchColumn();

if ($fail_count >= MAX_FAIL_ATTEMPTS) {
    $locked = true;
    $last_fail = $conn->prepare("SELECT MAX(created_at) FROM admin_logs
        WHERE action='LOGIN_FAILED' AND ip_address=?");
    $last_fail->execute([$ip]);
    $last = $last_fail->fetchColumn();
    if ($last) {
        $elapsed = time() - strtotime($last);
        $lockout_remaining = max(0, LOCKOUT_MINUTES * 60 - $elapsed);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $login_ok = false;

        // 1. Cek SUPERADMIN — dari tabel superadmin_accounts (multi-akun)
        $super_checked = false;
        try {
            $sa = $conn->prepare("SELECT * FROM superadmin_accounts WHERE username=? AND is_active=1 LIMIT 1");
            $sa->execute([$username]);
            $super_row = $sa->fetch();
            $super_checked = true;
            if ($super_row && password_verify($password, $super_row['password_hash'])) {
                $_SESSION['admin_id']     = 0;
                $_SESSION['admin_name']   = $super_row['nama'] ?: $super_row['username'];
                $_SESSION['is_super']     = true;
                $_SESSION['super_acc_id'] = $super_row['id'];
                try {
                    $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'LOGIN_SUPER', ?, ?)")
                         ->execute(['Login: ' . $super_row['username'], $ip]);
                } catch (Throwable) {}
                header('Location: superadmin_dashboard.php');
                exit;
            }
        } catch (Throwable) {}

        // Fallback: cek superadmin_config (sebelum tabel superadmin_accounts ada)
        if (!$super_checked) {
            $super_hash = SUPER_ADMIN_HASH;
            try {
                $sh = $conn->query("SELECT password_hash FROM superadmin_config WHERE id=1")->fetchColumn();
                if ($sh) $super_hash = $sh;
            } catch (Throwable) {}
            if ($username === SUPER_ADMIN_USERNAME && password_verify($password, $super_hash)) {
                $_SESSION['admin_id']     = 0;
                $_SESSION['admin_name']   = SUPER_ADMIN_NAME;
                $_SESSION['is_super']     = true;
                $_SESSION['super_acc_id'] = 1; // jalur legacy hanya cocok untuk akun utama
                try {
                    $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', ?)")
                         ->execute([$ip]);
                } catch (Throwable) {}
                header('Location: superadmin_dashboard.php');
                exit;
            }
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
            $log->execute([$admin['id'], $ip]);

            header('Location: admin_dashboard.php');
            exit;
        }

        // Login gagal → catat ke admin_logs
        try {
            $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'LOGIN_FAILED', ?, ?)");
            $log->execute(["Login gagal untuk username: " . substr($username, 0, 50), $ip]);
        } catch (Throwable $e) {}

        $remaining = MAX_FAIL_ATTEMPTS - $fail_count - 1;
        if ($remaining > 0) {
            $error = "Username atau password salah. Sisa percobaan: <strong>$remaining</strong>";
        } else {
            $error = "Username atau password salah. Akun Anda terkunci.";
            $locked = true;
            $lockout_remaining = LOCKOUT_MINUTES * 60;
        }
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
    <link rel="icon" href="favicon.ico?v=2" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png?v=2">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png?v=2">
    <title>Admin Login — SPMB SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: linear-gradient(135deg, #1a3c34 0%, #198754 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .login-card {
            max-width: 460px; width: 100%;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            overflow: hidden;
            background: #fff;
        }
        .login-header {
            background: linear-gradient(135deg, #198754 0%, #146c43 100%);
            color: #fff;
            padding: 36px 40px 28px;
            text-align: center;
        }
        .login-header .brand-icon {
            width: 64px; height: 64px; background: rgba(255,255,255,.18);
            border-radius: 16px; display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.8rem; margin-bottom: 14px;
        }
        .login-body { padding: 32px 40px 28px; }
        .form-control { padding: .7rem .9rem; border-radius: 10px; border-color: #dde2e7; }
        .form-control:focus { border-color: #198754; box-shadow: 0 0 0 3px rgba(25,135,84,.15); }
        .btn { padding: .7rem 1rem; border-radius: 10px; font-weight: 600; }
        .btn-success { background: linear-gradient(135deg, #198754, #146c43); border: 0; }
        .btn-success:hover { background: linear-gradient(135deg, #146c43, #0f5132); transform: translateY(-1px); }
        .form-label { font-weight: 500; font-size: .88rem; color: #4a5568; margin-bottom: 6px; }
        .input-group .form-control:focus { z-index: 3; }
        .caps-warning {
            display: none; margin-top: 6px;
            padding: 6px 10px; border-radius: 6px;
            background: #fff3cd; color: #664d03;
            font-size: .8rem;
        }
        .caps-warning.show { display: block; animation: shake .3s; }
        @keyframes shake { 0%,100%{transform:translateX(0);} 25%{transform:translateX(-3px);} 75%{transform:translateX(3px);} }
        @media (max-width: 480px) {
            body { padding: 12px; }
            .login-header { padding: 22px 20px 16px; }
            .login-body { padding: 20px 20px 18px; }
        }
        .lockout-banner {
            background: linear-gradient(135deg,#dc3545,#9b1c2b); color: #fff;
            padding: 16px; border-radius: 10px; margin-bottom: 20px;
            text-align: center;
        }
        .lockout-banner .countdown { font-size: 1.5rem; font-weight: 700; margin-top: 6px; }
        .btn:disabled { opacity: .6; cursor: not-allowed; }
        .login-footer { text-align: center; padding: 14px; background: #f8f9fa; color: #6c757d; font-size: .8rem; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="brand-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h4 class="mb-1 fw-bold">Admin Panel</h4>
        <small class="opacity-75">SPMB SMKS Laboratorium Jakarta</small>
    </div>
    <div class="login-body">
        <?php if ($locked): ?>
            <div class="lockout-banner">
                <i class="bi bi-lock-fill fs-2"></i>
                <div class="mt-2 fw-semibold">Terlalu Banyak Percobaan Gagal</div>
                <small>Akses dari IP Anda dikunci sementara.</small>
                <div class="countdown" id="countdown"><?= gmdate('i:s', $lockout_remaining) ?></div>
                <small>menit:detik tersisa</small>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger py-2 small">
                <i class="bi bi-exclamation-circle me-1"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" <?= $locked ? 'style="opacity:.5;pointer-events:none;"' : '' ?>>
            <input type="hidden" name="k" value="<?= htmlspecialchars($key) ?>">
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-person me-1"></i>Username
                </label>
                <input type="text" name="username" id="username" class="form-control"
                       placeholder="masukkan username" autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       <?= $locked ? 'disabled' : 'required autofocus' ?>>
            </div>
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-key me-1"></i>Password
                </label>
                <div class="input-group">
                    <input type="password" name="password" id="pwd" class="form-control"
                           placeholder="Password" autocomplete="current-password"
                           <?= $locked ? 'disabled' : 'required' ?>>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()" tabindex="-1">
                        <i class="bi bi-eye" id="eye-icon"></i>
                    </button>
                </div>
                <div class="caps-warning" id="capsWarning">
                    <i class="bi bi-exclamation-triangle me-1"></i><strong>Caps Lock</strong> sedang aktif!
                </div>
            </div>
            <button type="submit" class="btn btn-success w-100" id="submitBtn" <?= $locked ? 'disabled' : '' ?>>
                <span id="submitText"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk</span>
                <span id="submitLoad" style="display:none;">
                    <span class="spinner-border spinner-border-sm me-2"></span>Memverifikasi...
                </span>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="index.php" class="text-muted small text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke halaman utama
            </a>
        </div>
    </div>
    <div class="login-footer">
        <i class="bi bi-shield-check me-1"></i>Login aman · Maks <?= MAX_FAIL_ATTEMPTS ?> percobaan per <?= LOCKOUT_MINUTES ?> menit
    </div>
</div>

<script>
function togglePwd() {
    const p = document.getElementById('pwd');
    const i = document.getElementById('eye-icon');
    if (p.type === 'password') { p.type = 'text'; i.className = 'bi bi-eye-slash'; }
    else { p.type = 'password'; i.className = 'bi bi-eye'; }
}

// Caps Lock detection
const pwd = document.getElementById('pwd');
const capsWarn = document.getElementById('capsWarning');
if (pwd) {
    pwd.addEventListener('keydown', e => {
        if (e.getModifierState && e.getModifierState('CapsLock')) capsWarn.classList.add('show');
        else capsWarn.classList.remove('show');
    });
    pwd.addEventListener('blur', () => capsWarn.classList.remove('show'));
}

// Loading state saat submit
const form = document.getElementById('loginForm');
if (form) {
    form.addEventListener('submit', () => {
        document.getElementById('submitText').style.display = 'none';
        document.getElementById('submitLoad').style.display = 'inline-block';
        document.getElementById('submitBtn').disabled = true;
    });
}

// Countdown lockout
<?php if ($locked && $lockout_remaining > 0): ?>
let secs = <?= $lockout_remaining ?>;
const cd = document.getElementById('countdown');
const tick = setInterval(() => {
    secs--;
    if (secs <= 0) { clearInterval(tick); location.reload(); return; }
    const m = String(Math.floor(secs/60)).padStart(2,'0');
    const s = String(secs%60).padStart(2,'0');
    if (cd) cd.textContent = `${m}:${s}`;
}, 1000);
<?php endif; ?>
</script>
</body>
</html>
