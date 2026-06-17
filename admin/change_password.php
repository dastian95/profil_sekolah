<?php
$msg = '';
if (!empty($_SESSION['flash_change_password'])) {
    $msg = $_SESSION['flash_change_password'];
    unset($_SESSION['flash_change_password']);
}
$is_super = !empty($_SESSION['is_super']);

// Ambil daftar credential WebAuthn user ini untuk ditampilkan
$wau_utype = $is_super ? 'super' : 'admin';
$wau_uid   = $is_super ? (int)($_SESSION['super_acc_id'] ?? 0) : (int)$_SESSION['admin_id'];
$wau_creds = [];
try {
    $ws = $conn->prepare("SELECT id,device_name,created_at FROM webauthn_credentials WHERE user_type=? AND user_id=? ORDER BY created_at DESC");
    $ws->execute([$wau_utype, $wau_uid]);
    $wau_creds = $ws->fetchAll();
} catch (Throwable) {}

if ($is_super) {
    // Superadmin tidak bisa ganti password lewat panel — datanya hardcoded di admin.php
    ?>
    <div class="card mb-4" style="max-width:560px">
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
    // Lanjut ke bagian WebAuthn di bawah (jangan return)
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

<?php if (!$is_super): ?>
<div class="card mb-4" style="max-width:480px">
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
<?php endif; ?>

<!-- ── Sidik Jari / WebAuthn ── -->
<div class="card" style="max-width:480px" id="wauCard">
    <div class="card-header fw-semibold">
        <i class="bi bi-fingerprint me-2 text-primary"></i>Login Sidik Jari (WebAuthn)
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            Daftarkan sidik jari perangkat ini agar bisa login tanpa mengetik password.
            Cocok untuk Windows Hello, Touch ID, atau sensor sidik jari Android.
        </p>

        <!-- Daftar credential tersimpan -->
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
            <div class="alert alert-light border small mb-3" id="wauNoCreds">
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
    const btn     = document.getElementById('wauRegBtn');
    const devName = document.getElementById('wauDevName').value.trim();
    btn.disabled  = true;
    wauStatus('Meminta challenge dari server…');

    const handlerBase = 'webauthn_handler.php';

    try {
        // 1. Get registration challenge
        const chalRes  = await fetch(handlerBase + '?action=register_challenge');
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
            pubKeyCredParams:      chalData.pubKeyCredParams,
            timeout:               chalData.timeout,
            authenticatorSelection: chalData.authenticatorSelection,
            attestation:           chalData.attestation,
        };

        wauStatus('Silakan scan sidik jari Anda…');

        // 2. Create credential (triggers fingerprint prompt)
        const cred = await navigator.credentials.create({ publicKey: opts });

        wauStatus('Menyimpan ke server…');

        // 3. Send to server
        const payload = {
            clientDataJSON:    b64uEnc(cred.response.clientDataJSON),
            attestationObject: b64uEnc(cred.response.attestationObject),
            deviceName:        devName,
        };

        const verRes  = await fetch(handlerBase + '?action=register_verify', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const verData = await verRes.json();
        if (verData.error) throw new Error(verData.error);

        wauStatus('Sidik jari berhasil didaftarkan!', 'text-success fw-semibold');
        document.getElementById('wauDevName').value = '';
        btn.disabled = false;
        // Reload halaman agar daftar credential diperbarui
        setTimeout(() => location.reload(), 1200);

    } catch (e) {
        btn.disabled = false;
        if (e.name === 'NotAllowedError') {
            wauStatus('Dibatalkan atau timeout.', 'text-warning');
        } else if (e.name === 'InvalidStateError') {
            wauStatus('Perangkat ini sudah terdaftar sebelumnya.', 'text-warning');
        } else {
            wauStatus('Gagal: ' + e.message, 'text-danger');
        }
    }
}

async function deleteWauCred(id, btn) {
    if (!confirm('Hapus credential sidik jari ini?')) return;
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('id', id);
        const res  = await fetch('webauthn_handler.php?action=delete_credential', { method:'POST', body:fd });
        const data = await res.json();
        if (data.error) throw new Error(data.error);
        btn.closest('.d-flex').remove();
    } catch(e) {
        btn.disabled = false;
        alert('Gagal menghapus: ' + e.message);
    }
}
</script>
