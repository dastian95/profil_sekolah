<?php
require_once __DIR__ . '/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch Data
try {
    // Total Stats
    $total_users = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $verified_users = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND is_verified = 1")->fetchColumn();
    $total_apps = $conn->query("SELECT COUNT(*) FROM pendaftar")->fetchColumn();
    $submitted_apps = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status_pendaftaran = 'Terkirim'")->fetchColumn();

    // City Stats
    $city_stats = $conn->query("SELECT kota, COUNT(*) as total FROM data_peserta WHERE kota IS NOT NULL AND kota != '' GROUP BY kota ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Jurusan Stats
    $jurusan_stats = $conn->query("SELECT jurusan, COUNT(*) as total FROM data_peserta WHERE jurusan IS NOT NULL AND jurusan != '' GROUP BY jurusan ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laporan Statistik PPDB</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; }
        .section { margin-bottom: 30px; }
        .section h3 { border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .summary-box { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .box { border: 1px solid #ddd; padding: 15px; width: 22%; text-align: center; background: #f9f9f9; }
        .box h2 { margin: 10px 0 0; }
        @media print {
            @page { margin: 10mm; }
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>LAPORAN STATISTIK PPDB</h1>
        <p>SMK LABORATORIUM JAKARTA</p>
        <p>Tanggal Cetak: <?php echo date('d F Y'); ?></p>
    </div>

    <div class="section">
        <h3>Ringkasan Pendaftaran</h3>
        <div class="summary-box">
            <div class="box">
                <span>Total User</span>
                <h2><?php echo $total_users; ?></h2>
            </div>
            <div class="box">
                <span>Terverifikasi</span>
                <h2><?php echo $verified_users; ?></h2>
            </div>
            <div class="box">
                <span>Total Pendaftar</span>
                <h2><?php echo $total_apps; ?></h2>
            </div>
            <div class="box">
                <span>Dokumen Lengkap</span>
                <h2><?php echo $submitted_apps; ?></h2>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Statistik Peminatan Jurusan</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Jurusan</th>
                    <th>Jumlah Pendaftar</th>
                    <th>Persentase</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; 
                $total_j = array_sum(array_column($jurusan_stats, 'total'));
                foreach ($jurusan_stats as $row): 
                    $percent = $total_j > 0 ? round(($row['total'] / $total_j) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['jurusan']); ?></td>
                    <td><?php echo $row['total']; ?></td>
                    <td><?php echo $percent; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Statistik Sebaran Wilayah (Kota)</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kota / Kabupaten</th>
                    <th>Jumlah Pendaftar</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($city_stats as $row): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['kota']); ?></td>
                    <td><?php echo $row['total']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>