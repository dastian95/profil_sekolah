<?php
require_once __DIR__ . '/../src/conn.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$school_name = $_GET['school'] ?? '';
if (empty($school_name)) {
    die("Nama sekolah tidak ditemukan.");
}

try {
    $stmt = $conn->prepare("
        SELECT dp.nama, dp.nisn, dp.jurusan, dp.no_telp_siswa, p.status_pendaftaran 
        FROM data_peserta dp 
        LEFT JOIN pendaftar p ON dp.id_pendaftar = p.id_pendaftar 
        WHERE dp.asal_sekolah = ?
        ORDER BY dp.nama ASC
    ");
    $stmt->execute([$school_name]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Siswa - <?php echo htmlspecialchars($school_name); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header h2 { margin: 5px 0 0; font-size: 14px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        @media print {
            @page { margin: 10mm; }
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>DAFTAR PENDAFTAR PPDB</h1>
        <h2>Asal Sekolah: <strong><?php echo htmlspecialchars($school_name); ?></strong></h2>
        <p style="margin: 5px 0 0; font-size: 10px;">Dicetak pada: <?php echo date('d F Y H:i'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Lengkap</th>
                <th>NISN</th>
                <th>Jurusan Pilihan</th>
                <th>No. Telepon</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($students) > 0): ?>
                <?php $no = 1; foreach ($students as $s): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($s['nama']); ?></td>
                    <td><?php echo htmlspecialchars($s['nisn']); ?></td>
                    <td><?php echo htmlspecialchars($s['jurusan']); ?></td>
                    <td><?php echo htmlspecialchars($s['no_telp_siswa']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($s['status_pendaftaran'] ?? 'Draft'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">Tidak ada data siswa dari sekolah ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>