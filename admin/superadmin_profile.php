<?php
if (empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger">Akses ditolak.</div>'; return;
}

// Auto-migrate tabel superadmin_config
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS `superadmin_config` (
        `id` INT PRIMARY KEY DEFAULT 1,
        `nama` VARCHAR(100) NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    // Seed baris pertama dari konstanta jika belum ada
    $conn->exec("INSERT IGNORE INTO `superadmin_config` (id, password_hash) VALUES (1, '" . SUPER_ADMIN_HASH . "')");
} catch (Throwable) {}

// Ambil data saat ini
$cfg = $conn->query("SELECT * FROM superadmin_config WHERE id=1")->fetch();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_nama') {
        $nama_baru = trim($_POST['nama'] ?? '');
        if ($nama_baru) {
            $conn->prepare("UPDATE superadmin_config SET nama=? WHERE id=1")->execute([$nama_baru]);
            $_SESSION['admin_name'] = $nama_baru;
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Nama berhasil diupdate.</div>';
            $cfg['nama'] = $nama_baru;
        }
    }

    if ($action === 'change_password') {
        $old_pwd  = $_POST['old_password'] ?? '';
        $new_pwd  = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        try {
            $current_hash = $cfg['password_hash'] ?? SUPER_ADMIN_HASH;

            if (!password_verify($old_pwd, $current_hash)) {
                throw new Exception('Password lama tidak cocok.');
            }
            if (strlen($new_pwd) < 8) {
                throw new Exception('Password baru minimal 8 karakter.');
            }
            if ($new_pwd !== $confirm) {
                throw new Exception('Konfirmasi password tidak cocok.');
            }

            $new_hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $conn->prepare("UPDATE superadmin_config SET password_hash=? WHERE id=1")->execute([$new_hash]);

            log_admin_action($conn, 'SUPER_CHANGE_PWD', 'Superadmin mengubah password via halaman profil');

            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Password berhasil diubah. Gunakan password baru saat login berikutnya.</div>';
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i>' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

$display_name = $cfg['nama'] ?? SUPER_ADMIN_NAME;
?>

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
                <div class="text-muted small mb-2">@<?= SUPER_ADMIN_USERNAME ?></div>
                <span class="badge" style="background:linear-gradient(135deg,#7c3aed,#a855f7);font-size:.75rem;">
                    <i class="bi bi-shield-fill-check me-1"></i>Superadmin
                </span>
                <hr class="my-3">
                <div class="text-start small text-muted">
                    <div><i class="bi bi-info-circle me-2"></i>Password disimpan di database (terenkripsi bcrypt)</div>
                    <div class="mt-1"><i class="bi bi-clock-history me-2"></i>Diubah: <?= $cfg['updated_at'] ? date('d M Y, H:i', strtotime($cfg['updated_at'])) : '—' ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms Card -->
    <div class="col-md-8">
        <?= $msg ?>

        <!-- Ubah Nama -->
        <div class="card mb-3">
            <div class="card-header fw-semibold">
                <i class="bi bi-person me-2"></i>Nama Tampilan
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_nama">
                    <div class="input-group">
                        <input type="text" name="nama" class="form-control"
                               value="<?= htmlspecialchars($display_name) ?>"
                               placeholder="Nama Superadmin" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Simpan
                        </button>
                    </div>
                    <div class="form-text">Nama ini tampil di sidebar dan header panel.</div>
                </form>
            </div>
        </div>

        <!-- Ganti Password -->
        <div class="card">
            <div class="card-header fw-semibold">
                <i class="bi bi-shield-lock me-2"></i>Ganti Password
            </div>
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
                                        onclick="toggleEye('oldPwd','eyeOld')">
                                    <i class="bi bi-eye" id="eyeOld"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password Baru <small class="text-muted">(min. 8 karakter)</small></label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="newPwd" class="form-control"
                                       placeholder="Password baru" minlength="8" required
                                       oninput="checkStrength(this.value)" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary"
                                        onclick="toggleEye('newPwd','eyeNew')">
                                    <i class="bi bi-eye" id="eyeNew"></i>
                                </button>
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
                                   placeholder="Ulangi password baru" minlength="8" required
                                   autocomplete="new-password">
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

<script>
function toggleEye(inputId, iconId) {
    const el  = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    el.type   = el.type === 'password' ? 'text' : 'password';
    ico.className = el.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

function checkStrength(val) {
    const bar = document.getElementById('strengthBar');
    const fill = document.getElementById('strengthFill');
    const lbl  = document.getElementById('strengthLabel');
    bar.style.display = val.length ? '' : 'none';
    let score = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        [20, 'bg-danger',  'Sangat lemah'],
        [40, 'bg-danger',  'Lemah'],
        [60, 'bg-warning', 'Sedang'],
        [80, 'bg-info',    'Kuat'],
        [100,'bg-success', 'Sangat kuat'],
    ];
    const [pct, cls, text] = levels[score] ?? levels[0];
    fill.style.width = pct + '%';
    fill.className = 'progress-bar ' + cls;
    lbl.textContent = text;
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
    return confirm('Yakin ingin mengganti password superadmin?');
}
</script>
