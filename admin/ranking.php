<?php
$jurusan_list = [
    'Rekayasa Perangkat Lunak (RPL)',
    'Teknik Komputer dan Jaringan (RPL)',
    'Teknik Komputer dan Jaringan (TKJ)',
    'Asisten Keperawatan (AP)',
    'Tata Kecantikan Kulit dan Rambut (TKKR)',
];
$jurusan_list = [
    'Rekayasa Perangkat Lunak (RPL)',
    'Teknik Komputer dan Jaringan (TKJ)',
    'Asisten Keperawatan (AP)',
    'Tata Kecantikan Kulit dan Rambut (TKKR)',
];
$short = ['Rekayasa Perangkat Lunak (RPL)'=>'RPL','Teknik Komputer dan Jaringan (TKJ)'=>'TKJ','Asisten Keperawatan (AP)'=>'AP','Tata Kecantikan Kulit dan Rambut (TKKR)'=>'TKKR'];

$msg = '';

// Ambil konfigurasi gelombang
$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();
$gel_map  = [];
foreach ($gel_rows as $g) $gel_map[$g['gelombang']] = $g;

// ── Proses Penerimaan ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'proses') {
    $gelombang = (int)$_POST['gelombang'];
    $g         = $gel_map[$gelombang] ?? null;

    if (!$g) {
        $msg = '<div class="alert alert-danger">Gelombang tidak ditemukan.</div>';
    } else {
        $kuota_total  = (int)$g['kuota_per_jurusan'];
        $persen       = (float)$g['persen_gelombang'];
        $kuota_glm    = (int)round($kuota_total * $persen / 100);

        // Reset status pendaftar di gelombang ini ke 'pending' dulu
        $conn->prepare("UPDATE pendaftar SET status='pending' WHERE gelombang=?")->execute([$gelombang]);

        // Gugur usia > 21 langsung ditolak
        $conn->prepare("UPDATE pendaftar SET status='ditolak', catatan='Gugur: usia melebihi 21 tahun'
                        WHERE gelombang=? AND lolos_usia=0")->execute([$gelombang]);

        $total_diterima = 0;
        foreach ($jurusan_list as $jurusan) {
            // Ambil semua yang lolos usia, urut nilai_akhir DESC, usia DESC (lebih tua prioritas sama nilai)
            $stmt = $conn->prepare("SELECT id FROM pendaftar
                WHERE gelombang=? AND jurusan=? AND lolos_usia=1
                ORDER BY nilai_akhir DESC, usia DESC");
            $stmt->execute([$gelombang, $jurusan]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $terima_ids = array_slice($ids, 0, $kuota_glm);
            $tolak_ids  = array_slice($ids, $kuota_glm);

            if ($terima_ids) {
                $in = implode(',', array_fill(0, count($terima_ids), '?'));
                $conn->prepare("UPDATE pendaftar SET status='diterima', catatan=NULL WHERE id IN ($in)")
                     ->execute($terima_ids);
                $total_diterima += count($terima_ids);
            }
            if ($tolak_ids) {
                $in = implode(',', array_fill(0, count($tolak_ids), '?'));
                $conn->prepare("UPDATE pendaftar SET status='ditolak', catatan='Nilai tidak mencapai kuota' WHERE id IN ($in)")
                     ->execute($tolak_ids);
            }
        }

        $log = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
        $log->execute([$_SESSION['admin_id'], 'PROSES_RANKING',
            "Proses penerimaan Gelombang {$gelombang}: {$total_diterima} diterima", $_SERVER['REMOTE_ADDR']]);

        $msg = "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i>
            Proses penerimaan Gelombang <strong>{$gelombang}</strong> selesai.
            <strong>{$total_diterima}</strong> pendaftar diterima dari seluruh jurusan.</div>";
    }
}

// ── Filter tampilan ───────────────────────────────────────────────────────────
$fGel     = $_GET['gelombang'] ?? '1';
$fJurusan = $_GET['jurusan']   ?? '';
$g        = $gel_map[(int)$fGel] ?? null;

$kuota_glm = $g ? (int)round($g['kuota_per_jurusan'] * $g['persen_gelombang'] / 100) : 0;
?>

<?= $msg ?>

<!-- Pilih Gelombang Tampil -->
<div class="d-flex flex-wrap gap-2 align-items-center mb-4">
    <span class="fw-semibold">Tampilkan Gelombang:</span>
    <?php foreach ($gel_rows as $g_btn): ?>
    <a href="?page=ranking&gelombang=<?= $g_btn['gelombang'] ?>&jurusan=<?= urlencode($fJurusan) ?>"
       class="btn btn-sm <?= (int)$fGel===$g_btn['gelombang']?'btn-success':'btn-outline-success' ?>">
        Gelombang <?= $g_btn['gelombang'] ?>
    </a>
    <?php endforeach; ?>

    <div class="ms-auto">
        <?php if ($g): ?>
        <form method="POST" class="d-inline" onsubmit="return confirm('Proses penerimaan Gelombang <?= $fGel ?>? Status semua pendaftar gelombang ini akan di-reset dan dihitung ulang.')">
            <input type="hidden" name="action" value="proses">
            <input type="hidden" name="gelombang" value="<?= $fGel ?>">
            <button type="submit" class="btn btn-warning btn-sm">
                <i class="bi bi-calculator me-1"></i>Proses Penerimaan Glm <?= $fGel ?>
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Info Gelombang -->
<?php if ($g): ?>
<div class="alert alert-info small mb-3">
    <strong>Gelombang <?= $fGel ?></strong> —
    Pendaftaran: <?= date('d M Y', strtotime($g['tanggal_buka'])) ?> s/d <?= date('d M Y', strtotime($g['tanggal_tutup'])) ?> |
    Pengumuman: <?= date('d M Y', strtotime($g['tanggal_pengumuman'])) ?> |
    Kuota per jurusan: <strong><?= $g['kuota_per_jurusan'] ?></strong> |
    Porsi gelombang ini: <strong><?= $g['persen_gelombang'] ?>%</strong> →
    Ambil <strong><?= $kuota_glm ?> terbaik</strong> per jurusan |
    Status publish: <?= $g['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Belum</span>' ?>
</div>
<?php endif; ?>

<!-- Filter Jurusan -->
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="?page=ranking&gelombang=<?= $fGel ?>" class="btn btn-sm <?= !$fJurusan?'btn-dark':'btn-outline-dark' ?>">Semua</a>
    <?php foreach ($jurusan_list as $j): ?>
    <a href="?page=ranking&gelombang=<?= $fGel ?>&jurusan=<?= urlencode($j) ?>"
       class="btn btn-sm <?= $fJurusan===$j?'btn-dark':'btn-outline-dark' ?>">
        <?= $short[$j] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Tabel Ranking per Jurusan -->
<?php
$target_jurusan = $fJurusan ? [$fJurusan] : $jurusan_list;
foreach ($target_jurusan as $jurusan):
    $stmt = $conn->prepare("SELECT * FROM pendaftar WHERE gelombang=? AND jurusan=?
                            ORDER BY nilai_akhir DESC, usia DESC");
    $stmt->execute([$fGel, $jurusan]);
    $list = $stmt->fetchAll();
    $total_j = count($list);
    $diterima_j = count(array_filter($list, fn($r) => $r['status']==='diterima'));
?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><?= htmlspecialchars($jurusan) ?></span>
        <span class="small text-muted">
            <?= $total_j ?> pendaftar |
            Diterima: <span class="text-success fw-bold"><?= $diterima_j ?></span> /
            Kuota: <span class="fw-bold"><?= $kuota_glm ?></span>
        </span>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 small">
        <thead class="table-light">
            <tr><th>#</th><th>No. Daftar</th><th>Nama</th><th>NISN</th><th>L/P</th>
                <th>Raport (70%)</th><th>TKA (30%)</th><th>Nilai Akhir</th><th>Usia</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php if (empty($list)): ?>
            <tr><td colspan="10" class="text-center py-3 text-muted">Belum ada pendaftar untuk jurusan ini.</td></tr>
        <?php else:
            $rank = 0;
            foreach ($list as $r):
                $rank++;
                $is_batas = ($rank === $kuota_glm + 1 && $r['lolos_usia']);
                if ($is_batas): ?>
                <tr><td colspan="10" class="bg-warning text-center small py-1 fw-semibold">
                    ── Batas Kuota (<?= $kuota_glm ?> terbaik) ──
                </td></tr>
                <?php endif;
                $badge = match($r['status']) { 'diterima'=>'bg-success', 'ditolak'=>'bg-danger', default=>'bg-warning text-dark' };
                $rowClass = match($r['status']) { 'diterima'=>'table-success', 'ditolak'=>'table-danger opacity-75', default=>'' };
            ?>
            <tr class="<?= $rowClass ?>">
                <td class="text-muted"><?= $rank ?></td>
                <td><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                <td><?= htmlspecialchars($r['nama']) ?></td>
                <td><?= htmlspecialchars($r['nisn']) ?></td>
                <td><?= $r['jenis_kelamin'] ?></td>
                <td class="text-center"><?= number_format($r['nilai_raport'], 2) ?></td>
                <td class="text-center"><?= number_format($r['nilai_tka'], 2) ?></td>
                <td class="text-center fw-bold"><?= number_format($r['nilai_akhir'], 2) ?></td>
                <td class="text-center">
                    <?= $r['usia'] ?>
                    <?php if (!$r['lolos_usia']): ?><span class="text-danger ms-1" title="Gugur usia">⚠</span><?php endif; ?>
                </td>
                <td><span class="badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>
<?php endforeach; ?>
