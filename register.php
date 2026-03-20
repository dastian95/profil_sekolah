<?php
require_once __DIR__ . '/../src/conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Invalid request method';
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$captcha = trim($_POST['captcha'] ?? '');
$password = $_POST['password'] ?? '';
$password_verify = $_POST['password_verify'] ?? '';

if (empty($name) || empty($email) || empty($password) || empty($password_verify) || empty($captcha)) {
    echo 'All fields are required';
    exit;
}

if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
    echo 'Nama hanya boleh berisi huruf dan spasi';
    exit;
}

if (strlen($password) < 8) {
    echo 'Password must be at least 8 characters long';
    exit;
}

if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    echo 'Password must contain at least one uppercase letter and one number';
    exit;
}

if ($password !== $password_verify) {
    echo 'Passwords do not match';
    exit;
}

// Verify Captcha
if (!isset($_SESSION['captcha']) || strtolower($captcha) !== strtolower($_SESSION['captcha'])) {
    echo 'Invalid Captcha Code';
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo 'Invalid email format';
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo 'Email already registered';
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$token = md5(uniqid(mt_rand(), true));

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, verification_token, is_verified) VALUES (?, ?, ?, 'user', ?, 0)");
if (!$stmt->execute([$name, $email, $hashed_password, $token])) {
    echo 'Registration failed';
    exit;
}

// Kirim Email Verifikasi
$mail = new PHPMailer(true);
try {
    // Server settings (Laragon MailHog)
    $mail->isSMTP();
    $mail->Host       = 'localhost';
    $mail->SMTPAuth   = false;
    $mail->Port       = 1025;
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = filter_var($_ENV['SMTP_AUTH'], FILTER_VALIDATE_BOOLEAN);
    $mail->Port       = $_ENV['SMTP_PORT'];

    $mail->setFrom('admin@smklab.sch.id', 'SMK Lab Jakarta');
    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($email, $name);

    $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=" . $token;
    $link = rtrim($_ENV['APP_URL'], '/') . "/verify.php?token=" . $token;

    $mail->isHTML(true);
    $mail->Subject = 'Verifikasi Akun - SMK Lab Jakarta';
    $mail->Body    = "Halo $name,<br><br>Terima kasih telah mendaftar. Silakan klik link berikut untuk memverifikasi akun Anda:<br><a href='$link'>$link</a>";

    $mail->send();
} catch (Exception $e) {
    // Lanjut saja meski email gagal (opsional: log error)
}

echo "OK";
?>