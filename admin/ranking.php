<?php
$jurusan_list = JURUSAN_LIST;
$short        = JURUSAN_SHORT;

$msg = '';
if (!empty($_SESSION['flash_ranking'])) {
    $msg = $_SESSION['flash_ranking'];
    unset($_SESSION['flash_ranking']);
}

// Ambil konfigurasi gelombang
$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();
$gel_map  = [];
foreach ($gel_rows as $g) $gel_map[$g['gelombang']] = $g;

// ── Pin / Unpin ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pin') {
    $pin_id  = (int)$_POST['pendaftar_id'];
    $pin_val = (int)$_POST['pin_val'];  // 1 = pin, 0 = unpin
    $stmt = $conn->prepare("UPDATE pendaftar SET is_pinned=? WHERE id=?");
    $stmt->execute([$pin_val, $pin_id]);
    $label = $pin_val ? 'dipin' : 'dilepas';
    log_admin_action($conn, 'PIN_PENDAFTAR', "Pendaftar id={$pin_id} {$label}");
    $fGelRedirect = (int)($_POST['gelombang'] ?? 1);
    $fJurRedirect = urlencode($_POST['jurusan'] ?? '');
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . "?page=ranking&gelombang={$fGelRedirect}&jurusan={$fJurRedirect}");
    exit;
}

