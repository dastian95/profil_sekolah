<?php
// Laporan & Statistik — hanya tersedia di superadmin_dashboard

// ── Shared: kolom, header, row-builder, style untuk semua export ─────────────
function _export_select(): string {
    return "SELECT no_pendaftaran, gelombang, nama, nisn, tanggal_lahir, usia,
        jenis_kelamin, asal_sekolah, no_telp, alamat AS alamat_lengkap, jurusan,
        nilai_raport, nilai_tka, nilai_akhir, lolos_usia, status
        FROM pendaftar";
}
function _export_headers(): array {
    return [
        'No Pendaftaran','Gelombang','Nama','NISN','Tgl Lahir','Usia','L/P',
        'Asal Sekolah','No Telp','Alamat Lengkap','Jurusan',
        'Nilai Raport','Nilai TKA','Nilai Akhir','Lolos Usia','Status',
    ];
}
function _export_row(array $r): array {
    return [
        $r['no_pendaftaran'], $r['gelombang'], $r['nama'], $r['nisn'],
        $r['tanggal_lahir'], $r['usia'], $r['jenis_kelamin'],
        $r['asal_sekolah'], $r['no_telp']??'', $r['alamat_lengkap']??'',
        $r['jurusan'], $r['nilai_raport'], $r['nilai_tka'], $r['nilai_akhir'],
        $r['lolos_usia'] ? 'Ya' : 'Tidak (>21thn)',
        $r['status'],
    ];
}
// col 14=Lolos Usia, col 15=Status
function _export_style(int $col, $val): int {
    if ($col === 15) {
        return match($val) {
            'terima'   => XLSX_GREEN,
            'gugur'    => XLSX_RED,
            'diproses' => XLSX_YELLOW,
            'lengkap'  => XLSX_BLUE,
            default    => XLSX_GRAY,
        };
    }
    if ($col === 14) return $val === 'Ya' ? XLSX_GREEN : XLSX_RED;
    return XLSX_NORMAL;
}

// ── Export XLSX Per Hari (early exit) ────────────────────────────────────────
if (($_GET['action'] ?? '') === 'export_perhari') {
    require_once __DIR__ . '/xlsx_helper.php';

    $tgl = $_GET['tanggal'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) $tgl = date('Y-m-d');

    $stmt = $conn->prepare(_export_select() . " WHERE created_at >= ? AND created_at <= ? ORDER BY jurusan, created_at ASC");
    $stmt->execute([$tgl . ' 01:00:00', $tgl . ' 21:00:00']);
    $rows = $stmt->fetchAll();

    $fname = 'laporan_spmb_harian_' . $tgl . '_' . date('His') . '.xlsx';
    log_admin_action($conn, 'EXPORT_PERHARI', "Export harian $tgl: ".count($rows)." baris");

    $data = array_map('_export_row', $rows);
    xlsx_send($fname, _export_headers(), $data, 'Harian ' . date('d M Y', strtotime($tgl)), '_export_style');
}

// ── Export XLSX (early exit) ──────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'export_laporan') {
    require_once __DIR__ . '/xlsx_helper.php';

    $fJur = $_GET['jurusan'] ?? '';
    $fGlm = $_GET['gelombang'] ?? '';
    $fSts = $_GET['status'] ?? '';

    $where = ['1=1']; $params = [];
    if ($fJur) { $where[] = 'jurusan=?'; $params[] = $fJur; }
    if ($fGlm) { $where[] = 'gelombang=?'; $params[] = (int)$fGlm; }
    if ($fSts) { $where[] = 'status=?'; $params[] = $fSts; }

    $stmt = $conn->prepare(_export_select() . " WHERE " . implode(' AND ', $where) . " ORDER BY jurusan, gelombang, status, nilai_akhir DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $parts = [];
    if ($fJur) $parts[] = JURUSAN_SHORT[$fJur] ?? $fJur;
    if ($fGlm) $parts[] = 'G'.$fGlm;
    if ($fSts) $parts[] = $fSts;
    $fname = 'laporan_spmb' . ($parts ? '_'.implode('_',$parts) : '') . '_' . date('Ymd_His') . '.xlsx';

    log_admin_action($conn, 'EXPORT_LAPORAN', "Export laporan: $fname, ".count($rows)." baris");

    $data = array_map('_export_row', $rows);
    xlsx_send($fname, _export_headers(), $data, 'Laporan SPMB', '_export_style');
}

