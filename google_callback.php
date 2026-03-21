<?php
require_once __DIR__ . '/conn.php';

// google_config.php is no longer needed, we load from .env
$google_client = new Google_Client();
$google_client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$google_client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$google_client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$google_client->addScope('email');
$google_client->addScope('profile');

if (isset($_GET['code'])) {
    try {
        $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (!isset($token['error'])) {
            $google_service = new Google_Service_Oauth2($google_client);
            $data = $google_service->userinfo->get();

            $email = $data['email'];
            $name = $data['name'];

            // --- Domain Restriction Logic ---
            $allowed_domain = $_ENV['ALLOWED_LOGIN_DOMAIN']; // Diambil dari .env
            
            // Cek apakah email berakhiran dengan domain yang diizinkan
            if (substr($email, -strlen('@' . $allowed_domain)) !== '@' . $allowed_domain) {
                 die('<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">
                        <h2 style="color: red;">Akses Ditolak</h2>
                        <p>Maaf, hanya email dengan domain <strong>@' . $allowed_domain . '</strong> yang diperbolehkan login.</p>
                        <p>Email Anda: ' . htmlspecialchars($email) . '</p>
                        <a href="login.php" style="background: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Kembali ke Login</a>
                      </div>');
            }
            // --------------------------------

            // Cek apakah email sudah terdaftar
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Cek Status Ban
                if (isset($user['is_banned']) && $user['is_banned'] == 1) {
                    die('<div style="font-family: sans-serif; text-align: center; margin-top: 50px;"><h2 style="color: red;">Akses Ditolak</h2><p>Akun Anda telah diblokir sementara. Silakan hubungi admin.</p><a href="login.php" style="background: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Kembali ke Login</a></div>');
                }

                // User ada, login langsung
                $_SESSION['user_id'] = $user['id_pendaftar'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                
                // Jika user lama belum verifikasi, otomatis verifikasi karena login via Google (email valid)
                if ($user['is_verified'] == 0) {
                    $upd = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id_pendaftar = ?");
                    $upd->execute([$user['id_pendaftar']]);
                }

                $redirect = ($user['role'] === 'admin') ? 'admin_dashboard.php' : 'dashboard.php';
                header("Location: $redirect");
                exit;
            } else {
                // User belum ada, buat akun baru otomatis
                // Password random karena login via Google
                $random_password = bin2hex(random_bytes(8)); 
                $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, 'user', 1)");
                if ($stmt->execute([$name, $email, $hashed_password])) {
                    $new_id = $conn->lastInsertId();
                    
                    $_SESSION['user_id'] = $new_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'user';
                    $_SESSION['user_email'] = $email;

                    header("Location: dashboard.php");
                    exit;
                } else {
                    die("Gagal membuat akun Google.");
                }
            }
        }
    } catch (Exception $e) {
        die("Google Login Error: " . $e->getMessage());
    }
}
header("Location: login.php");
exit;
?>