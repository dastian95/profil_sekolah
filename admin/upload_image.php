<?php
// Endpoint upload gambar untuk panel Konten Website (superadmin)
// POST multipart field "image" → simpan ke assets/uploads/ → JSON {ok, path}
require_once dirname(__DIR__) . '/conn.php';
require_once __DIR__ . '/_constants.php';

header('Content-Type: application/json');
$fail = function (string $m) { echo json_encode(['ok' => false, 'error' => $m]); exit; };

if (empty($_SESSION['is_super']))                                   $fail('Akses ditolak.');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) $fail('Tidak ada file yang dikirim.');

$f = $_FILES['image'];
if ($f['error'] !== UPLOAD_ERR_OK)      $fail('Upload gagal (kode ' . $f['error'] . ').');
if ($f['size'] > 3 * 1024 * 1024)       $fail('Ukuran maksimal 3 MB.');

$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) $fail('Ekstensi tidak diizinkan (jpg/png/webp/gif).');
if (getimagesize($f['tmp_name']) === false)                        $fail('File bukan gambar yang valid.');

$dir = dirname(__DIR__) . '/assets/uploads';
if (!is_dir($dir) && !mkdir($dir, 0755, true)) $fail('Gagal membuat folder uploads.');

$name = 'img_' . date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) $fail('Gagal menyimpan file.');

log_admin_action($conn, 'UPLOAD_IMAGE', "Upload: assets/uploads/$name (" . round($f['size'] / 1024) . " KB)");
echo json_encode(['ok' => true, 'path' => 'assets/uploads/' . $name]);
