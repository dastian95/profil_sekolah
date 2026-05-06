<?php
// Export CSV langsung jika diminta
if (isset($_GET['export'])) {
    $gelombang = $_GET['gelombang'] ?? '';
    $jurusan   = $_GET['jurusan']   ?? '';
    $status    = $_GET['status']    ?? '';

    $where = ['1=1']; $params = [];
    if ($gelombang) { $where[] = 'gelombang=?'; $params[] = $gelombang; }
    if ($jurusan)   { $where[] = 'jurusan=?';   $params[] = $jurusan; }
    if ($status)    { $where[] = 'status=?';    $params[] = $status; }

    $stmt = $conn->prepare("SELECT no_pendaftaran,gelombang,nama,nisn,tanggal_lahir,usia,jenis_kelamin,
        asal_sekolah,no_telp,alamat,jurusan,nilai_raport,nilai_tka,nilai_akhir,lolos_usia,status,catatan,created_at
        FROM pendaftar WHERE " . implode(' AND ', $where) . " ORDER BY gelombang, jurusan, nilai_akhir DESC, usia DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $filename = 'pendaftar_ppdb_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['No Pendaftaran','Gelombang','Nama','NISN','Tgl Lahir','Usia','L/P',
        'Asal Sekolah','No Telp','Alamat','Jurusan','Nilai Raport','Nilai TKA',
        'Nilai Akhir','Lolos Usia','Status','Catatan','Waktu Daftar']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['no_pendaftaran'], $r['gelombang'], $r['nama'], $r['nisn'],
            $r['tanggal_lahir'], $r['usia'], $r['jenis_kelamin'],
            $r['asal_sekolah'], $r['no_telp'], $r['alamat'], $r['jurusan'],
            $r['nilai_raport'], $r['nilai_tka'], $r['nilai_akhir'],
            $r['lolos_usia'] ? 'Ya' : 'Tidak (>21thn)',
            $r['status'], $r['catatan'], $r['created_at'],
        ]);
    }
    fclose($out);

    $log = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
    $log->execute([$_SESSION['admin_id'], 'EXPORT_CSV', "Export CSV: {$filename}", $_SERVER['REMOTE_ADDR']]);
    exit;
}

// Statistik ringkas
$total      = $conn->query("SELECT COUNT(*) FROM pendaftar")->fetchColumn();
$diterima   = $conn->query("SELECT COUNT(*) FROM pendaftar WHERE status='diterima'")->fetchColumn();
$gel_rows   = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();
$jurusan_list = [
    'Rekayasa Perangkat Lunak (RPL)',
    'Teknik Komputer dan Jaringan (TKJ)',
    'Asisten Keperawatan (AP)',
    'Tata Kecantikan Kulit dan Rambut (TKKR)',
];
?>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Export data dalam format <strong>CSV</strong> — dapat dibuka dengan Microsoft Excel atau Google Sheets.
    Data diurutkan per gelombang, jurusan, dan nilai akhir tertinggi.
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="fs-2 fw-bold text-primary"><?= $total ?></div>
                <div class="text-muted small">Total Pendaftar</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="fs-2 fw-bold text-success"><?= $diterima ?></div>
                <div class="text-muted small">Total Diterima</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="fs-2 fw-bold text-info"><?= count($jurusan_list) * array_sum(array_column($gel_rows, 'kuota_per_jurusan')) / max(count($gel_rows),1) ?></div>
                <div class="text-muted small">Total Kuota</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Export Data CSV</div>
    <div class="card-body">
        <form method="GET" target="_blank">
            <input type="hidden" name="page" value="backup">
            <input type="hidden" name="export" value="1">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Gelombang</label>
                    <select name="gelombang" class="form-select">
                        <option value="">Semua Gelombang</option>
                        <option value="1">Gelombang 1</option>
                        <option value="2">Gelombang 2</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jurusan</label>
                    <select name="jurusan" class="form-select">
                        <option value="">Semua Jurusan</option>
                        <?php foreach ($jurusan_list as $j): ?>
                        <option value="<?= htmlspecialchars($j) ?>"><?= htmlspecialchars($j) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="diterima">Diterima</option>
                        <option value="ditolak">Ditolak</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-download me-1"></i>Download CSV
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
