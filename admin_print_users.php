<?php
require_once __DIR__ . '/conn.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    // Fetch user data
    $stmt = $conn->prepare("
        SELECT
            u.nisn,
            u.name,
            u.email,
            CASE WHEN u.is_verified = 1 THEN 'Verified' ELSE 'Unverified' END as status_akun,
            dp.asal_sekolah,
            dp.jurusan,
            dp.no_telp_siswa,
            p.status_pendaftaran
        FROM users u
        LEFT JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
        LEFT JOIN pendaftar p ON u.id_pendaftar = p.id_pendaftar
        WHERE u.role = 'user'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Data Pendaftar - SMK Lab Jakarta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0 0; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .text-center { text-align: center; }
        @media print {
            @page { size: landscape; margin: 10mm; }
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>LAPORAN DATA PENDAFTAR PPDB</h1>
        <p>SMK LABORATORIUM JAKARTA</p>
        <p>Tahun Ajaran <?php echo date('Y') . '/' . (date('Y')+1); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>NISN</th>
                <th>Nama Lengkap</th>
                <th>Email</th>
                <th>Asal Sekolah</th>
                <th>Jurusan</th>
                <th>No. Telp</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($users as $user): ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($user['nisn'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($user['name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['asal_sekolah'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($user['jurusan'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($user['no_telp_siswa'] ?? '-'); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($user['status_pendaftaran'] ?? 'Draft'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>