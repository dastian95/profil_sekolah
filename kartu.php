<?php
require_once __DIR__ . '/conn.php';

// cegah akses langsung tanpa lewat form
if (!isset($_SESSION['nama_lengkap'])) {
  header("Location: index.php");
  exit;
}

// contoh nomor pendaftaran otomatis
$noDaftar = $_SESSION['no_daftar'] ?? "PPDB-" . rand(100000, 999999);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kartu Peserta PPDB</title>
<link rel="stylesheet" href="css/kartu.css">
</head>
<body>

<div class="kartu" id="kartuPeserta">
  <h2>KARTU PESERTA PPDB 2026<br>SMK LABORATORIUM JAKARTA</h2>

  <div class="row">
    <div class="col data">
      <p><strong>No. Pendaftaran:</strong> <?= $noDaftar ?></p>
      <p><strong>Nama:</strong> <?= $_SESSION['nama_lengkap'] ?></p>
      <p><strong>NISN:</strong> <?= $_SESSION['nisn'] ?></p>
      <p><strong>Jurusan:</strong> <?= $_SESSION['jurusan'] ?></p>
      <p><strong>Lokasi Ujian:</strong>Sekolah Laboratorium Jakarta</p>
      <p><strong>Tanggal Ujian:</strong>15 Juni 2025</p>
    </div>
  </div>
</div>

<button onclick="window.print()" class="btn-print">
  Download / Print Kartu
</button>

</body>
</html>
