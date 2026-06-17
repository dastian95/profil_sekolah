<?php
require_once __DIR__ . '/conn.php';
header('Content-Type: application/json');

// Auto-migrate
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS webauthn_credentials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_type ENUM('admin','super') NOT NULL,
        user_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        credential_id VARCHAR(600) NOT NULL,
        public_key TEXT NOT NULL,
        sign_count INT NOT NULL DEFAULT 0,
        device_name VARCHAR(100) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_cred_id (credential_id(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException) {}

// ─── Helpers ────────────────────────────────────────────────────────────────

function b64u_enc(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64u_dec(string $data): string {
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);
    return base64_decode(strtr($data, '-_', '+/'));
}

function json_err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

function get_rp_id(): string {
    return preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
}

function get_origin(): string {
    $s = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $s . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

// ─── CBOR Decoder (subset needed for WebAuthn) ──────────────────────────────

function cbor_read_arg(string $d, int &$p, int $info): int {
    if ($info <= 23) return $info;
    if ($info === 24) return ord($d[$p++]);
    if ($info === 25) { $v = unpack('n', substr($d, $p, 2))[1]; $p += 2; return $v; }
    if ($info === 26) { $v = unpack('N', substr($d, $p, 4))[1]; $p += 4; return $v; }
    if ($info === 27) {
        $hi = unpack('N', substr($d, $p,   4))[1];
        $lo = unpack('N', substr($d, $p+4, 4))[1];
        $p += 8;
        return ($hi * 0x100000000) + $lo;
    }
    throw new Exception("CBOR: unsupported additional info $info");
}

function cbor_decode(string $d, int &$p = 0): mixed {
    if ($p >= strlen($d)) throw new Exception('CBOR: unexpected end of data');
    $byte  = ord($d[$p++]);
    $major = $byte >> 5;
    $info  = $byte & 0x1F;

    switch ($major) {
        case 0: return cbor_read_arg($d, $p, $info);
        case 1: return -1 - cbor_read_arg($d, $p, $info);
        case 2: // bytes
            $len = cbor_read_arg($d, $p, $info);
            $v   = substr($d, $p, $len); $p += $len;
            return $v;
        case 3: // text
            $len = cbor_read_arg($d, $p, $info);
            $v   = substr($d, $p, $len); $p += $len;
            return $v;
        case 4: // array
            $len = cbor_read_arg($d, $p, $info);
            $arr = [];
            for ($i = 0; $i < $len; $i++) $arr[] = cbor_decode($d, $p);
            return $arr;
        case 5: // map
            $len = cbor_read_arg($d, $p, $info);
            $map = [];
            for ($i = 0; $i < $len; $i++) {
                $k        = cbor_decode($d, $p);
                $map[(string)$k] = cbor_decode($d, $p);
            }
            return $map;
        case 7:
            if ($info === 20) return false;
            if ($info === 21) return true;
            if ($info === 22) return null;
            if ($info === 25) { $p += 2; return 0.0; } // half-float, skip
            if ($info === 26) { $p += 4; return 0.0; } // float, skip
            if ($info === 27) { $p += 8; return 0.0; } // double, skip
            if ($info === 31) return null; // break
            throw new Exception("CBOR: unsupported simple $info");
        default:
            throw new Exception("CBOR: unsupported major type $major");
    }
}

// ─── DER helpers for building SubjectPublicKeyInfo ──────────────────────────

function der_len(int $n): string {
    if ($n < 128)   return chr($n);
    if ($n < 256)   return "\x81" . chr($n);
    return "\x82" . chr($n >> 8) . chr($n & 0xFF);
}

function der_seq(string $c): string { return "\x30" . der_len(strlen($c)) . $c; }
function der_oid(string $o): string { return "\x06" . der_len(strlen($o)) . $o; }
function der_bit(string $c): string { return "\x03" . der_len(strlen($c) + 1) . "\x00" . $c; }

function cose_to_pem(array $key): string {
    $kty = (int)($key['1'] ?? 0);

    if ($kty === 2) {
        // EC P-256 (alg -7 / ES256)
        $x = $key['-2'] ?? '';
        $y = $key['-3'] ?? '';
        if (strlen($x) !== 32 || strlen($y) !== 32) throw new Exception('Invalid EC coordinates');

        // OID id-ecPublicKey: 1.2.840.10045.2.1
        $oidAlg = "\x2a\x86\x48\xce\x3d\x02\x01";
        // OID prime256v1: 1.2.840.10045.3.1.7
        $oidCrv = "\x2a\x86\x48\xce\x3d\x03\x01\x07";

        $spki = der_seq(der_seq(der_oid($oidAlg) . der_oid($oidCrv)) . der_bit("\x04" . $x . $y));
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    if ($kty === 3) {
        // RSA (alg -257 / RS256)
        $n = $key['-1'] ?? '';
        $e = $key['-2'] ?? '';
        if (!$n || !$e) throw new Exception('Invalid RSA key');

        // Ensure positive (add 0x00 prefix if high bit set)
        if (ord($n[0]) > 0x7F) $n = "\x00" . $n;
        if (ord($e[0]) > 0x7F) $e = "\x00" . $e;

        $rsaKey  = der_seq("\x02" . der_len(strlen($n)) . $n . "\x02" . der_len(strlen($e)) . $e);
        $oidRsa  = "\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01";
        $spki    = der_seq(der_seq(der_oid($oidRsa) . "\x05\x00") . der_bit($rsaKey));
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    throw new Exception("Unsupported COSE key type: $kty");
}

// ─── Parse authData ──────────────────────────────────────────────────────────

function parse_auth_data(string $ad): array {
    if (strlen($ad) < 37) throw new Exception('authData too short');
    $rpIdHash  = substr($ad, 0, 32);
    $flags     = ord($ad[32]);
    $signCount = unpack('N', substr($ad, 33, 4))[1];
    $up        = ($flags & 0x01) !== 0;
    $uv        = ($flags & 0x04) !== 0;
    $at        = ($flags & 0x40) !== 0;

    $credId = null;
    $coseKey = null;

    if ($at && strlen($ad) > 37) {
        $off = 37;
        $off += 16; // aaguid
        if ($off + 2 > strlen($ad)) throw new Exception('authData: truncated at credIdLen');
        $cidLen = unpack('n', substr($ad, $off, 2))[1]; $off += 2;
        $credId = substr($ad, $off, $cidLen); $off += $cidLen;
        $coseRaw = substr($ad, $off);
        $p = 0;
        $coseKey = cbor_decode($coseRaw, $p);
    }

    return compact('rpIdHash', 'flags', 'signCount', 'up', 'uv', 'at', 'credId', 'coseKey');
}

// ─── Routing ─────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? '';

// ── register_challenge ────────────────────────────────────────────────────────
if ($action === 'register_challenge') {
    if (!isset($_SESSION['admin_id'])) json_err('Belum login', 401);

    $challenge = random_bytes(32);
    $_SESSION['wau_reg_chal'] = b64u_enc($challenge);

    $uid   = $_SESSION['is_super'] ? ($_SESSION['super_acc_id'] ?? 0) : (int)$_SESSION['admin_id'];
    $uname = $_SESSION['admin_name'] ?? 'admin';
    $utype = !empty($_SESSION['is_super']) ? 'super' : 'admin';

    // user.id must be unique per user — combine type+id
    $userId = b64u_enc(pack('Ca*', $utype === 'super' ? 1 : 0, pack('N', $uid)));

    echo json_encode([
        'challenge' => $_SESSION['wau_reg_chal'],
        'rp' => [
            'id'   => get_rp_id(),
            'name' => 'SPMB SMK Lab Jakarta',
        ],
        'user' => [
            'id'          => $userId,
            'name'        => $uname,
            'displayName' => $uname,
        ],
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],    // ES256
            ['type' => 'public-key', 'alg' => -257],   // RS256
        ],
        'timeout'               => 60000,
        'authenticatorSelection' => [
            'authenticatorAttachment' => 'platform',
            'userVerification'        => 'required',
            'residentKey'             => 'preferred',
        ],
        'attestation' => 'none',
    ]);
    exit;
}

// ── register_verify ──────────────────────────────────────────────────────────
if ($action === 'register_verify') {
    if (!isset($_SESSION['admin_id'])) json_err('Belum login', 401);
    if (empty($_SESSION['wau_reg_chal'])) json_err('Challenge tidak ditemukan atau sudah kedaluwarsa');

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) json_err('Request body tidak valid');

    try {
        // Verify clientDataJSON
        $cdj = json_decode(b64u_dec($body['clientDataJSON'] ?? ''), true);
        if (!$cdj) json_err('clientDataJSON tidak valid');
        if (($cdj['type'] ?? '') !== 'webauthn.create') json_err('Type tidak valid');
        if (($cdj['challenge'] ?? '') !== $_SESSION['wau_reg_chal']) json_err('Challenge tidak cocok');
        if (($cdj['origin'] ?? '') !== get_origin()) {
            // Allow localhost variants
            $o = $cdj['origin'] ?? '';
            if (!str_starts_with($o, 'http://localhost') && !str_starts_with($o, 'https://' . get_rp_id())) {
                json_err('Origin tidak valid: ' . $o);
            }
        }

        // Decode attestationObject
        $attObjRaw = b64u_dec($body['attestationObject'] ?? '');
        $pos       = 0;
        $attObj    = cbor_decode($attObjRaw, $pos);
        $authDataRaw = $attObj['authData'] ?? '';
        if (!$authDataRaw) json_err('authData tidak ditemukan');

        $ad = parse_auth_data($authDataRaw);
        if (!$ad['up']) json_err('User not present');
        if (!$ad['uv']) json_err('User verification failed');

        $expectedRpIdHash = hash('sha256', get_rp_id(), true);
        if ($ad['rpIdHash'] !== $expectedRpIdHash) json_err('rpId hash tidak cocok');

        if (!$ad['at'] || !$ad['credId'] || !$ad['coseKey']) json_err('Attested credential data tidak ada');

        $credIdB64 = b64u_enc($ad['credId']);
        $pem       = cose_to_pem($ad['coseKey']);

        // Store
        $utype = !empty($_SESSION['is_super']) ? 'super' : 'admin';
        $uid   = !empty($_SESSION['is_super']) ? (int)($_SESSION['super_acc_id'] ?? 0) : (int)$_SESSION['admin_id'];
        $uname = $_SESSION['admin_name'] ?? '';
        $dname = trim($body['deviceName'] ?? '') ?: null;

        // Remove old credential for same user (one device = one credential per user for simplicity)
        // Allow multiple: just insert
        $check = $conn->prepare("SELECT id FROM webauthn_credentials WHERE credential_id=?");
        $check->execute([$credIdB64]);
        if ($check->fetch()) json_err('Credential ini sudah terdaftar');

        $conn->prepare("INSERT INTO webauthn_credentials (user_type,user_id,username,credential_id,public_key,sign_count,device_name) VALUES (?,?,?,?,?,?,?)")
             ->execute([$utype, $uid, $uname, $credIdB64, $pem, $ad['signCount'], $dname]);

        unset($_SESSION['wau_reg_chal']);
        echo json_encode(['ok' => true, 'message' => 'Sidik jari berhasil didaftarkan!']);

    } catch (Throwable $e) {
        json_err('Gagal mendaftarkan: ' . $e->getMessage());
    }
    exit;
}

// ── login_challenge ──────────────────────────────────────────────────────────
if ($action === 'login_challenge') {
    $username  = trim($_POST['username'] ?? '');
    $challenge = random_bytes(32);
    $_SESSION['wau_login_chal']  = b64u_enc($challenge);
    $_SESSION['wau_login_uname'] = $username;

    $allowCreds = [];
    if ($username) {
        // Cari user_id & user_type lewat tabel akun (bukan display name)
        $uid   = null;
        $utype = null;

        // Cek superadmin_accounts
        try {
            $sa = $conn->prepare("SELECT id FROM superadmin_accounts WHERE username=? AND is_active=1");
            $sa->execute([$username]);
            $row = $sa->fetch();
            if ($row) { $uid = (int)$row['id']; $utype = 'super'; }
        } catch (Throwable) {}

        // Cek tabel admins
        if (!$uid) {
            try {
                $adm = $conn->prepare("SELECT id FROM admins WHERE username=?");
                $adm->execute([$username]);
                $row = $adm->fetch();
                if ($row) { $uid = (int)$row['id']; $utype = 'admin'; }
            } catch (Throwable) {}
        }

        if ($uid && $utype) {
            $stmt = $conn->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_type=? AND user_id=?");
            $stmt->execute([$utype, $uid]);
            foreach ($stmt->fetchAll() as $c) {
                $allowCreds[] = ['type' => 'public-key', 'id' => $c['credential_id']];
            }
        }
    }

    echo json_encode([
        'challenge'        => $_SESSION['wau_login_chal'],
        'timeout'          => 60000,
        'rpId'             => get_rp_id(),
        'allowCredentials' => $allowCreds,
        'userVerification' => 'required',
    ]);
    exit;
}

// ── login_verify ─────────────────────────────────────────────────────────────
if ($action === 'login_verify') {
    if (empty($_SESSION['wau_login_chal'])) json_err('Challenge tidak ditemukan atau sudah kedaluwarsa');

    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) json_err('Request body tidak valid');

    try {
        // Verify clientDataJSON
        $cdj = json_decode(b64u_dec($body['clientDataJSON'] ?? ''), true);
        if (!$cdj) json_err('clientDataJSON tidak valid');
        if (($cdj['type'] ?? '') !== 'webauthn.get') json_err('Type tidak valid');
        if (($cdj['challenge'] ?? '') !== $_SESSION['wau_login_chal']) json_err('Challenge tidak cocok');
        $expectedOrigin = get_origin();
        if (($cdj['origin'] ?? '') !== $expectedOrigin) {
            $o = $cdj['origin'] ?? '';
            if (!str_starts_with($o, 'http://localhost') && !str_starts_with($o, 'https://' . get_rp_id())) {
                json_err('Origin tidak valid');
            }
        }

        // Lookup credential
        $credId = $body['id'] ?? '';
        if (!$credId) json_err('Credential ID kosong');

        $stmt = $conn->prepare("SELECT * FROM webauthn_credentials WHERE credential_id=?");
        $stmt->execute([$credId]);
        $cred = $stmt->fetch();
        if (!$cred) json_err('Credential tidak ditemukan. Daftarkan sidik jari terlebih dahulu.');

        // Parse authenticatorData
        $authDataRaw = b64u_dec($body['authenticatorData'] ?? '');
        if (strlen($authDataRaw) < 37) json_err('authenticatorData terlalu pendek');

        $rpIdHash = substr($authDataRaw, 0, 32);
        $expectedRpIdHash = hash('sha256', get_rp_id(), true);
        if ($rpIdHash !== $expectedRpIdHash) json_err('rpId hash tidak cocok');

        $flags = ord($authDataRaw[32]);
        $up    = ($flags & 0x01) !== 0;
        $uv    = ($flags & 0x04) !== 0;
        if (!$up) json_err('User not present');
        if (!$uv) json_err('User verification failed');

        $signCount = unpack('N', substr($authDataRaw, 33, 4))[1];
        if ($signCount > 0 && $signCount <= (int)$cred['sign_count']) {
            json_err('Sign count tidak valid (kemungkinan cloning)');
        }

        // Verify ECDSA/RSA signature
        $clientDataHash    = hash('sha256', b64u_dec($body['clientDataJSON'] ?? ''), true);
        $verificationData  = $authDataRaw . $clientDataHash;
        $signature         = b64u_dec($body['signature'] ?? '');

        $result = openssl_verify($verificationData, $signature, $cred['public_key'], OPENSSL_ALGO_SHA256);
        if ($result !== 1) json_err('Signature tidak valid. Coba lagi.');

        // Update sign count
        $conn->prepare("UPDATE webauthn_credentials SET sign_count=? WHERE id=?")->execute([$signCount, $cred['id']]);

        // Consume challenge
        unset($_SESSION['wau_login_chal'], $_SESSION['wau_login_uname']);

        // Jika dipanggil dari jalur rahasia, hanya izinkan superadmin utama (id=1)
        if (!empty($body['super_only']) && !($cred['user_type'] === 'super' && (int)$cred['user_id'] === 1)) {
            json_err('Tidak diizinkan.');
        }

        // Login user
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if ($cred['user_type'] === 'super') {
            $sa = $conn->prepare("SELECT * FROM superadmin_accounts WHERE id=? AND is_active=1");
            $sa->execute([$cred['user_id']]);
            $row = $sa->fetch();
            if (!$row) json_err('Akun superadmin tidak aktif');
            $_SESSION['admin_id']     = 0;
            $_SESSION['admin_name']   = $row['nama'] ?: $row['username'];
            $_SESSION['is_super']     = true;
            $_SESSION['super_acc_id'] = (int)$row['id'];
            $redirect = 'superadmin_dashboard.php';
        } else {
            $adm = $conn->prepare("SELECT * FROM admins WHERE id=?");
            $adm->execute([$cred['user_id']]);
            $row = $adm->fetch();
            if (!$row) json_err('Akun admin tidak ditemukan');
            $_SESSION['admin_id']   = (int)$row['id'];
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['is_super']   = false;
            $redirect = 'admin_dashboard.php';
        }

        try {
            $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)")
                 ->execute([$cred['user_id'], 'LOGIN_WEBAUTHN', 'Login via sidik jari: ' . $cred['username'], $ip]);
        } catch (Throwable) {}

        echo json_encode(['ok' => true, 'redirect' => $redirect]);

    } catch (Throwable $e) {
        json_err('Gagal verifikasi: ' . $e->getMessage());
    }
    exit;
}