// ── Ambil data sekolah untuk kop cetak ───────────────────────────────────────
$sch_nama   = 'SMKS Laboratorium Jakarta';
$sch_alamat = 'Jl. Rawa Jaya No.37, Duren Sawit, Jakarta Timur 13460';
try {
    $sq = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('sekolah_nama','sekolah_alamat')");
    foreach ($sq as $row) {
        if (trim((string)$row['setting_value']) === '') continue;
        if ($row['setting_key'] === 'sekolah_nama')   $sch_nama   = $row['setting_value'];
        if ($row['setting_key'] === 'sekolah_alamat') $sch_alamat = $row['setting_value'];
    }
} catch (Throwable) {}

// ── Print Per Hari (early exit) — output HTML cetak gabungan, dikelompokkan per jurusan ──
if (($_GET['action'] ?? '') === 'print_perhari') {
    $tgl = $_GET['tanggal'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) $tgl = date('Y-m-d');

    $stmt = $conn->prepare("SELECT no_pendaftaran, nama, nisn, jenis_kelamin, jurusan, status
        FROM pendaftar WHERE created_at >= ? AND created_at <= ? ORDER BY jurusan, created_at ASC");
    $stmt->execute([$tgl . ' 01:00:00', $tgl . ' 21:00:00']);
    $rows = $stmt->fetchAll();
    log_admin_action($conn, 'PRINT_PERHARI', "Print harian $tgl: ".count($rows)." baris");

    $stCol = [
        'terima'=>['#166534','#d1fae5'], 'gugur'=>['#991b1b','#fee2e2'],
        'diproses'=>['#92400e','#fef3c7'], 'lengkap'=>['#0e7490','#cffafe'], 'ditahan'=>['#374151','#e5e7eb'],
    ];
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

    $tbody = ''; $no = 0;
    foreach ($rows as $r) {
        $no++;
        $sc = $stCol[$r['status']] ?? ['#333','#f8f8f8'];
        $kd = JURUSAN_SHORT[$r['jurusan']] ?? $r['jurusan'];
        $tbody .= '<tr><td style="text-align:center;">'.$no.'</td>'
            .'<td>'.$h($r['no_pendaftaran']).'</td><td>'.$h($r['nama']).'</td><td>'.$h($r['nisn'] ?: '—').'</td>'
            .'<td style="text-align:center;">'.$h($r['jenis_kelamin']).'</td>'
            .'<td style="text-align:center;">'.$h($kd).'</td>'
            .'<td style="text-align:center;background:'.$sc[1].';color:'.$sc[0].';font-weight:700;">'.$h($r['status']).'</td></tr>';
    }
    if ($tbody === '') $tbody = '<tr><td colspan="7" style="text-align:center;color:#999;">Tidak ada pendaftar pada tanggal ini</td></tr>';

    while (ob_get_level() > 0) ob_end_clean();
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Laporan Harian '.$h($tgl).'</title><style>'
        .'@page{size:A4;margin:12mm 12mm 14mm;}'
        .'body{font-family:"Segoe UI",Arial,sans-serif;color:#111;margin:0;}'
        .'.print-kop{border-bottom:3px double #000;margin-bottom:12px;padding-bottom:8px;}'
        .'.print-kop h5{font-size:1.05rem;font-weight:800;margin:0 0 2px;}'
        .'.print-kop small{font-size:.78rem;color:#333;}'
        .'.print-meta{margin-top:6px;font-size:.8rem;}'
        .'table{width:100%;border-collapse:collapse;font-size:.82rem;}'
        .'th,td{border:1px solid #555;padding:4px 7px;}'
        .'thead{display:table-header-group;}thead th{background:#e9ecef;font-weight:700;text-align:center;}'
        .'tbody tr{page-break-inside:avoid;}'
        .'.print-foot{text-align:right;font-size:.72rem;color:#666;margin-top:8px;}'
        .'.noprint-btn{position:fixed;top:10px;right:10px;z-index:9999;padding:8px 16px;background:#7c3aed;color:#fff;border:0;border-radius:8px;font-weight:700;cursor:pointer;font-family:inherit;}'
        .'@media print{body{print-color-adjust:exact;-webkit-print-color-adjust:exact;}.noprint-btn{display:none;}}'
        .'</style></head><body>'
        .'<button class="noprint-btn" onclick="window.print()">&#128424; Cetak</button>'
        .'<div class="print-kop"><h5>'.$h($sch_nama).'</h5><small>'.$h($sch_alamat).'</small>'
        .'<div class="print-meta"><strong>Laporan Pendaftaran Harian — '.$h(date('d F Y', strtotime($tgl))).'</strong>'
        .' &nbsp;|&nbsp; Dicetak: '.date('d F Y H:i').' &nbsp;|&nbsp; Jumlah: <strong>'.count($rows).'</strong> pendaftar</div></div>'
        .'<table><thead><tr><th>#</th><th>No. Pendaftaran</th><th>Nama</th><th>NISN</th><th>L/P</th><th>Jurusan</th><th>Status</th></tr></thead>'
        .'<tbody>'.$tbody.'</tbody></table>'
        .'<div class="print-foot">*** Dokumen dicetak dari sistem SPMB '.$h($sch_nama).' ***</div>'
        .'<script>window.onload=function(){setTimeout(function(){window.print();},350);};<\/script></body></html>';
    exit;
}

// ── Query: per gelombang × status ────────────────────────────────────────────
$glm_stats = [];
try {
    $st = $conn->query("SELECT gelombang, status, COUNT(*) AS n FROM pendaftar GROUP BY gelombang, status ORDER BY gelombang, status");
    foreach ($st as $r) {
        $g = (int)$r['gelombang'];
        $glm_stats[$g][$r['status']] = (int)$r['n'];
        if (!isset($glm_stats[$g]['total'])) $glm_stats[$g]['total'] = 0;
        $glm_stats[$g]['total'] += (int)$r['n'];
    }
} catch (Throwable) {}

// ── Query: per jurusan × status ──────────────────────────────────────────────
$jur_stats = [];
try {
    $st = $conn->query("SELECT jurusan, status, COUNT(*) AS n FROM pendaftar GROUP BY jurusan, status");
    foreach ($st as $r) {
        $j = $r['jurusan'];
        $jur_stats[$j][$r['status']] = (int)$r['n'];
        if (!isset($jur_stats[$j]['total'])) $jur_stats[$j]['total'] = 0;
        $jur_stats[$j]['total'] += (int)$r['n'];
    }
} catch (Throwable) {}

// ── Summary ──────────────────────────────────────────────────────────────────
$total_all = $total_terima = $total_gugur = $total_proses = 0;
foreach ($glm_stats as $gd) {
    $total_all    += $gd['total'] ?? 0;
    $total_terima += $gd['terima'] ?? 0;
    $total_gugur  += $gd['gugur'] ?? 0;
    $total_proses += ($gd['diproses'] ?? 0) + ($gd['lengkap'] ?? 0);
}

// ── Query: harian ────────────────────────────────────────────────────────────
$harian = [];
try {
    $st = $conn->query("SELECT DATE(created_at) AS tgl, COUNT(*) AS n FROM pendaftar
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at) ORDER BY tgl");
    foreach ($st as $r) $harian[$r['tgl']] = (int)$r['n'];
} catch (Throwable) {}

// ── Data per jurusan untuk print ─────────────────────────────────────────────
$print_data = []; // [jurusan] => ['terima' => [...rows], 'semua' => [...rows]]
foreach (JURUSAN_LIST as $jFull) {
    try {
        $stT = $conn->prepare("SELECT no_pendaftaran, nama, nisn, jenis_kelamin, gelombang, asal_sekolah,
            nilai_raport, nilai_tka, nilai_akhir, usia, status, catatan
            FROM pendaftar WHERE jurusan=? AND status='terima'
            ORDER BY gelombang, nilai_akhir DESC, usia DESC");
        $stT->execute([$jFull]);
        $print_data[$jFull]['terima'] = $stT->fetchAll();

        $stA = $conn->prepare("SELECT no_pendaftaran, nama, nisn, jenis_kelamin, gelombang, asal_sekolah,
            nilai_raport, nilai_tka, nilai_akhir, usia, status, catatan, DATE(created_at) AS tgl
            FROM pendaftar WHERE jurusan=?
            ORDER BY gelombang, status, nilai_akhir DESC, usia DESC");
        $stA->execute([$jFull]);
        $print_data[$jFull]['semua'] = $stA->fetchAll();
    } catch (Throwable) {}
}

// ── Gelombang list untuk filter ──────────────────────────────────────────────
$glm_opts = array_keys($glm_stats);

$chart_labels = array_map(fn($j) => JURUSAN_SHORT[$j] ?? $j, JURUSAN_LIST);
$chart_terima = array_map(fn($j) => $jur_stats[$j]['terima'] ?? 0, JURUSAN_LIST);
$chart_total  = array_map(fn($j) => $jur_stats[$j]['total'] ?? 0, JURUSAN_LIST);
$status_all   = ['diproses'=>0,'lengkap'=>0,'gugur'=>0,'terima'=>0];
foreach ($glm_stats as $gd) { foreach ($status_all as $s => $_) $status_all[$s] += $gd[$s] ?? 0; }

$status_labels_all = [
    'terima'   => 'Diterima',
    'gugur'    => 'Gugur',
    'diproses' => 'Diproses',
    'lengkap'  => 'Lengkap',
    ''         => 'Semua Status',
];
?>

<!-- ── Print-only Styles ─────────────────────────────────────────────────────── -->
<style>
@media print {
    body * { visibility: hidden !important; }
    #print-area, #print-area * { visibility: visible !important; }
    #print-area { position: fixed; inset: 0; padding: 20px 28px; background: #fff; z-index: 9999; }
    .no-print { display: none !important; }
}
#print-area { display: none; }
.print-kop { border-bottom: 3px double #000; margin-bottom: 16px; padding-bottom: 12px; }
.print-kop h5 { font-size: 1.1rem; font-weight: 800; margin: 0 0 2px; }
.print-kop small { font-size: .78rem; }
.print-table { width: 100%; border-collapse: collapse; font-size: .78rem; margin-top: 8px; }
.print-table th, .print-table td { border: 1px solid #555; padding: 4px 6px; }
.print-table thead th { background: #e9ecef; font-weight: 700; text-align: center; }
.print-table tbody tr:nth-child(even) { background: #f8f8f8; }
.badge-st { padding: 2px 6px; border-radius: 4px; font-size: .7rem; font-weight: 700; }
</style>

<!-- Area khusus print (tidak terlihat di layar) -->
<div id="print-area"></div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- HEADER                                                                      -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bar-chart-fill me-2" style="color:#7c3aed;"></i>Laporan & Statistik SPMB</h4>
        <div class="text-muted small">Data per <?= date('d M Y H:i') ?></div>
    </div>
</div>

<!-- ── Summary Cards ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4 no-print">
    <?php foreach ([
        ['Total Pendaftar', $total_all,    'bi-people-fill',       '#7c3aed', '#ede9fe'],
        ['Diterima',        $total_terima, 'bi-check-circle-fill', '#059669', '#d1fae5'],
        ['Gugur',           $total_gugur,  'bi-x-circle-fill',     '#dc2626', '#fee2e2'],
        ['Dalam Proses',    $total_proses, 'bi-hourglass-split',   '#d97706', '#fef3c7'],
    ] as [$lbl, $val, $ico, $col, $bg]): ?>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-0" style="background:<?= $bg ?>;">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                <div style="width:46px;height:46px;border-radius:12px;background:<?= $col ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi <?= $ico ?>" style="color:#fff;font-size:1.3rem;"></i>
                </div>
                <div>
                    <div style="font-size:1.6rem;font-weight:800;color:<?= $col ?>;line-height:1;"><?= $val ?></div>
                    <div class="small fw-semibold" style="color:<?= $col ?>;opacity:.75;"><?= $lbl ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Charts ────────────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4 no-print">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Pendaftar per Jurusan</div>
            <div class="card-body"><canvas id="chartJurusan" style="max-height:280px;"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart me-2"></i>Distribusi Status</div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartStatus" style="max-height:250px;max-width:250px;"></canvas>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($harian)): ?>
<div class="card mb-4 no-print">
    <div class="card-header"><i class="bi bi-calendar3 me-2"></i>Pendaftaran Harian (30 Hari Terakhir)</div>
    <div class="card-body"><canvas id="chartHarian" style="max-height:200px;"></canvas></div>
</div>
<?php endif; ?>

<!-- ── Per Gelombang ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4 no-print">
<?php foreach ($glm_stats as $g => $gd): ?>
<div class="col-md-6">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-layers me-2"></i>Gelombang <?= $g ?></span>
            <span class="badge bg-secondary"><?= $gd['total'] ?? 0 ?> pendaftar</span>
        </div>
        <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <?php foreach (JURUSAN_SHORT as $jFull => $jShort): ?>
                    <th class="text-center"><?= $jShort ?></th>
                    <?php endforeach; ?>
                    <th class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (['terima'=>'Diterima','diproses'=>'Diproses','lengkap'=>'Lengkap','gugur'=>'Gugur'] as $st => $stLabel):
                $badgeClass = STATUS_BADGE[$st] ?? 'bg-secondary'; ?>
            <tr><td colspan="<?= count(JURUSAN_SHORT)+1 ?>" class="py-1 ps-2">
                <span class="badge <?= $badgeClass ?> me-1"><?= $stLabel ?></span>
            </td></tr>
            <tr>
                <?php $rowTotal = 0;
                foreach (JURUSAN_LIST as $jFull):
                    try { $cnt=$conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=? AND jurusan=? AND status=?"); $cnt->execute([$g,$jFull,$st]); $n=(int)$cnt->fetchColumn(); } catch(Throwable){$n=0;}
                    $rowTotal+=$n; ?>
                    <td class="text-center"><?= $n?:'<span class="text-muted">—</span>' ?></td>
                <?php endforeach; ?>
                <td class="text-center fw-bold"><?= $rowTotal?:'<span class="text-muted">—</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<!-- REKAP PER JURUSAN + Export + Print                                           -->
<!-- ══════════════════════════════════════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span><i class="bi bi-table me-2"></i>Rekap per Jurusan</span>
        <!-- Export CSV dengan filter -->
        <form method="GET" action="superadmin_dashboard.php" class="d-flex flex-wrap gap-2 align-items-center no-print" id="formExport" target="_blank">
            <input type="hidden" name="page" value="laporan">
            <input type="hidden" name="action" value="export_laporan">
            <select name="jurusan" class="form-select form-select-sm" style="max-width:160px;">
                <option value="">Semua Jurusan</option>
                <?php foreach (JURUSAN_LIST as $j): ?>
                <option value="<?= htmlspecialchars($j) ?>"><?= htmlspecialchars(JURUSAN_SHORT[$j] ?? $j) ?> — <?= htmlspecialchars($j) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="gelombang" class="form-select form-select-sm" style="max-width:130px;">
                <option value="">Semua Gelombang</option>
                <?php foreach ($glm_opts as $g): ?><option value="<?= $g ?>">Gelombang <?= $g ?></option><?php endforeach; ?>
            </select>
            <select name="status" class="form-select form-select-sm" style="max-width:140px;">
                <?php foreach ($status_labels_all as $val => $label): ?>
                <option value="<?= $val ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Export .xlsx
            </button>
            <a href="superadmin_dashboard.php?page=laporan&action=export_laporan" class="btn btn-outline-secondary btn-sm" target="_blank">
                <i class="bi bi-file-earmark-arrow-down me-1"></i>Export Semua (.xlsx)
            </a>
        </form>
        <!-- Export Per Hari -->
        <form method="GET" action="superadmin_dashboard.php" class="d-flex flex-wrap gap-2 align-items-center no-print ms-md-auto" target="_blank">
            <input type="hidden" name="page" value="laporan">
            <label class="small fw-semibold text-muted mb-0 me-1">Per Hari:</label>
            <input type="date" name="tanggal" class="form-control form-control-sm" style="max-width:160px;"
                   value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            <button type="submit" name="action" value="export_perhari" class="btn btn-primary btn-sm">
                <i class="bi bi-calendar-day me-1"></i>Export Per Hari (.xlsx)
            </button>
            <button type="button" onclick="printPerHari(this)" class="btn btn-info btn-sm">
                <i class="bi bi-printer me-1"></i>Print Per Hari
            </button>
        </form>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Jurusan</th>
                <th class="text-center"><span class="badge bg-warning text-dark">Diproses</span></th>
                <th class="text-center"><span class="badge bg-info text-dark">Lengkap</span></th>
                <th class="text-center"><span class="badge bg-danger">Gugur</span></th>
                <th class="text-center"><span class="badge bg-success">Terima</span></th>
                <th class="text-center fw-bold">Total</th>
                <th class="text-center">% Diterima</th>
                <th class="text-center no-print">Cetak</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach (JURUSAN_LIST as $jFull):
            $js  = $jur_stats[$jFull] ?? [];
            $tot = $js['total'] ?? 0;
            $ter = $js['terima'] ?? 0;
            $pct = $tot > 0 ? round($ter / $tot * 100) : 0; ?>
        <tr>
            <td>
                <span class="badge bg-secondary me-1"><?= JURUSAN_SHORT[$jFull] ?></span>
                <span class="small text-muted"><?= htmlspecialchars($jFull) ?></span>
            </td>
            <td class="text-center"><?= $js['diproses'] ?? 0 ?></td>
            <td class="text-center"><?= $js['lengkap'] ?? 0 ?></td>
            <td class="text-center"><?= $js['gugur'] ?? 0 ?></td>
            <td class="text-center fw-bold text-success"><?= $ter ?></td>
            <td class="text-center fw-bold"><?= $tot ?></td>
            <td class="text-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px;">
                        <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span class="small fw-semibold" style="min-width:32px;"><?= $pct ?>%</span>
                </div>
            </td>
            <td class="text-center no-print">
                <div class="d-flex gap-1 justify-content-center">
                    <button class="btn btn-sm btn-outline-success py-0 px-2"
                            onclick="printJurusan('<?= htmlspecialchars($jFull, ENT_QUOTES) ?>', 'terima')"
                            title="Cetak Diterima">
                        <i class="bi bi-printer me-1"></i>Terima
                    </button>
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2"
                            onclick="printJurusan('<?= htmlspecialchars($jFull, ENT_QUOTES) ?>', 'semua')"
                            title="Cetak Semua Pendaftar">
                        <i class="bi bi-printer me-1"></i>Semua
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot class="table-secondary fw-bold">
            <tr>
                <td>Total</td>
                <?php foreach (['diproses','lengkap','gugur','terima'] as $st): ?>
                <td class="text-center"><?= $status_all[$st] ?></td>
                <?php endforeach; ?>
                <td class="text-center"><?= $total_all ?></td>
                <td class="text-center"><?= $total_all > 0 ? round($total_terima/$total_all*100) : 0 ?>%</td>
                <td class="text-center no-print">
                    <button class="btn btn-sm btn-dark py-0 px-2" onclick="printAllJurusan()" title="Cetak daftar siswa diterima — semua jurusan">
                        <i class="bi bi-printer me-1"></i>Diterima 4 Jurusan
                    </button>
                </td>
            </tr>
        </tfoot>
    </table>
    </div>
    </div>
</div>

<!-- Data print diembed sebagai JSON agar tidak query ulang -->
<script>
const PRINT_DATA   = <?= json_encode($print_data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?>;
const SCH_NAMA     = <?= json_encode($sch_nama) ?>;
const SCH_ALAMAT   = <?= json_encode($sch_alamat) ?>;
const JURUSAN_LIST = <?= json_encode(JURUSAN_LIST) ?>;
const JUR_SHORT    = <?= json_encode(JURUSAN_SHORT, JSON_UNESCAPED_UNICODE) ?>;

const STATUS_COLOR = {
    'terima':   '#166534',
    'gugur':    '#991b1b',
    'diproses': '#92400e',
    'lengkap':  '#0e7490',
};
const STATUS_BG = {
    'terima':   '#d1fae5',
    'gugur':    '#fee2e2',
    'diproses': '#fef3c7',
    'lengkap':  '#cffafe',
};

function printJurusan(jurusan, mode) {
    const data = PRINT_DATA[jurusan];
    if (!data) return;

    const rows   = mode === 'terima' ? data.terima : data.semua;
    const label  = mode === 'terima' ? 'Daftar Siswa Diterima' : 'Daftar Seluruh Pendaftar';
    const now    = new Date().toLocaleString('id-ID', {dateStyle:'long', timeStyle:'short'});

    let tbody = '';
    rows.forEach((r, i) => {
        const stColor = STATUS_COLOR[r.status] || '#333';
        const stBg    = STATUS_BG[r.status]    || '#f8f8f8';
        tbody += `<tr>
            <td style="text-align:center;">${i+1}</td>
            <td>${r.no_pendaftaran}</td>
            <td>${r.nama}</td>
            <td>${r.nisn || '—'}</td>
            <td style="text-align:center;">${r.jenis_kelamin}</td>
            <td style="text-align:center;font-weight:700;">${r.nilai_akhir || '—'}</td>
            ${mode !== 'terima' ? `<td style="text-align:center;background:${stBg};color:${stColor};font-weight:700;">${r.status}</td>` : ''}
        </tr>`;
    });

    const colspan = mode !== 'terima' ? 7 : 6;
    const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>${esc(label)} — ${esc(jurusan)}</title>
<style>
  @page { size: A4; margin: 12mm 12mm 14mm; }
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; margin: 0; }
  .print-kop { border-bottom: 3px double #000; margin-bottom: 12px; padding-bottom: 8px; }
  .print-kop h5 { font-size: 1.05rem; font-weight: 800; margin: 0 0 2px; }
  .print-kop small { font-size: .78rem; color: #333; }
  .print-meta { margin-top: 6px; font-size: .8rem; }
  table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  th, td { border: 1px solid #555; padding: 4px 7px; }
  thead { display: table-header-group; }            /* header berulang tiap halaman */
  thead th { background: #e9ecef; font-weight: 700; text-align: center; }
  tbody tr { page-break-inside: avoid; }            /* baris tidak terpotong antar halaman */
  tbody tr:nth-child(even) { background: #f6f6f6; }
  .print-foot { text-align: right; font-size: .72rem; color: #666; margin-top: 8px; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
</style></head><body>
  <div class="print-kop">
    <h5>${esc(SCH_NAMA)}</h5>
    <small>${esc(SCH_ALAMAT)}</small>
    <div class="print-meta"><strong>${esc(label)} — Jurusan ${esc(jurusan)}</strong> &nbsp;|&nbsp; Dicetak: ${now} &nbsp;|&nbsp; Jumlah: <strong>${rows.length}</strong> siswa</div>
  </div>
  <table>
    <thead>
      <tr>
        <th>#</th><th>No. Pendaftaran</th><th>Nama</th><th>NISN</th><th>L/P</th><th>Nilai Akhir</th>
        ${mode !== 'terima' ? '<th>Status</th>' : ''}
      </tr>
    </thead>
    <tbody>${tbody || `<tr><td colspan="${colspan}" style="text-align:center;color:#999;">Tidak ada data</td></tr>`}</tbody>
  </table>
  <div class="print-foot">*** Dokumen dicetak dari sistem SPMB ${esc(SCH_NAMA)} ***</div>
  <script>window.onload = function(){ window.print(); }<\/script>
</body></html>`;

    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write(html);
    w.document.close();
}

// Cetak daftar siswa DITERIMA — 4 lembar terpisah, satu jurusan per halaman (kolom #, NISN, Nama)
function printAllJurusan() {
    const now = new Date().toLocaleString('id-ID', {dateStyle:'long', timeStyle:'short'});
    const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    let sheets = '';
    JURUSAN_LIST.forEach((jur, idx) => {
        const rows = (PRINT_DATA[jur] && PRINT_DATA[jur].terima) || [];
        let tbody = '';
        rows.forEach((r, i) => {
            tbody += `<tr>
                <td style="text-align:center;">${i+1}</td>
                <td>${esc(r.nisn) || '—'}</td>
                <td>${esc(r.nama)}</td>
            </tr>`;
        });
        sheets += `<div class="sheet"${idx > 0 ? ' style="page-break-before: always;"' : ''}>
          <div class="print-kop">
            <h5>${esc(SCH_NAMA)}</h5>
            <small>${esc(SCH_ALAMAT)}</small>
            <div class="print-meta"><strong>Daftar Siswa Diterima — Jurusan ${esc(jur)}</strong> &nbsp;|&nbsp; Dicetak: ${now} &nbsp;|&nbsp; Jumlah: <strong>${rows.length}</strong> siswa</div>
          </div>
          <table>
            <thead><tr><th>#</th><th>NISN</th><th>Nama</th></tr></thead>
            <tbody>${tbody || '<tr><td colspan="3" style="text-align:center;color:#999;">Tidak ada data</td></tr>'}</tbody>
          </table>
          <div class="print-foot">*** Dokumen dicetak dari sistem SPMB ${esc(SCH_NAMA)} ***</div>
        </div>`;
    });

    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Daftar Diterima per Jurusan</title>
<style>
  @page { size: A4; margin: 12mm 12mm 14mm; }
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; margin: 0; }
  .print-kop { border-bottom: 3px double #000; margin-bottom: 12px; padding-bottom: 8px; }
  .print-kop h5 { font-size: 1.05rem; font-weight: 800; margin: 0 0 2px; }
  .print-kop small { font-size: .78rem; color: #333; }
  .print-meta { margin-top: 6px; font-size: .8rem; }
  table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  th, td { border: 1px solid #555; padding: 4px 7px; }
  thead { display: table-header-group; }
  thead th { background: #e9ecef; font-weight: 700; text-align: center; }
  tbody tr { page-break-inside: avoid; }
  tbody tr:nth-child(even) { background: #f6f6f6; }
  .print-foot { text-align: right; font-size: .72rem; color: #666; margin-top: 8px; }
</style></head><body>
${sheets}
  <script>window.onload = function(){ window.print(); }<\/script>
</body></html>`;

    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write(html);
    w.document.close();
}

// Cetak Laporan Per Hari — sama mekanismenya dgn cetak lain (data client + window.open + document.write)
function printPerHari(btn) {
    const form = btn.closest('form');
    const inp  = form ? form.querySelector('input[name="tanggal"]') : null;
    const tgl  = (inp && inp.value) ? inp.value : '<?= date('Y-m-d') ?>';
    const now  = new Date().toLocaleString('id-ID', {dateStyle:'long', timeStyle:'short'});
    const esc  = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const stCol = {'terima':['#166534','#d1fae5'],'gugur':['#991b1b','#fee2e2'],'diproses':['#92400e','#fef3c7'],'lengkap':['#0e7490','#cffafe'],'ditahan':['#374151','#e5e7eb']};

    let tbody = '', no = 0;
    JURUSAN_LIST.forEach(jur => {
        const rows = (PRINT_DATA[jur] && PRINT_DATA[jur].semua) || [];
        rows.forEach(r => {
            if (r.tgl !== tgl) return;
            no++;
            const sc = stCol[r.status] || ['#333','#f8f8f8'];
            const kd = JUR_SHORT[jur] || jur;
            tbody += `<tr><td style="text-align:center;">${no}</td><td>${esc(r.no_pendaftaran)}</td><td>${esc(r.nama)}</td>`
                + `<td>${esc(r.nisn) || '—'}</td><td style="text-align:center;">${esc(r.jenis_kelamin)}</td>`
                + `<td style="text-align:center;">${esc(kd)}</td>`
                + `<td style="text-align:center;background:${sc[1]};color:${sc[0]};font-weight:700;">${esc(r.status)}</td></tr>`;
        });
    });
    if (!tbody) tbody = '<tr><td colspan="7" style="text-align:center;color:#999;">Tidak ada pendaftar pada tanggal ini</td></tr>';

    const bln = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const pt = tgl.split('-');
    const tglFmt = (pt.length === 3) ? (parseInt(pt[2],10) + ' ' + (bln[parseInt(pt[1],10)-1] || '') + ' ' + pt[0]) : tgl;

    const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Laporan Harian ${esc(tgl)}</title>
<style>
  @page { size: A4; margin: 12mm 12mm 14mm; }
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; margin: 0; }
  .print-kop { border-bottom: 3px double #000; margin-bottom: 12px; padding-bottom: 8px; }
  .print-kop h5 { font-size: 1.05rem; font-weight: 800; margin: 0 0 2px; }
  .print-kop small { font-size: .78rem; color: #333; }
  .print-meta { margin-top: 6px; font-size: .8rem; }
  table { width: 100%; border-collapse: collapse; font-size: .82rem; }
  th, td { border: 1px solid #555; padding: 4px 7px; }
  thead { display: table-header-group; }
  thead th { background: #e9ecef; font-weight: 700; text-align: center; }
  tbody tr { page-break-inside: avoid; }
  .print-foot { text-align: right; font-size: .72rem; color: #666; margin-top: 8px; }
  @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
</style></head><body>
  <div class="print-kop"><h5>${esc(SCH_NAMA)}</h5><small>${esc(SCH_ALAMAT)}</small>
    <div class="print-meta"><strong>Laporan Pendaftaran Harian — ${esc(tglFmt)}</strong> &nbsp;|&nbsp; Dicetak: ${now} &nbsp;|&nbsp; Jumlah: <strong>${no}</strong> pendaftar</div></div>
  <table><thead><tr><th>#</th><th>No. Pendaftaran</th><th>Nama</th><th>NISN</th><th>L/P</th><th>Jurusan</th><th>Status</th></tr></thead>
  <tbody>${tbody}</tbody></table>
  <div class="print-foot">*** Dokumen dicetak dari sistem SPMB ${esc(SCH_NAMA)} ***</div>
  <script>window.onload = function(){ window.print(); }<\/script>
</body></html>`;

    const w = window.open('', '_blank', 'width=950,height=720');
    w.document.write(html);
    w.document.close();
}

// Charts
(function() {
    const ctx = document.getElementById('chartJurusan');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                { label:'Diterima',       data:<?= json_encode($chart_terima) ?>, backgroundColor:'rgba(5,150,105,.8)',  borderRadius:4 },
                { label:'Total Pendaftar',data:<?= json_encode($chart_total) ?>,  backgroundColor:'rgba(124,58,237,.25)',borderRadius:4 }
            ]
        },
        options: { responsive:true, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true,precision:0}} }
    });
})();

(function() {
    const ctx = document.getElementById('chartStatus');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Diproses','Lengkap','Gugur','Diterima'],
            datasets: [{ data:[<?= $status_all['diproses'] ?>,<?= $status_all['lengkap'] ?>,<?= $status_all['gugur'] ?>,<?= $status_all['terima'] ?>], backgroundColor:['#fbbf24','#06b6d4','#ef4444','#10b981'], borderWidth:2 }]
        },
        options: { responsive:true, plugins:{legend:{position:'bottom'}} }
    });
})();

(function() {
    const ctx = document.getElementById('chartHarian');
    if (!ctx) return;
    const data = <?= json_encode($harian) ?>;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: Object.keys(data),
            datasets: [{ label:'Pendaftar Masuk', data:Object.values(data), borderColor:'#7c3aed', backgroundColor:'rgba(124,58,237,.1)', fill:true, tension:.4, pointRadius:4 }]
        },
        options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,precision:0}} }
    });
})();
</script>
