<?php
if (empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger">Akses ditolak.</div>'; return;
}

// Auto-migrate: superadmin_config (lama)
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS `superadmin_config` (
        `id` INT PRIMARY KEY DEFAULT 1,
        `nama` VARCHAR(100) NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $conn->exec("INSERT IGNORE INTO `superadmin_config` (id, password_hash) VALUES (1, '" . SUPER_ADMIN_HASH . "')");
} catch (Throwable) {}

// Auto-migrate: superadmin_accounts (multi-akun)
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS `superadmin_accounts` (
        `id` INT PRIMARY KEY AUTO_INCREMENT,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `nama` VARCHAR(100) NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    // Seed akun utama dari superadmin_config
    $orig_hash = SUPER_ADMIN_HASH;
    try { $h = $conn->query("SELECT password_hash FROM superadmin_config WHERE id=1")->fetchColumn(); if ($h) $orig_hash = $h; } catch (Throwable) {}
    $conn->prepare("INSERT IGNORE INTO superadmin_accounts (id, username, nama, password_hash) VALUES (1, ?, 'Super Admin', ?)")
         ->execute([SUPER_ADMIN_USERNAME, $orig_hash]);
    // Seed 2 akun tambahan (password bisa diganti via halaman ini)
    $conn->prepare("INSERT IGNORE INTO superadmin_accounts (id, username, nama, password_hash) VALUES (2, 'superadmin2', 'Super Admin 2', ?)")
         ->execute(['$2y$12$ofV4xyuoCzw53wJWcTuepOT3q5Pcj6na.wu703nHER.g5WdirHGwW']);
    $conn->prepare("INSERT IGNORE INTO superadmin_accounts (id, username, nama, password_hash) VALUES (3, 'superadmin3', 'Super Admin 3', ?)")
         ->execute(['$2y$12$pMexbtS80i8APohZIq4lNu7znUgYdugiOJRgJolztVQ.K8d731ray']);
} catch (Throwable) {}

$acc_id = (int)($_SESSION['super_acc_id'] ?? 1);
$cfg    = $conn->query("SELECT * FROM superadmin_config WHERE id=1")->fetch();
$my_acc = null;
try { $st = $conn->prepare("SELECT * FROM superadmin_accounts WHERE id=?"); $st->execute([$acc_id]); $my_acc = $st->fetch() ?: null; } catch(Throwable) {}