// ── delete_credential ────────────────────────────────────────────────────────
if ($action === 'delete_credential') {
    if (!isset($_SESSION['admin_id'])) json_err('Belum login', 401);

    $id    = (int)($_POST['id'] ?? 0);
    $utype = !empty($_SESSION['is_super']) ? 'super' : 'admin';
    $uid   = !empty($_SESSION['is_super']) ? (int)($_SESSION['super_acc_id'] ?? 0) : (int)$_SESSION['admin_id'];

    $stmt = $conn->prepare("DELETE FROM webauthn_credentials WHERE id=? AND user_type=? AND user_id=?");
    $stmt->execute([$id, $utype, $uid]);

    if ($stmt->rowCount()) {
        echo json_encode(['ok' => true]);
    } else {
        json_err('Credential tidak ditemukan atau bukan milik Anda');
    }
    exit;
}

// ── list_credentials ─────────────────────────────────────────────────────────
if ($action === 'list_credentials') {
    if (!isset($_SESSION['admin_id'])) json_err('Belum login', 401);

    $utype = !empty($_SESSION['is_super']) ? 'super' : 'admin';
    $uid   = !empty($_SESSION['is_super']) ? (int)($_SESSION['super_acc_id'] ?? 0) : (int)$_SESSION['admin_id'];

    $stmt = $conn->prepare("SELECT id, device_name, created_at FROM webauthn_credentials WHERE user_type=? AND user_id=? ORDER BY created_at DESC");
    $stmt->execute([$utype, $uid]);
    echo json_encode(['ok' => true, 'credentials' => $stmt->fetchAll()]);
    exit;
}

json_err('Action tidak dikenali', 404);