// ── Proses Penerimaan ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'proses') {
    $gelombang = (int)$_POST['gelombang'];
    $g         = $gel_map[$gelombang] ?? null;

    if (!$g) {
        $msg = '<div class="alert alert-danger">Gelombang tidak ditemukan.</div>';
    } else {
        $kuota_glm = (int)($g['kuota_glm'] ?? round($g['kuota_per_jurusan'] * $g['persen_gelombang'] / 100));

        // Reset semua ke diproses
        $conn->prepare("UPDATE pendaftar SET status='diproses', catatan=NULL WHERE gelombang=?")->execute([$gelombang]);

        // Gugur usia
        $conn->prepare("UPDATE pendaftar SET status='gugur', catatan='Gugur: usia melebihi 21 tahun'
                        WHERE gelombang=? AND lolos_usia=0")->execute([$gelombang]);

        // Gugur TKA di bawah minimum (hanya Reguler — Khusus & PKBM tanpa TKA; pin = override admin)
        $min_tka = (int)($g['min_tka'] ?? 0);
        if ($min_tka > 0) {
            $conn->prepare("UPDATE pendaftar SET status='gugur', catatan=?
                WHERE gelombang=? AND sistem_pendidikan='reguler' AND nilai_tka < ?
                AND is_pinned=0 AND status='diproses'")
                ->execute(["Gugur: nilai TKA di bawah minimum ({$min_tka})", $gelombang, $min_tka]);
        }

        $total_diterima = 0;
        foreach ($jurusan_list as $jurusan) {
            // Ambil pinned dulu (pasti diterima)
            $stmtPin = $conn->prepare("SELECT id FROM pendaftar
                WHERE gelombang=? AND jurusan=? AND lolos_usia=1 AND is_pinned=1");
            $stmtPin->execute([$gelombang, $jurusan]);
            $pinned_ids = $stmtPin->fetchAll(PDO::FETCH_COLUMN);

            $sisa_kuota = max(0, $kuota_glm - count($pinned_ids));

            // Sisanya (tidak pinned & belum gugur), urut nilai_akhir DESC
            $stmtNorm = $conn->prepare("SELECT id FROM pendaftar
                WHERE gelombang=? AND jurusan=? AND lolos_usia=1 AND is_pinned=0 AND status='diproses'
                ORDER BY nilai_akhir DESC, usia DESC");
            $stmtNorm->execute([$gelombang, $jurusan]);
            $normal_ids = $stmtNorm->fetchAll(PDO::FETCH_COLUMN);

            $terima_normal = array_slice($normal_ids, 0, $sisa_kuota);
            $tolak_ids     = array_slice($normal_ids, $sisa_kuota);

            $all_terima = array_merge($pinned_ids, $terima_normal);

            if ($all_terima) {
                $in = implode(',', array_fill(0, count($all_terima), '?'));
                $conn->prepare("UPDATE pendaftar SET status='terima', catatan=NULL WHERE id IN ($in)")
                     ->execute($all_terima);
                $total_diterima += count($all_terima);
            }
            if ($tolak_ids) {
                $in = implode(',', array_fill(0, count($tolak_ids), '?'));
                $conn->prepare("UPDATE pendaftar SET status='gugur', catatan='Nilai tidak mencapai kuota' WHERE id IN ($in)")
                     ->execute($tolak_ids);
            }
        }

        log_admin_action($conn, 'PROSES_RANKING',
            "Proses penerimaan Gelombang {$gelombang}: {$total_diterima} diterima");

        $msg = "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i>
            Proses penerimaan Gelombang <strong>{$gelombang}</strong> selesai.
            <strong>{$total_diterima}</strong> pendaftar diterima dari seluruh jurusan.</div>";
    }

    // PRG: redirect setelah proses agar refresh tidak mengulang seleksi
    $_SESSION['flash_ranking'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . '?page=ranking&gelombang=' . $gelombang);
    exit;
}

// ── Filter tampilan ───────────────────────────────────────────────────────────
$fGel     = (int)($_GET['gelombang'] ?? 1) ?: 1;
$fJurusan = $_GET['jurusan']   ?? '';
if ($fJurusan !== '' && !in_array($fJurusan, $jurusan_list, true)) $fJurusan = '';
$g        = $gel_map[(int)$fGel] ?? null;
$kuota_glm = $g ? (int)($g['kuota_glm'] ?? round($g['kuota_per_jurusan'] * $g['persen_gelombang'] / 100)) : 0;

// ── Load data semua pendaftar di gelombang+jurusan ini (termasuk raport) ──────
$target_jurusan = $fJurusan ? [$fJurusan] : $jurusan_list;
$all_data = [];

$db_error = null;
try {
    foreach ($target_jurusan as $jurusan) {
        $stmt = $conn->prepare("SELECT * FROM pendaftar WHERE gelombang=? AND jurusan=?
                                ORDER BY is_pinned DESC, nilai_akhir DESC, usia DESC");
        $stmt->execute([$fGel, $jurusan]);
        $list = $stmt->fetchAll();

        $ids = array_column($list, 'id');
        $raport_map = [];
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $rsStmt = $conn->prepare("SELECT pendaftar_id, mata_pelajaran, semester, nilai
                                      FROM pendaftar_raport WHERE pendaftar_id IN ($in)");
            $rsStmt->execute($ids);
            foreach ($rsStmt as $row) {
                $raport_map[$row['pendaftar_id']][$row['mata_pelajaran']][(int)$row['semester']] = (float)$row['nilai'];
            }
        }
        $all_data[$jurusan] = ['list' => $list, 'raport' => $raport_map];
    }
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}
?>

<?= $msg ?>

<?php if ($db_error): ?>
<div class="alert alert-danger">
    <h5 class="mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Database belum diperbarui</h5>
    <p class="mb-2">Kolom yang dibutuhkan belum ada di tabel <code>pendaftar</code>. Jalankan SQL berikut di phpMyAdmin (tab SQL):</p>
    <pre class="bg-dark text-white p-3 rounded small mb-2">ALTER TABLE `pendaftar`
  ADD COLUMN IF NOT EXISTS `is_pinned`         TINYINT(1)    NOT NULL DEFAULT 0     AFTER `lolos_usia`,
  ADD COLUMN IF NOT EXISTS `sistem_pendidikan` ENUM('reguler','pkbm','khusus') NOT NULL DEFAULT 'reguler' AFTER `is_pinned`,
  ADD COLUMN IF NOT EXISTS `no_pendaftaran`    VARCHAR(20)   DEFAULT NULL;</pre>
    <small class="text-muted">Detail: <?= htmlspecialchars($db_error) ?></small>
</div>
<?php return; endif; ?>

<!-- Pilih Gelombang -->
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
        <form method="POST" class="d-inline" onsubmit="return confirm('Proses penerimaan Gelombang <?= $fGel ?>?\nStatus semua pendaftar gelombang ini akan dihitung ulang.\nSiswa yang di-PIN tetap dijamin diterima.')">
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
    Ambil <strong><?= $kuota_glm ?> terbaik</strong> per jurusan |
    Status publish: <?= $g['is_published'] ? '<span class="badge bg-success">Published</span>' : '<span class="badge bg-secondary">Belum</span>' ?>
    <span class="ms-3 text-warning fw-semibold"><i class="bi bi-pin-fill me-1"></i>Siswa ber-PIN dijamin diterima & tidak tampil di publik</span>
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
<?php foreach ($target_jurusan as $jurusan):
    $list      = $all_data[$jurusan]['list'];
    $raport_map = $all_data[$jurusan]['raport'];
    $total_j   = count($list);
    $diterima_j = count(array_filter($list, fn($r) => $r['status']==='terima'));
    $pinned_count = count(array_filter($list, fn($r) => $r['is_pinned']));

    // Pisahkan: lolos_usia vs gugur_usia
    $lolos  = array_filter($list, fn($r) => $r['lolos_usia']);
    $gugur  = array_filter($list, fn($r) => !$r['lolos_usia']);

    // Dalam lolos: pinned dulu, lalu urut nilai — sudah terurut dari query (is_pinned DESC, nilai_akhir DESC)
    // Top = dalam kuota, below = di luar kuota
    $lolos_arr  = array_values($lolos);
    $top_arr    = [];
    $below_arr  = [];
    $pin_count_so_far = 0;
    $normal_count = 0;
    foreach ($lolos_arr as $r) {
        if ($r['is_pinned']) {
            $top_arr[] = $r;
            $pin_count_so_far++;
        } else {
            $normal_count++;
            if ($pin_count_so_far + $normal_count <= $kuota_glm) {
                $top_arr[] = $r;
            } else {
                $below_arr[] = $r;
            }
        }
    }
    $jurusan_id = preg_replace('/[^a-z0-9]/', '', strtolower($short[$jurusan]));
?>
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><?= htmlspecialchars($jurusan) ?></span>
        <span class="small text-muted">
            <?= $total_j ?> pendaftar |
            PIN: <span class="text-warning fw-bold"><?= $pinned_count ?></span> |
            Diterima: <span class="text-success fw-bold"><?= $diterima_j ?></span> /
            Kuota: <span class="fw-bold"><?= $kuota_glm ?></span>
        </span>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-center" style="width:40px">#</th>
                <th>No. Daftar</th>
                <th>Nama</th>
                <th>NISN</th>
                <th class="text-center">L/P</th>
                <th class="text-center">Raport (70%)</th>
                <th class="text-center">TKA (30%)</th>
                <th class="text-center">Nilai Akhir</th>
                <th class="text-center">Usia</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lolos_arr) && empty($gugur)): ?>
            <tr><td colspan="11" class="text-center py-3 text-muted">Belum ada pendaftar untuk jurusan ini.</td></tr>
        <?php else:
            $rank = 0;
            // ── Baris DALAM kuota ──────────────────────────────────────────
            foreach ($top_arr as $r):
                $rank++;
                $is_pinned = (bool)$r['is_pinned'];
                $badge = STATUS_BADGE[$r['status']] ?? 'bg-secondary';
                $label = STATUS_LABEL[$r['status']] ?? $r['status'];
                $rowClass = $is_pinned ? 'table-warning' :
                            ($r['status']==='terima' ? 'table-success' :
                            ($r['status']==='gugur' ? 'table-danger opacity-75' : ''));
                $raport_json = json_encode($raport_map[$r['id']] ?? [], JSON_UNESCAPED_UNICODE);
                $row_json = json_encode([
                    'id'                => $r['id'],
                    'no_pendaftaran'    => $r['no_pendaftaran'],
                    'nama'              => $r['nama'],
                    'nisn'              => $r['nisn'],
                    'tanggal_lahir'     => $r['tanggal_lahir'],
                    'usia'              => $r['usia'],
                    'jenis_kelamin'     => $r['jenis_kelamin'],
                    'asal_sekolah'      => $r['asal_sekolah'],
                    'no_telp'           => $r['no_telp'],
                    'alamat'            => $r['alamat'],
                    'sistem_pendidikan' => $r['sistem_pendidikan'],
                    'jurusan'           => $r['jurusan'],
                    'nilai_raport'      => $r['nilai_raport'],
                    'nilai_tka'         => $r['nilai_tka'],
                    'nilai_akhir'       => $r['nilai_akhir'],
                    'lolos_usia'        => $r['lolos_usia'],
                    'is_pinned'         => $r['is_pinned'],
                    'status'            => $r['status'],
                    'catatan'           => $r['catatan'],
                    'gelombang'         => $r['gelombang'],
                    'raport'            => $raport_map[$r['id']] ?? [],
                ], JSON_UNESCAPED_UNICODE);
            ?>
            <tr class="<?= $rowClass ?>">
                <td class="text-center text-muted small"><?= $rank ?></td>
                <td class="small"><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                <td>
                    <?= htmlspecialchars($r['nama']) ?>
                    <?php if ($is_pinned): ?><i class="bi bi-pin-fill text-warning ms-1" title="Pinned — dijamin diterima"></i><?php endif; ?>
                </td>
                <td class="small"><?= htmlspecialchars($r['nisn']) ?></td>
                <td class="text-center"><?= $r['jenis_kelamin'] ?></td>
                <td class="text-center"><?= number_format($r['nilai_raport'], 2) ?></td>
                <td class="text-center"><?= number_format($r['nilai_tka'], 2) ?></td>
                <td class="text-center fw-bold"><?= number_format($r['nilai_akhir'], 2) ?></td>
                <td class="text-center small"><?= $r['usia'] ?> thn</td>
                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size:0.75rem"
                                onclick='openViewModal(<?= htmlspecialchars($row_json, ENT_QUOTES) ?>)'>
                            <i class="bi bi-eye"></i>
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="pin">
                            <input type="hidden" name="pendaftar_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="pin_val" value="<?= $is_pinned ? 0 : 1 ?>">
                            <input type="hidden" name="gelombang" value="<?= $fGel ?>">
                            <input type="hidden" name="jurusan" value="<?= htmlspecialchars($fJurusan) ?>">
                            <button type="submit" class="btn btn-xs py-0 px-1 <?= $is_pinned ? 'btn-warning' : 'btn-outline-secondary' ?>"
                                    style="font-size:0.75rem"
                                    title="<?= $is_pinned ? 'Lepas PIN' : 'PIN (jamin diterima)' ?>"
                                    onclick="return confirm('<?= $is_pinned ? 'Lepas PIN siswa ini?' : 'PIN siswa ini? Siswa ber-PIN dijamin diterima dan tidak tampil di publik.' ?>')">
                                <i class="bi bi-pin<?= $is_pinned ? '-fill' : '' ?>"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach;

            // ── Garis batas kuota ──────────────────────────────────────────
            if (!empty($below_arr)): ?>
            <tr>
                <td colspan="11" class="p-0">
                    <button class="btn btn-sm w-100 rounded-0 py-1 text-muted bg-light border-0 border-top border-bottom"
                            style="font-size:0.8rem"
                            onclick="toggleBelowKuota('<?= $jurusan_id ?>')">
                        <i class="bi bi-chevron-down me-1" id="chevron-<?= $jurusan_id ?>"></i>
                        ── Batas Kuota (<?= $kuota_glm ?> terbaik) ──
                        <span class="text-danger fw-semibold"><?= count($below_arr) ?> tidak lolos</span>
                        — klik untuk tampilkan
                    </button>
                </td>
            </tr>
            <?php
            // ── Baris DI LUAR kuota (collapsed, abu-abu) ──────────────────
            foreach ($below_arr as $r):
                $rank++;
                $badge = STATUS_BADGE[$r['status']] ?? 'bg-secondary';
                $label = STATUS_LABEL[$r['status']] ?? $r['status'];
                $row_json = json_encode([
                    'id'                => $r['id'],
                    'no_pendaftaran'    => $r['no_pendaftaran'],
                    'nama'              => $r['nama'],
                    'nisn'              => $r['nisn'],
                    'tanggal_lahir'     => $r['tanggal_lahir'],
                    'usia'              => $r['usia'],
                    'jenis_kelamin'     => $r['jenis_kelamin'],
                    'asal_sekolah'      => $r['asal_sekolah'],
                    'no_telp'           => $r['no_telp'],
                    'alamat'            => $r['alamat'],
                    'sistem_pendidikan' => $r['sistem_pendidikan'],
                    'jurusan'           => $r['jurusan'],
                    'nilai_raport'      => $r['nilai_raport'],
                    'nilai_tka'         => $r['nilai_tka'],
                    'nilai_akhir'       => $r['nilai_akhir'],
                    'lolos_usia'        => $r['lolos_usia'],
                    'is_pinned'         => $r['is_pinned'],
                    'status'            => $r['status'],
                    'catatan'           => $r['catatan'],
                    'gelombang'         => $r['gelombang'],
                    'raport'            => $raport_map[$r['id']] ?? [],
                ], JSON_UNESCAPED_UNICODE);
            ?>
            <tr class="below-kuota-<?= $jurusan_id ?> text-muted" style="display:none; background:#f8f9fa;">
                <td class="text-center small"><?= $rank ?></td>
                <td class="small"><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                <td><?= htmlspecialchars($r['nama']) ?></td>
                <td class="small"><?= htmlspecialchars($r['nisn']) ?></td>
                <td class="text-center"><?= $r['jenis_kelamin'] ?></td>
                <td class="text-center"><?= number_format($r['nilai_raport'], 2) ?></td>
                <td class="text-center"><?= number_format($r['nilai_tka'], 2) ?></td>
                <td class="text-center fw-bold"><?= number_format($r['nilai_akhir'], 2) ?></td>
                <td class="text-center small"><?= $r['usia'] ?> thn</td>
                <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
                <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                        <button class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size:0.75rem"
                                onclick='openViewModal(<?= htmlspecialchars($row_json, ENT_QUOTES) ?>)'>
                            <i class="bi bi-eye"></i>
                        </button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="pin">
                            <input type="hidden" name="pendaftar_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="pin_val" value="1">
                            <input type="hidden" name="gelombang" value="<?= $fGel ?>">
                            <input type="hidden" name="jurusan" value="<?= htmlspecialchars($fJurusan) ?>">
                            <button type="submit" class="btn btn-xs btn-outline-secondary py-0 px-1"
                                    style="font-size:0.75rem" title="PIN (jamin diterima)"
                                    onclick="return confirm('PIN siswa ini? Siswa ber-PIN dijamin diterima dan tidak tampil di publik.')">
                                <i class="bi bi-pin"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif;

            // ── Gugur Usia (collapsed, merah pucat) ───────────────────────
            $gugur_arr = array_values($gugur);
            if (!empty($gugur_arr)): ?>
            <tr>
                <td colspan="11" class="p-0">
                    <button class="btn btn-sm w-100 rounded-0 py-1 text-danger bg-danger bg-opacity-10 border-0 border-top"
                            style="font-size:0.8rem"
                            onclick="toggleGugur('<?= $jurusan_id ?>')">
                        <i class="bi bi-chevron-down me-1" id="chevron-gugur-<?= $jurusan_id ?>"></i>
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <?= count($gugur_arr) ?> Pendaftar Gugur (Usia &gt; 21 Tahun) — klik untuk tampilkan
                    </button>
                </td>
            </tr>
            <?php foreach ($gugur_arr as $r):
                $row_json = json_encode([
                    'id'             => $r['id'],
                    'no_pendaftaran' => $r['no_pendaftaran'],
                    'nama'           => $r['nama'],
                    'nisn'           => $r['nisn'],
                    'tanggal_lahir'  => $r['tanggal_lahir'],
                    'usia'           => $r['usia'],
                    'jenis_kelamin'  => $r['jenis_kelamin'],
                    'asal_sekolah'   => $r['asal_sekolah'],
                    'no_telp'        => $r['no_telp'],
                    'alamat'         => $r['alamat'],
                    'sistem_pendidikan' => $r['sistem_pendidikan'],
                    'jurusan'        => $r['jurusan'],
                    'nilai_raport'   => $r['nilai_raport'],
                    'nilai_tka'      => $r['nilai_tka'],
                    'nilai_akhir'    => $r['nilai_akhir'],
                    'lolos_usia'     => $r['lolos_usia'],
                    'is_pinned'      => $r['is_pinned'],
                    'status'         => $r['status'],
                    'catatan'        => $r['catatan'],
                    'gelombang'      => $r['gelombang'],
                    'raport'         => $raport_map[$r['id']] ?? [],
                ], JSON_UNESCAPED_UNICODE);
            ?>
            <tr class="gugur-usia-<?= $jurusan_id ?>" style="display:none; background:#fff5f5;">
                <td class="text-center small text-danger">—</td>
                <td class="small text-danger"><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                <td class="text-danger"><?= htmlspecialchars($r['nama']) ?> <i class="bi bi-exclamation-triangle-fill text-danger ms-1"></i></td>
                <td class="small"><?= htmlspecialchars($r['nisn']) ?></td>
                <td class="text-center"><?= $r['jenis_kelamin'] ?></td>
                <td class="text-center"><?= number_format($r['nilai_raport'], 2) ?></td>
                <td class="text-center"><?= number_format($r['nilai_tka'], 2) ?></td>
                <td class="text-center fw-bold text-danger"><?= number_format($r['nilai_akhir'], 2) ?></td>
                <td class="text-center small text-danger fw-bold"><?= $r['usia'] ?> thn</td>
                <td><span class="badge bg-danger">Gugur</span></td>
                <td class="text-center">
                    <button class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:0.75rem"
                            onclick='openViewModal(<?= htmlspecialchars($row_json, ENT_QUOTES) ?>)'>
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>
<?php endforeach; ?>

<!-- ── Modal View Detail Pendaftar ──────────────────────────────────────────── -->
<div class="modal fade" id="modalViewPendaftar" tabindex="-1" aria-labelledby="modalViewLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalViewLabel"><i class="bi bi-person-lines-fill me-2"></i>Detail Pendaftar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalViewBody">
        <!-- diisi JS -->
      </div>
      <div class="modal-footer justify-content-between">
        <a href="#" id="modalViewEditLink" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil-square me-1"></i>Edit di Data Pendaftar
        </a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
// ── Toggle collapsed rows ────────────────────────────────────────────────────
function toggleBelowKuota(id) {
    const rows = document.querySelectorAll('.below-kuota-' + id);
    const chevron = document.getElementById('chevron-' + id);
    const hidden = rows[0] && rows[0].style.display === 'none';
    rows.forEach(r => r.style.display = hidden ? '' : 'none');
    if (chevron) chevron.className = 'bi me-1 ' + (hidden ? 'bi-chevron-up' : 'bi-chevron-down');
}

function toggleGugur(id) {
    const rows = document.querySelectorAll('.gugur-usia-' + id);
    const chevron = document.getElementById('chevron-gugur-' + id);
    const hidden = rows[0] && rows[0].style.display === 'none';
    rows.forEach(r => r.style.display = hidden ? '' : 'none');
    if (chevron) chevron.className = 'bi me-1 ' + (hidden ? 'bi-chevron-up' : 'bi-chevron-down');
}

// ── View Modal ───────────────────────────────────────────────────────────────
function openViewModal(d) {
    // Update edit link
    document.getElementById('modalViewEditLink').href = '?page=pendaftar&edit_id=' + d.id;

    // Build HTML
    const statusBadge = { terima: 'bg-success', gugur: 'bg-danger', diproses: 'bg-warning text-dark' };
    const badge = statusBadge[d.status] || 'bg-secondary';
    const pinBadge = d.is_pinned ? '<span class="badge bg-warning text-dark ms-1"><i class="bi bi-pin-fill"></i> PIN</span>' : '';
    const gugurBadge = !d.lolos_usia ? '<span class="badge bg-danger ms-1">Gugur Usia</span>' : '';

    let html = `
    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0">
                <tr><th class="text-muted fw-normal" style="width:45%">No. Pendaftaran</th><td><strong>${d.no_pendaftaran}</strong></td></tr>
                <tr><th class="text-muted fw-normal">Nama</th><td><strong>${esc(d.nama)}</strong>${pinBadge}${gugurBadge}</td></tr>
                <tr><th class="text-muted fw-normal">NISN</th><td>${esc(d.nisn)}</td></tr>
                <tr><th class="text-muted fw-normal">Tgl Lahir</th><td>${d.tanggal_lahir} (${d.usia} thn)</td></tr>
                <tr><th class="text-muted fw-normal">Jenis Kelamin</th><td>${d.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</td></tr>
                <tr><th class="text-muted fw-normal">Asal Sekolah</th><td>${esc(d.asal_sekolah)}</td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0">
                <tr><th class="text-muted fw-normal" style="width:45%">Gelombang</th><td>${d.gelombang}</td></tr>
                <tr><th class="text-muted fw-normal">Jurusan</th><td>${esc(d.jurusan)}</td></tr>
                <tr><th class="text-muted fw-normal">Sistem Pend.</th><td>${d.sistem_pendidikan === 'pkbm' ? 'PKBM (85% Raport)' : d.sistem_pendidikan === 'khusus' ? 'Daftar Khusus (85% Raport)' : 'Reguler (SMP)'}</td></tr>
                <tr><th class="text-muted fw-normal">No. Telp</th><td>${esc(d.no_telp) || '-'}</td></tr>
                <tr><th class="text-muted fw-normal">Alamat</th><td>${esc(d.alamat) || '-'}</td></tr>
                <tr><th class="text-muted fw-normal">Status</th><td><span class="badge ${badge}">${d.status}</span></td></tr>
            </table>
        </div>
    </div>
    <hr>
    <div class="row g-2 mb-3">
        <div class="col-md-4 text-center">
            <div class="fw-semibold text-muted small">Nilai Raport ${(d.sistem_pendidikan==='khusus'||d.sistem_pendidikan==='pkbm') ? '(85%)' : '(70%)'}</div>
            <div class="fs-4 fw-bold">${parseFloat(d.nilai_raport).toFixed(2)}</div>
        </div>
        <div class="col-md-4 text-center">
            <div class="fw-semibold text-muted small">${(d.sistem_pendidikan==='khusus'||d.sistem_pendidikan==='pkbm') ? 'Nilai TKA (N/A)' : 'Nilai TKA (30%)'}</div>
            <div class="fs-4 fw-bold">${parseFloat(d.nilai_tka).toFixed(2)}</div>
        </div>
        <div class="col-md-4 text-center">
            <div class="fw-semibold text-muted small">Nilai Akhir</div>
            <div class="fs-4 fw-bold text-primary">${parseFloat(d.nilai_akhir).toFixed(2)}</div>
        </div>
    </div>`;

    // Raport matrix
    if (d.raport && Object.keys(d.raport).length > 0) {
        html += `<hr><h6 class="mb-2">Detail Raport</h6>`;
        if (d.sistem_pendidikan === 'pkbm') {
            html += buildRaportTablePKBM(d.raport);
        } else {
            html += buildRaportTableRegular(d.raport);
        }
    }

    if (d.catatan) {
        html += `<div class="alert alert-warning small mt-3 mb-0"><strong>Catatan:</strong> ${esc(d.catatan)}</div>`;
    }

    document.getElementById('modalViewBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('modalViewPendaftar')).show();
}

function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function buildRaportTableRegular(raport) {
    const mapel = [
        'Pendidikan Agama dan Budi Pekerti',
        'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
        'Bahasa Indonesia','Matematika',
        'Ilmu Pengetahuan Alam (IPA)','Ilmu Pengetahuan Sosial (IPS)','Bahasa Inggris'
    ];
    const smt = [1,2,3,4,5,6];
    let html = '<div class="table-responsive"><table class="table table-sm table-bordered mb-0 small">';
    html += '<thead class="table-light"><tr><th>Mata Pelajaran</th>';
    smt.forEach(s => html += `<th class="text-center">Smt ${s}</th>`);
    html += '<th class="text-center">Rata</th></tr></thead><tbody>';
    mapel.forEach(mp => {
        html += `<tr><td>${esc(mp)}</td>`;
        let sum = 0, cnt = 0;
        smt.forEach(s => {
            const v = raport[mp] && raport[mp][s] != null ? raport[mp][s] : null;
            html += `<td class="text-center">${v !== null ? parseFloat(v).toFixed(1) : '<span class="text-muted">-</span>'}</td>`;
            if (v !== null) { sum += parseFloat(v); cnt++; }
        });
        html += `<td class="text-center fw-semibold">${cnt > 0 ? (sum/cnt).toFixed(2) : '-'}</td></tr>`;
    });
    html += '</tbody></table></div>';
    return html;
}

function buildRaportTablePKBM(raport) {
    const mapelUmum = [
        'Pendidikan Agama dan Budi Pekerti',
        'Pendidikan Pancasila dan Kewarganegaraan (PPKn)',
        'Bahasa Indonesia','Bahasa Inggris','Matematika',
        'Ilmu Pengetahuan Alam (IPA)','Ilmu Pengetahuan Sosial (IPS)'
    ];
    const mapelKhusus = ['Pemberdayaan','Keterampilan'];
    const tingkat = {1:'Tingkat 3 (Kls VII–VIII)', 2:'Tingkat 4 (Kls IX)'};

    function buildSection(title, mapelList) {
        let html = `<div class="fw-semibold small mb-1 mt-2">${title}</div>`;
        html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0 small">';
        html += '<thead class="table-light"><tr><th>Mata Pelajaran</th>';
        Object.entries(tingkat).forEach(([k,v]) => html += `<th class="text-center">${esc(v)}</th>`);
        html += '<th class="text-center">Rata</th></tr></thead><tbody>';
        mapelList.forEach(mp => {
            html += `<tr><td>${esc(mp)}</td>`;
            let sum = 0, cnt = 0;
            Object.keys(tingkat).forEach(t => {
                const v = raport[mp] && raport[mp][t] != null ? raport[mp][t] : null;
                html += `<td class="text-center">${v !== null ? parseFloat(v).toFixed(1) : '<span class="text-muted">-</span>'}</td>`;
                if (v !== null) { sum += parseFloat(v); cnt++; }
            });
            html += `<td class="text-center fw-semibold">${cnt > 0 ? (sum/cnt).toFixed(2) : '-'}</td></tr>`;
        });
        html += '</tbody></table></div>';
        return html;
    }

    return buildSection('Kelompok Umum', mapelUmum) + buildSection('Kelompok Khusus', mapelKhusus);
}
</script>
