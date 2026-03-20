<?php
require_once __DIR__ . '/../src/conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Log FILES for debugging
    file_put_contents('debug.log', "FILES: " . print_r($_FILES, true) . "\n", FILE_APPEND);
    file_put_contents('debug.log', "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);

    // Get form data
    $nama_lengkap = $_POST['nama_lengkap'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $asal_sekolah = $_POST['asal_sekolah'];
    $nisn = $_POST['nisn'];
    $email = $_POST['email'];
    $no_telp_ortu = $_POST['no_telp_ortu'];
    $no_telp_siswa = $_POST['no_telp_siswa'];
    $alamat = $_POST['alamat'];
    $jurusan = $_POST['jurusan'];
    $foto = $_FILES['foto']['name'] ?? '';
    if (!empty($foto)) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'img'];
        $ext = strtolower(pathinfo($foto, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            file_put_contents('error.log', "Invalid file type: $ext\n");
            header("Location: index.php?error=2");
            exit();
        }
    }
    $kecamatan = $_POST['kecamatan'];
    $kota = $_POST['kota'];
    $provinsi = $_POST['provinsi'];
    // Map jurusan codes to full names for display
    $jurusan_options = [
        'rpl' => 'Rekayasa Perangkat Lunak (RPL)',
        'tkj' => 'Teknik Komputer dan Jaringan (TKJ)',
        'dkv' => 'Asisten Keperawatan (AP)',
        'akl' => 'Tata Kecantikan Kulit dan Rambut (TKKR)'
    ];
    $jurusan_display = isset($jurusan_options[$jurusan]) ? $jurusan_options[$jurusan] : $jurusan;

    // Prepare SQL statement
    $sql = "INSERT INTO data_peserta (nama, tanggal_lahir, asal_sekolah, nisn, email, no_telp_ortu, no_telp_siswa, alamat, jurusan, foto, Kecamatan, kota, provinsi)
            VALUES (:nama_lengkap, :tanggal_lahir, :asal_sekolah, :nisn, :email, :no_telp_ortu, :no_telp_siswa, :alamat, :jurusan_display, :foto, :kecamatan, :kota, :provinsi)";

    $stmt = $conn->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
    $stmt->bindParam(':tanggal_lahir', $tanggal_lahir);
    $stmt->bindParam(':asal_sekolah', $asal_sekolah);
    $stmt->bindParam(':nisn', $nisn);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':no_telp_ortu', $no_telp_ortu);
    $stmt->bindParam(':no_telp_siswa', $no_telp_siswa);
    $stmt->bindParam(':alamat', $alamat);
    $stmt->bindParam(':jurusan_display', $jurusan_display);
    $stmt->bindParam(':foto', $foto);
    $stmt->bindParam(':kecamatan', $kecamatan);
    $stmt->bindParam(':kota', $kota);
    $stmt->bindParam(':provinsi', $provinsi);

    try {
        $stmt->execute();
        $last_id = $conn->lastInsertId();

        file_put_contents('debug.log', "Insert successful, id: $last_id, foto: $foto\n", FILE_APPEND);

        // Generate registration number (e.g., PPDB2026 + ID)
        $no_daftar = "PPDB2026" . str_pad($last_id, 4, '0', STR_PAD_LEFT);

        // Upload foto if provided
        if (!empty($foto)) {
            $upload_success = move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto);
            file_put_contents('debug.log', "Upload success: " . ($upload_success ? 'yes' : 'no') . "\n", FILE_APPEND);
        }

        // Save to session
        $_SESSION['nama_lengkap'] = $nama_lengkap;
        $_SESSION['nisn'] = $nisn;
        $_SESSION['jurusan'] = $jurusan_display;
        $_SESSION['no_daftar'] = $no_daftar;

    } catch(PDOException $e) {
        // Log error
        file_put_contents('error.log', $e->getMessage());
        // Redirect to index with error
        header("Location: index.php?error=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Memproses Pendaftaran...</title>

<style>
  body {
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background: #e3f2e1;
    font-family: Arial, sans-serif;
    overflow: hidden;
  }

  /* Container untuk efek mask */
  .logo-container {
    width: 150px;
    overflow: hidden;
  }

  /* Logo */
  .logo {
    width: 150px;
    opacity: 0;
    animation: reveal 2.5s ease forwards;
  }

  /* animasi reveal dari kiri + fade-in */
  @keyframes reveal {
    0% {
      clip-path: inset(0 100% 0 0);
      opacity: 0;
    }
    100% {
      clip-path: inset(0 0 0 0);
      opacity: 1;
    }
  }

  .text {
    position: absolute;
    bottom: 80px;
    font-size: 18px;
    color: #006c3c;
  }
</style>
</head>

<body>

<!-- ANIMASI LOGO REVEAL -->
<div class="logo-container">
  <img src="assets/smk.png" class="logo" alt="Logo Sekolah">
</div>

<div class="text">Sedang memproses pendaftaran...</div>

<script>
  // setelah animasi selesai, pindah ke halaman kartu
  setTimeout(() => {
    window.location.href = "kartu.php";
  }, 2500);
</script>

</body>
</html>