<?php
// File ini harus di-include di dalam layout admin utama Anda,
// mirip dengan cara kerja admin_manage_users.php.
// Pastikan variabel koneksi database $conn sudah tersedia.

// Memastikan hanya admin yang bisa mengakses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die('Akses Ditolak. Anda harus login sebagai admin.');
}

// --- Konfigurasi Kuota ---
$total_slots = 108;
$quotas = [
    'duren_sawit' => [
        'name' => 'Jalur Zonasi Prioritas 1 (Kec. Duren Sawit)',
        'slots' => floor($total_slots * 0.50), // 54
        'candidates' => [],
        'total_found' => 0,
    ],
    'jakarta_timur' => [
        'name' => 'Jalur Zonasi Prioritas 2 (Kota Jakarta Timur)',
        'slots' => floor($total_slots * 0.30), // 32
        'candidates' => [],
        'total_found' => 0,
    ],
    'dki_jakarta' => [
        'name' => 'Jalur Zonasi Prioritas 3 (Prov. DKI Jakarta)',
        'slots' => floor($total_slots * 0.20), // 21
        'candidates' => [],
        'total_found' => 0,
    ],
];

// Menyesuaikan kuota terakhir untuk mengisi sisa slot akibat pembulatan
$assigned_slots = $quotas['duren_sawit']['slots'] + $quotas['jakarta_timur']['slots'] + $quotas['dki_jakarta']['slots'];
$remaining_slots = $total_slots - $assigned_slots;
if ($remaining_slots > 0) {
    $quotas['dki_jakarta']['slots'] += $remaining_slots; // Menjadi 22
}

$selected_ids = [0]; // Nilai awal untuk mencegah error SQL pada klausa IN() yang kosong

try {
    // --- 1. Ambil Kandidat Duren Sawit ---
    $key = 'duren_sawit';
    $sql = "
        SELECT u.id_pendaftar, u.name, u.nisn, dp.asal_sekolah, dp.Kecamatan, dp.kota, dp.provinsi, p.tanggal_daftar
        FROM users u
        JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
        JOIN pendaftar p ON u.id_pendaftar = p.id_pendaftar
        WHERE dp.Kecamatan = 'DUREN SAWIT'
          AND u.role = 'user' AND u.is_verified = 1 AND p.status_pendaftaran = 'Terkirim'
        ORDER BY p.tanggal_daftar ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $all_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $quotas[$key]['total_found'] = count($all_candidates);
    $quotas[$key]['candidates'] = array_slice($all_candidates, 0, $quotas[$key]['slots']);
    foreach ($quotas[$key]['candidates'] as $candidate) {
        $selected_ids[] = $candidate['id_pendaftar'];
    }

    // --- 2. Ambil Kandidat Jakarta Timur ---
    $key = 'jakarta_timur';
    $sql = "
        SELECT u.id_pendaftar, u.name, u.nisn, dp.asal_sekolah, dp.Kecamatan, dp.kota, dp.provinsi, p.tanggal_daftar
        FROM users u
        JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
        JOIN pendaftar p ON u.id_pendaftar = p.id_pendaftar
        WHERE dp.kota = 'KOTA JAKARTA TIMUR'
          AND u.role = 'user' AND u.is_verified = 1 AND p.status_pendaftaran = 'Terkirim'
          AND u.id_pendaftar NOT IN (" . implode(',', $selected_ids) . ")
        ORDER BY p.tanggal_daftar ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $all_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $quotas[$key]['total_found'] = count($all_candidates);
    $quotas[$key]['candidates'] = array_slice($all_candidates, 0, $quotas[$key]['slots']);
    foreach ($quotas[$key]['candidates'] as $candidate) {
        $selected_ids[] = $candidate['id_pendaftar'];
    }

    // --- 3. Ambil Kandidat DKI Jakarta ---
    $key = 'dki_jakarta';
    $sql = "
        SELECT u.id_pendaftar, u.name, u.nisn, dp.asal_sekolah, dp.Kecamatan, dp.kota, dp.provinsi, p.tanggal_daftar
        FROM users u
        JOIN data_peserta dp ON u.id_pendaftar = dp.id_pendaftar
        JOIN pendaftar p ON u.id_pendaftar = p.id_pendaftar
        WHERE dp.provinsi = 'DKI JAKARTA'
          AND u.role = 'user' AND u.is_verified = 1 AND p.status_pendaftaran = 'Terkirim'
          AND u.id_pendaftar NOT IN (" . implode(',', $selected_ids) . ")
        ORDER BY p.tanggal_daftar ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $all_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $quotas[$key]['total_found'] = count($all_candidates);
    $quotas[$key]['candidates'] = array_slice($all_candidates, 0, $quotas[$key]['slots']);

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark fw-bold"><i class="bi bi-funnel-fill me-2 text-primary"></i>Alat Seleksi Siswa Berdasarkan Kuota Zonasi</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <h6 class="alert-heading"><i class="bi bi-info-circle-fill"></i> Informasi Sistem Seleksi</h6>
            <p class="mb-1">Sistem ini secara otomatis memilih kandidat siswa yang lolos berdasarkan kuota zonasi. Total penerimaan adalah <strong><?php echo $total_slots; ?> siswa</strong>.</p>
            <ul class="mb-0">
                <li><strong>Prioritas 1:</strong> <strong><?php echo $quotas['duren_sawit']['slots']; ?> siswa</strong> dari Kecamatan Duren Sawit.</li>
                <li><strong>Prioritas 2:</strong> <strong><?php echo $quotas['jakarta_timur']['slots']; ?> siswa</strong> dari Kota Jakarta Timur (di luar Duren Sawit).</li>
                <li><strong>Prioritas 3:</strong> <strong><?php echo $quotas['dki_jakarta']['slots']; ?> siswa</strong> dari Provinsi DKI Jakarta (di luar Prioritas 1 & 2).</li>
            </ul>
            <hr>
            <p class="mb-0 small text-muted">Kriteria: Pendaftar yang telah <strong>terverifikasi</strong>, status pendaftaran <strong>'Terkirim'</strong>, dan diurutkan berdasarkan waktu daftar tercepat.</p>
        </div>

        <?php foreach ($quotas as $key => $quota): ?>
            <div class="card mb-4">
                <div class="card-header"><h6 class="mb-0"><?php echo htmlspecialchars($quota['name']); ?></h6></div>
                <div class="card-body">
                    <p>Kuota Terpenuhi: <strong><?php echo count($quota['candidates']); ?> / <?php echo $quota['slots']; ?></strong> siswa. <span class="ms-2 text-muted small">(Ditemukan total <?php echo $quota['total_found']; ?> kandidat)</span></p>
                    <?php if (count($quota['candidates']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead class="table-light">
                                    <tr><th>#</th><th>Nama Lengkap</th><th>NISN</th><th>Asal Sekolah</th><th>Tanggal Daftar</th><th>Lokasi</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quota['candidates'] as $index => $c): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                                            <td><?php echo htmlspecialchars($c['nisn']); ?></td>
                                            <td><?php echo htmlspecialchars($c['asal_sekolah']); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($c['tanggal_daftar'])); ?></td>
                                            <td><small>Kec. <?php echo htmlspecialchars($c['Kecamatan']); ?>, <?php echo htmlspecialchars($c['kota']); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center">Tidak ada kandidat yang terpilih untuk kuota ini.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>