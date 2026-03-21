<?php
require_once __DIR__ . '/conn.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    // Redirect to login or show an error
    header("Location: login.php");
    exit;
}

try {
    // Fetch user data with all profile details
    $stmt = $conn->prepare("
        SELECT
            u.nisn,
            u.name,
            u.email,
            CASE WHEN u.is_verified = 1 THEN 'Verified' ELSE 'Unverified' END as status_akun,
            dp.tanggal_lahir,
            dp.asal_sekolah,
            dp.npsn,
            dp.no_telp_siswa,
            dp.no_telp_ortu,
            dp.jurusan,
            dp.alamat,
            dp.Kecamatan,
            dp.kota,
            dp.provinsi,
            p.status_pendaftaran
        FROM users u
        LEFT JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
        LEFT JOIN pendaftar p ON u.id_pendaftar = p.id_pendaftar
        WHERE u.role = 'user'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "data_pendaftar_" . date('Y-m-d') . ".xls";

    // Set headers to force download as an Excel file
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: Arial, sans-serif;
            }

            table {
                border-collapse: collapse;
                width: 100%;
            }

            th,
            td {
                border: 1px solid #000000;
                padding: 8px;
                text-align: left;
                vertical-align: top;
            }

            th {
                background-color: #4CAF50;
                color: white;
                font-weight: bold;
                text-align: center;
            }

            tr:nth-child(even) {
                background-color: #f2f2f2;
            }

            .text-center {
                text-align: center;
            }
        </style>
    </head>

    <body>
        <h2 style="text-align: center; margin-bottom: 20px;">Data Pendaftar SMK Laboratorium Jakarta</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>NISN</th>
                    <th>Nama Lengkap</th>
                    <th>Email</th>
                    <th>Status Akun</th>
                    <th>Tanggal Lahir</th>
                    <th>Asal Sekolah</th>
                    <th>NPSN</th>
                    <th>Telepon Siswa</th>
                    <th>Telepon Ortu</th>
                    <th>Jurusan</th>
                    <th>Alamat</th>
                    <th>Kecamatan</th>
                    <th>Kota</th>
                    <th>Provinsi</th>
                    <th>Status Pendaftaran Dokumen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php $no = 1;
                    foreach ($users as $user): ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td style="mso-number-format:'@'"><?php echo htmlspecialchars($user['nisn'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['status_akun'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['tanggal_lahir'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['asal_sekolah'] ?? ''); ?></td>
                            <td style="mso-number-format:'@'"><?php echo htmlspecialchars($user['npsn'] ?? ''); ?></td>
                            <td style="mso-number-format:'@'"><?php echo htmlspecialchars($user['no_telp_siswa'] ?? ''); ?></td>
                            <td style="mso-number-format:'@'"><?php echo htmlspecialchars($user['no_telp_ortu'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['jurusan'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['alamat'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['Kecamatan'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['kota'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['provinsi'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($user['status_pendaftaran'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="15" class="text-center">Belum ada data pendaftar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </body>

    </html>
<?php
    exit();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
