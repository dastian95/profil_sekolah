<?php
require_once __DIR__ . '/conn.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$date_filter_sql = "";
$params = [];

if (!empty($start_date) && !empty($end_date)) {
    $date_filter_sql = " AND u.created_at BETWEEN ? AND ? ";
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
}

try {
    // 1. Statistik Jurusan & Jalur
    $sql1 = "SELECT dp.jurusan, dp.jalur_daftar, COUNT(*) as total
             FROM data_peserta dp
             JOIN users u ON dp.id_pendaftar = u.id_pendaftar
             WHERE dp.jurusan IS NOT NULL AND dp.jurusan != '' $date_filter_sql
             GROUP BY dp.jurusan, dp.jalur_daftar
             ORDER BY dp.jurusan ASC, total DESC";
    $stmt = $conn->prepare($sql1);
    $stmt->execute($params);
    $jurusan_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Statistik Asal Sekolah (Ranking Lengkap)
    $sql2 = "SELECT dp.asal_sekolah, COUNT(*) as total
             FROM data_peserta dp
             JOIN users u ON dp.id_pendaftar = u.id_pendaftar
             WHERE dp.asal_sekolah IS NOT NULL AND dp.asal_sekolah != '' $date_filter_sql
             GROUP BY dp.asal_sekolah
             ORDER BY total DESC";
    $stmt = $conn->prepare($sql2);
    $stmt->execute($params);
    $school_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Statistik Daerah (Kota/Kabupaten)
    $sql3 = "SELECT dp.kota, COUNT(*) as total
             FROM data_peserta dp
             JOIN users u ON dp.id_pendaftar = u.id_pendaftar
             WHERE dp.kota IS NOT NULL AND dp.kota != '' $date_filter_sql
             GROUP BY dp.kota
             ORDER BY total DESC";
    $stmt = $conn->prepare($sql3);
    $stmt->execute($params);
    $city_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "laporan_analisis_ppdb_" . date('Y-m-d_H-i') . ".xls";

    // Headers for Excel download
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
            body { font-family: Arial, sans-serif; }
            table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
            th, td { border: 1px solid #000000; padding: 8px; text-align: left; vertical-align: top; }
            th { background-color: #4CAF50; color: white; font-weight: bold; text-align: center; }
            h2, h3 { margin-top: 20px; margin-bottom: 10px; }
            .text-center { text-align: center; }
        </style>
    </head>
    <body>
        <h2 class="text-center">LAPORAN ANALISIS DATA PPDB</h2>
        <p class="text-center">SMK Laboratorium Jakarta - Tanggal: <?php echo date('d F Y'); ?></p>
        <?php if(!empty($start_date)): ?>
            <p class="text-center">Periode: <?php echo $start_date; ?> s/d <?php echo $end_date; ?></p>
        <?php endif; ?>
        <br>

        <h3>1. Statistik Peminatan Jurusan & Jalur</h3>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Jurusan</th>
                    <th>Jalur Pendaftaran</th>
                    <th>Jumlah Pendaftar</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($jurusan_stats)): ?>
                    <tr><td colspan="4" class="text-center">Tidak ada data.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach($jurusan_stats as $row): ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['jurusan']); ?></td>
                        <td><?php echo htmlspecialchars($row['jalur_daftar'] ?: 'Umum'); ?></td>
                        <td class="text-center"><?php echo $row['total']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h3>2. Statistik Asal Sekolah (Ranking)</h3>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Nama Sekolah Asal</th>
                    <th>Jumlah Pendaftar</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($school_stats)): ?>
                    <tr><td colspan="3" class="text-center">Tidak ada data.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach($school_stats as $row): ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['asal_sekolah']); ?></td>
                        <td class="text-center"><?php echo $row['total']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <h3>3. Statistik Sebaran Wilayah (Kota/Kabupaten)</h3>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>Kota / Kabupaten</th>
                    <th>Jumlah Pendaftar</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($city_stats)): ?>
                    <tr><td colspan="3" class="text-center">Tidak ada data.</td></tr>
                <?php else: ?>
                    <?php $no = 1; foreach($city_stats as $row): ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($row['kota']); ?></td>
                        <td class="text-center"><?php echo $row['total']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </body>
    </html>
    <?php
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>