$msg = '';
if (!empty($_SESSION['flash_superadmin_profile'])) {
    $msg = $_SESSION['flash_superadmin_profile'];
    unset($_SESSION['flash_superadmin_profile']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_nama') {
        $nama_baru = trim($_POST['nama'] ?? '');
        if ($nama_baru) {
            try {
                $conn->prepare("UPDATE superadmin_accounts SET nama=? WHERE id=?")->execute([$nama_baru, $acc_id]);
                // Sinkron ke tabel lama hanya untuk akun utama
                if ($acc_id === 1) $conn->prepare("UPDATE superadmin_config SET nama=? WHERE id=1")->execute([$nama_baru]);
            } catch (Throwable) {}
            $_SESSION['admin_name'] = $nama_baru;
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Nama berhasil diupdate.</div>';
            if ($my_acc) $my_acc['nama'] = $nama_baru;
        }
    }

    if ($action === 'change_password') {
        $old_pwd = $_POST['old_password'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        try {
            $current_hash = $my_acc['password_hash'] ?? ($cfg['password_hash'] ?? SUPER_ADMIN_HASH);
            if (!password_verify($old_pwd, $current_hash)) throw new Exception('Password lama tidak cocok.');
            if (strlen($new_pwd) < 8)     throw new Exception('Password baru minimal 8 karakter.');
            if ($new_pwd !== $confirm)    throw new Exception('Konfirmasi password tidak cocok.');
            $new_hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $conn->prepare("UPDATE superadmin_accounts SET password_hash=? WHERE id=?")->execute([$new_hash, $acc_id]);
            if ($acc_id === 1) $conn->prepare("UPDATE superadmin_config SET password_hash=? WHERE id=1")->execute([$new_hash]);
            log_admin_action($conn, 'SUPER_CHANGE_PWD', 'Superadmin mengubah password');
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Password berhasil diubah.</div>';
            if ($my_acc) $my_acc['password_hash'] = $new_hash;
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // ── Aksi kelola akun: hanya Superadmin Utama (akun id=1) ─────────────────
    if (in_array($action, ['add_account', 'toggle_active', 'reset_password', 'delete_account'], true)
        && !is_primary_super()) {
        $msg = '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>Hanya <strong>Superadmin Utama</strong> yang dapat mengelola akun superadmin.</div>';
        $action = ''; // batalkan aksi
    }

    // Tambah akun baru
    if ($action === 'add_account') {
        $new_user = trim($_POST['new_username'] ?? '');
        $new_name = trim($_POST['new_nama'] ?? '');
        $new_pwd  = $_POST['new_password'] ?? '';
        $new_conf = $_POST['new_confirm'] ?? '';
        try {
            if (!preg_match('/^[a-z0-9_]{3,30}$/', $new_user)) throw new Exception('Username hanya huruf kecil, angka, dan _ (3–30 karakter).');
            if (strlen($new_pwd) < 8)    throw new Exception('Password minimal 8 karakter.');
            if ($new_pwd !== $new_conf)  throw new Exception('Konfirmasi password tidak cocok.');
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $conn->prepare("INSERT INTO superadmin_accounts (username, nama, password_hash) VALUES (?,?,?)")
                 ->execute([$new_user, $new_name ?: null, $hash]);
            log_admin_action($conn, 'SUPER_ADD_ACCOUNT', "Tambah akun superadmin: $new_user");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Akun <strong>' . htmlspecialchars($new_user) . '</strong> berhasil ditambahkan.</div>';
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // Toggle aktif/nonaktif
    if ($action === 'toggle_active') {
        $tid = (int)($_POST['target_id'] ?? 0);
        if ($tid === $acc_id) {
            $msg = '<div class="alert alert-warning">Tidak bisa menonaktifkan akun yang sedang dipakai.</div>';
        } else {
            try {
                $conn->prepare("UPDATE superadmin_accounts SET is_active = 1 - is_active WHERE id=?")->execute([$tid]);
                log_admin_action($conn, 'SUPER_TOGGLE_ACCOUNT', "Toggle aktif akun superadmin id=$tid");
                $msg = '<div class="alert alert-success">Status akun diperbarui.</div>';
            } catch (Throwable) {}
        }
    }

    // Reset password oleh superadmin (tanpa perlu password lama)
    if ($action === 'reset_password') {
        $tid      = (int)($_POST['target_id'] ?? 0);
        $new_pwd  = $_POST['reset_password'] ?? '';
        $new_conf = $_POST['reset_confirm'] ?? '';
        try {
            if (strlen($new_pwd) < 8)   throw new Exception('Password minimal 8 karakter.');
            if ($new_pwd !== $new_conf) throw new Exception('Konfirmasi tidak cocok.');
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $conn->prepare("UPDATE superadmin_accounts SET password_hash=? WHERE id=?")->execute([$hash, $tid]);
            if ($tid === 1) $conn->prepare("UPDATE superadmin_config SET password_hash=? WHERE id=1")->execute([$hash]);
            log_admin_action($conn, 'SUPER_RESET_PWD', "Reset password akun superadmin id=$tid");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Password berhasil direset.</div>';
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // Hapus akun
    if ($action === 'delete_account') {
        $tid = (int)($_POST['target_id'] ?? 0);
        if ($tid === $acc_id) {
            $msg = '<div class="alert alert-warning">Tidak bisa menghapus akun yang sedang dipakai.</div>';
        } elseif ($tid === 1) {
            $msg = '<div class="alert alert-warning">Akun utama (superadmin) tidak bisa dihapus.</div>';
        } else {
            try {
                $conn->prepare("DELETE FROM superadmin_accounts WHERE id=?")->execute([$tid]);
                log_admin_action($conn, 'SUPER_DEL_ACCOUNT', "Hapus akun superadmin id=$tid");
                $msg = '<div class="alert alert-success">Akun berhasil dihapus.</div>';
            } catch (Throwable) {}
        }
    }

    // PRG: redirect setelah POST agar refresh tidak mengulang aksi
    $_SESSION['flash_superadmin_profile'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: superadmin_dashboard.php?page=superadmin_profile');
    exit;
}

$display_name = $my_acc['nama'] ?? ($cfg['nama'] ?? SUPER_ADMIN_NAME);
$my_username  = $my_acc['username'] ?? SUPER_ADMIN_USERNAME;

// Ambil semua akun untuk tabel kelola
$all_accounts = [];
try {
    $all_accounts = $conn->query("SELECT * FROM superadmin_accounts ORDER BY id")->fetchAll();
} catch (Throwable) {}

// Ambil credential WebAuthn milik akun ini
$wau_creds = [];
try {
    $ws = $conn->prepare("SELECT id,device_name,created_at FROM webauthn_credentials WHERE user_type='super' AND user_id=? ORDER BY created_at DESC");
    $ws->execute([$acc_id]);
    $wau_creds = $ws->fetchAll();
} catch (Throwable) {}
?>

<?= $msg ?>

<div class="row g-4">
    <!-- Profil Card -->
    <div class="col-md-4">
        <div class="card text-center h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-center mb-3"
                     style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#a855f7);margin:0 auto;">
                    <i class="bi bi-shield-fill-check text-white" style="font-size:2rem;"></i>
                </div>
                <h5 class="fw-bold mb-0"><?= htmlspecialchars($display_name) ?></h5>
                <div class="text-muted small mb-2">@<?= htmlspecialchars($my_username) ?></div>
                <span class="badge" style="background:linear-gradient(135deg,#7c3aed,#a855f7);font-size:.75rem;">
                    <i class="bi <?= is_primary_super() ? 'bi-star-fill' : 'bi-shield-fill-check' ?> me-1"></i><?= is_primary_super() ? 'Superadmin Utama' : 'Superadmin' ?>
                </span>
                <hr class="my-3">
                <div class="text-start small text-muted">
                    <div><i class="bi bi-info-circle me-2"></i>Password terenkripsi bcrypt</div>
                    <div class="mt-1"><i class="bi bi-hash me-2"></i>ID Akun: <?= $acc_id ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms -->
    <div class="col-md-8">
        <!-- Ubah Nama -->
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-person me-2"></i>Nama Tampilan</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_nama">
                    <div class="input-group">
                        <input type="text" name="nama" class="form-control"
                               value="<?= htmlspecialchars($display_name) ?>"
                               placeholder="Nama Superadmin" required>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                    </div>
                    <div class="form-text">Nama ini tampil di sidebar dan header panel.</div>
                </form>
            </div>
        </div>

        <!-- Ganti Password Sendiri -->
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-shield-lock me-2"></i>Ganti Password Anda</div>
            <div class="card-body">
                <form method="POST" onsubmit="return validatePwd()">
                    <input type="hidden" name="action" value="change_password">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Password Lama</label>
                            <div class="input-group">
                                <input type="password" name="old_password" id="oldPwd" class="form-control"
                                       placeholder="Password saat ini" required autocomplete="current-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="toggleEye('oldPwd','eyeOld')"><i class="bi bi-eye" id="eyeOld"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password Baru <small class="text-muted">(min. 8 karakter)</small></label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="newPwd" class="form-control"
                                       placeholder="Password baru" minlength="8" required
                                       oninput="checkStrength(this.value)" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="toggleEye('newPwd','eyeNew')"><i class="bi bi-eye" id="eyeNew"></i></button>
                            </div>
                            <div id="strengthBar" class="mt-1" style="display:none;">
                                <div class="progress" style="height:4px;">
                                    <div id="strengthFill" class="progress-bar" style="width:0%;transition:width .3s;"></div>
                                </div>
                                <small id="strengthLabel" class="text-muted"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" id="confirmPwd" class="form-control"
                                   placeholder="Ulangi password baru" minlength="8" required autocomplete="new-password">
                            <div id="matchWarn" class="form-text text-danger d-none">
                                <i class="bi bi-x-circle me-1"></i>Password tidak cocok
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning fw-semibold">
                                <i class="bi bi-arrow-clockwise me-1"></i>Ganti Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (is_primary_super()): ?>
<!-- ══ Kelola Akun Superadmin (hanya Superadmin Utama) ══════════════════════ -->
<div class="card mt-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-people-fill me-2"></i>Kelola Akun Superadmin</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#formTambahAkun">
            <i class="bi bi-plus-lg me-1"></i>Tambah Akun
        </button>
    </div>

    <!-- Form Tambah Akun -->
    <div class="collapse" id="formTambahAkun">
        <div class="card-body border-bottom bg-light">
            <form method="POST">
                <input type="hidden" name="action" value="add_account">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Username <small class="text-muted">(a-z, 0-9, _)</small></label>
                        <input type="text" name="new_username" class="form-control" placeholder="contoh: budi_s" pattern="[a-z0-9_]{3,30}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nama Tampilan</label>
                        <input type="text" name="new_nama" class="form-control" placeholder="Nama (opsional)">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Min. 8 karakter" minlength="8" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="new_confirm" class="form-control" placeholder="Ulangi password" minlength="8" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-person-plus me-1"></i>Simpan Akun Baru
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Akun -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($all_accounts as $acc): ?>
            <tr <?= $acc['id'] == $acc_id ? 'class="table-primary"' : '' ?>>
                <td><span class="text-muted small"><?= $acc['id'] ?></span></td>
                <td>
                    <span class="fw-semibold"><?= htmlspecialchars($acc['username']) ?></span>
                    <?php if ($acc['id'] == $acc_id): ?>
                    <span class="badge bg-primary ms-1" style="font-size:.65rem;">Anda</span>
                    <?php endif; ?>
                    <?php if ($acc['id'] == 1): ?>
                    <span class="badge bg-secondary ms-1" style="font-size:.65rem;">Utama</span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small"><?= htmlspecialchars($acc['nama'] ?? '—') ?></td>
                <td>
                    <?php if ($acc['is_active']): ?>
                    <span class="badge bg-success">Aktif</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <!-- Reset Password -->
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal"
                            data-bs-target="#modalReset<?= $acc['id'] ?>">
                        <i class="bi bi-key"></i> Reset
                    </button>
                    <?php if ($acc['id'] != $acc_id): ?>
                    <!-- Toggle Aktif -->
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="target_id" value="<?= $acc['id'] ?>">
                        <button type="submit" class="btn btn-sm <?= $acc['is_active'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                            <i class="bi <?= $acc['is_active'] ? 'bi-pause-fill' : 'bi-play-fill' ?>"></i>
                            <?= $acc['is_active'] ? 'Nonaktif' : 'Aktifkan' ?>
                        </button>
                    </form>
                    <?php if ($acc['id'] != 1): ?>
                    <!-- Hapus -->
                    <form method="POST" class="d-inline"
                          onsubmit="return confirm('Hapus akun <?= htmlspecialchars($acc['username']) ?>?')">
                        <input type="hidden" name="action" value="delete_account">
                        <input type="hidden" name="target_id" value="<?= $acc['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals Reset Password -->
<?php foreach ($all_accounts as $acc): ?>
<div class="modal fade" id="modalReset<?= $acc['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="target_id" value="<?= $acc['id'] ?>">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password — <?= htmlspecialchars($acc['username']) ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Password Baru <small class="text-muted">(min. 8 karakter)</small></label>
                        <input type="password" name="reset_password" class="form-control" minlength="8" required>
                    </div>
                    <div>
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="reset_confirm" class="form-control" minlength="8" required>
                    </div>
                    <?php if ($acc['id'] == $acc_id): ?>
                    <div class="alert alert-info small mt-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i>Ini akun Anda sendiri. Tidak perlu memasukkan password lama.
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Info akun baru -->
<?php
$seed_notif = false;
try {
    $cnt = (int)$conn->query("SELECT COUNT(*) FROM superadmin_accounts")->fetchColumn();
    if ($cnt >= 3) {
        $sa2 = $conn->prepare("SELECT * FROM superadmin_accounts WHERE id=2"); $sa2->execute(); $sa2r = $sa2->fetch();
        $sa3 = $conn->prepare("SELECT * FROM superadmin_accounts WHERE id=3"); $sa3->execute(); $sa3r = $sa3->fetch();
        if ($sa2r && $sa3r) $seed_notif = true;
    }
} catch (Throwable) {}
?>
<?php if ($seed_notif): ?>
<div class="alert alert-info mt-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>2 akun superadmin baru telah dibuat:</strong><br>
    Username: <code>superadmin2</code> — Password sementara: <code>Superadmin2!</code><br>
    Username: <code>superadmin3</code> — Password sementara: <code>Superadmin3!</code><br>
    <span class="text-danger fw-semibold">Segera ganti password keduanya via tombol Reset di atas!</span>
</div>
<?php endif; ?>
<?php endif; // is_primary_super — Kelola Akun ?>

<!-- ══ Login Sidik Jari / WebAuthn ══════════════════════════════════════════ -->
<div class="card mt-4" style="max-width:560px">
    <div class="card-header fw-semibold">
        <i class="bi bi-fingerprint me-2 text-primary"></i>Login Sidik Jari (WebAuthn)
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            Daftarkan sidik jari perangkat ini agar bisa login tanpa mengetik password.
            Cocok untuk Windows Hello, Touch ID, atau sensor sidik jari Android.
        </p>

        <div id="wauCredList">
        <?php if ($wau_creds): ?>
            <div class="mb-3">
                <div class="fw-semibold small mb-2">Perangkat terdaftar:</div>
                <?php foreach ($wau_creds as $wc): ?>
                <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2 mb-2 bg-light">
                    <div>
                        <i class="bi bi-laptop me-2 text-primary"></i>
                        <span class="small fw-semibold"><?= htmlspecialchars($wc['device_name'] ?: 'Perangkat') ?></span>
                        <span class="text-muted small ms-2"><?= date('d/m/Y H:i', strtotime($wc['created_at'])) ?></span>
                    </div>
                    <button class="btn btn-sm btn-outline-danger py-0 px-2"
                            onclick="deleteWauCred(<?= $wc['id'] ?>, this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-light border small mb-3">
                <i class="bi bi-info-circle me-2"></i>Belum ada perangkat terdaftar.
            </div>
        <?php endif; ?>
        </div>

        <div id="wauBrowserAlert" class="alert alert-warning small py-2 mb-3" style="display:none">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Browser ini tidak mendukung WebAuthn atau tidak ada sensor biometrik yang tersedia.
        </div>

        <div class="row g-2 align-items-end mb-2">
            <div class="col">
                <label class="form-label small mb-1">Nama perangkat <span class="text-muted">(opsional)</span></label>
                <input type="text" id="wauDevName" class="form-control form-control-sm"
                       placeholder="cth: Laptop Kantor">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm" id="wauRegBtn" onclick="registerFingerprint()">
                    <i class="bi bi-fingerprint me-1"></i>Daftarkan
                </button>
            </div>
        </div>
        <div id="wauStatus" class="small text-muted" style="min-height:18px"></div>
    </div>
</div>

<script>
(function() {
    if (!window.PublicKeyCredential) {
        document.getElementById('wauBrowserAlert').style.display = 'block';
        document.getElementById('wauRegBtn').disabled = true;
        return;
    }
    PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().then(ok => {
        if (!ok) {
            document.getElementById('wauBrowserAlert').style.display = 'block';
            document.getElementById('wauRegBtn').disabled = true;
        }
    });
})();

function b64uDec(s) {
    s = s.replace(/-/g,'+').replace(/_/g,'/');
    while (s.length % 4) s += '=';
    return Uint8Array.from(atob(s), c => c.charCodeAt(0));
}
function b64uEnc(buf) {
    return btoa(String.fromCharCode(...new Uint8Array(buf)))
        .replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
}
function wauStatus(msg, cls='text-muted') {
    const el = document.getElementById('wauStatus');
    el.className = 'small ' + cls;
    el.textContent = msg;
}

async function registerFingerprint() {
    const btn    = document.getElementById('wauRegBtn');
    const dname  = document.getElementById('wauDevName').value.trim();
    btn.disabled = true;
    wauStatus('Meminta challenge dari server…');
    try {
        const chalRes  = await fetch('webauthn_handler.php?action=register_challenge');
        const chalData = await chalRes.json();
        if (chalData.error) throw new Error(chalData.error);

        const opts = {
            challenge: b64uDec(chalData.challenge),
            rp:        chalData.rp,
            user: {
                id:          b64uDec(chalData.user.id),
                name:        chalData.user.name,
                displayName: chalData.user.displayName,
            },
            pubKeyCredParams:       chalData.pubKeyCredParams,
            timeout:                chalData.timeout,
            authenticatorSelection: chalData.authenticatorSelection,
            attestation:            chalData.attestation,
        };

        wauStatus('Silakan scan sidik jari…');
        const cred = await navigator.credentials.create({ publicKey: opts });
        wauStatus('Menyimpan ke server…');

        const payload = {
            clientDataJSON:    b64uEnc(cred.response.clientDataJSON),
            attestationObject: b64uEnc(cred.response.attestationObject),
            deviceName:        dname,
        };
        const verRes  = await fetch('webauthn_handler.php?action=register_verify', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload),
        });
        const verData = await verRes.json();
        if (verData.error) throw new Error(verData.error);

        wauStatus('Sidik jari berhasil didaftarkan!', 'text-success fw-semibold');
        document.getElementById('wauDevName').value = '';
        btn.disabled = false;
        setTimeout(() => location.reload(), 1200);
    } catch(e) {
        btn.disabled = false;
        if (e.name === 'NotAllowedError')    wauStatus('Dibatalkan atau timeout.', 'text-warning');
        else if (e.name === 'InvalidStateError') wauStatus('Perangkat ini sudah terdaftar.', 'text-warning');
        else wauStatus('Gagal: ' + e.message, 'text-danger');
    }
}

async function deleteWauCred(id, btn) {
    if (!confirm('Hapus credential sidik jari ini?')) return;
    btn.disabled = true;
    try {
        const fd = new FormData(); fd.append('id', id);
        const res  = await fetch('webauthn_handler.php?action=delete_credential', { method:'POST', body:fd });
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        btn.closest('.d-flex').remove();
    } catch(e) { btn.disabled = false; alert('Gagal: ' + e.message); }
}
</script>

<script>
function toggleEye(inputId, iconId) {
    const el  = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    el.type   = el.type === 'password' ? 'text' : 'password';
    ico.className = el.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function checkStrength(val) {
    const bar  = document.getElementById('strengthBar');
    const fill = document.getElementById('strengthFill');
    const lbl  = document.getElementById('strengthLabel');
    bar.style.display = val.length ? '' : 'none';
    let score = 0;
    if (val.length >= 8)          score++;
    if (val.length >= 12)         score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        [20,'bg-danger','Sangat lemah'],[40,'bg-danger','Lemah'],
        [60,'bg-warning','Sedang'],[80,'bg-info','Kuat'],[100,'bg-success','Sangat kuat'],
    ];
    const [pct, cls, text] = levels[score] ?? levels[0];
    fill.style.width = pct + '%'; fill.className = 'progress-bar ' + cls; lbl.textContent = text;
}

document.getElementById('confirmPwd')?.addEventListener('input', function() {
    const match = this.value === document.getElementById('newPwd').value;
    document.getElementById('matchWarn').classList.toggle('d-none', match || !this.value);
});

function validatePwd() {
    const n = document.getElementById('newPwd').value;
    const c = document.getElementById('confirmPwd').value;
    if (n !== c) { alert('Password baru dan konfirmasi tidak cocok.'); return false; }
    if (n.length < 8) { alert('Password minimal 8 karakter.'); return false; }
    return confirm('Yakin ingin mengganti password?');
}
</script>
