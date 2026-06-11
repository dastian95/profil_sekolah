<?php
$jurusan_list = JURUSAN_LIST;
$short        = JURUSAN_SHORT;
$mapel_list   = MATA_PELAJARAN;
$semester_list = SEMESTER_LIST;

$err = '';

// ─── Helper: hitung usia & nilai_akhir ──────────────────────────────────────
function hitungPendaftar(array $data, float $rata_raport, string $sistem = 'reguler'): array {
    $lahir       = new DateTime($data['tanggal_lahir']);
    $sekarang    = new DateTime();
    $usia        = (int)$lahir->diff($sekarang)->y;
    // Daftar Khusus: hanya berdasarkan pilihan sistem, bukan paksa dari usia
    $is_khusus   = ($sistem === 'khusus');
    $nilai_akhir = $is_khusus
        ? round($rata_raport, 4)
        : round(($rata_raport * 0.70) + ($data['nilai_tka'] * 0.30), 4);
    $lolos_usia  = ($usia <= 21) ? 1 : 0;
    return array_merge($data, [
        'usia'         => $usia,
        'nilai_raport' => round($rata_raport, 4),
        'nilai_tka'    => $is_khusus ? 0 : $data['nilai_tka'],
        'nilai_akhir'  => $nilai_akhir,
        'lolos_usia'   => $lolos_usia,
    ]);
}

// ─── Helper: rata-rata raport dari matrix ───────────────────────────────────
function rataRaportFromMatrix(array $matrix): float {
    $sum = 0; $cnt = 0;
    foreach ($matrix as $row) {
        foreach ($row as $v) {
            if ($v !== '' && $v !== null) { $sum += (float)$v; $cnt++; }
        }
    }
    return $cnt > 0 ? $sum / $cnt : 0;
}

// ─── Helper: simpan matrix raport ───────────────────────────────────────────
function saveRaportMatrix(PDO $conn, int $pendaftar_id, array $matrix, array $mapel_list, array $semester_list): void {
    $conn->prepare("DELETE FROM pendaftar_raport WHERE pendaftar_id=?")->execute([$pendaftar_id]);
    $ins = $conn->prepare("INSERT INTO pendaftar_raport (pendaftar_id, mata_pelajaran, semester, nilai) VALUES (?, ?, ?, ?)");
    foreach ($mapel_list as $mp) {
        foreach ($semester_list as $s) {
            $v = $matrix[$mp][$s] ?? '';
            if ($v !== '' && is_numeric($v)) {
                $ins->execute([$pendaftar_id, $mp, $s, (float)$v]);
            }
        }
    }
}

// ─── Helper: load matrix raport ─────────────────────────────────────────────
function loadRaportMatrix(PDO $conn, int $pendaftar_id, array $mapel_list, array $semester_list): array {
    $matrix = [];
    foreach ($mapel_list as $mp) {
        foreach ($semester_list as $s) {
            $matrix[$mp][$s] = '';
        }
    }
    $stmt = $conn->prepare("SELECT mata_pelajaran, semester, nilai FROM pendaftar_raport WHERE pendaftar_id=?");
    $stmt->execute([$pendaftar_id]);
    foreach ($stmt as $r) {
        $matrix[$r['mata_pelajaran']][(int)$r['semester']] = (float)$r['nilai'];
    }
    return $matrix;
}

// ─── Generate no_pendaftaran ────────────────────────────────────────────────
function generateNoPendaftaran(PDO $conn, int $gelombang): string {
    $tahun  = date('Y');
    $prefix = "SPMB-{$tahun}-G{$gelombang}-";
    $last   = $conn->prepare("SELECT no_pendaftaran FROM pendaftar WHERE gelombang=? ORDER BY id DESC LIMIT 1");
    $last->execute([$gelombang]);
    $row    = $last->fetchColumn();
    $seq    = $row ? ((int)substr($row, -4) + 1) : 1;
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

// Tentukan gelombang aktif (auto) — dipakai untuk default saat tambah baru
$gelombang_aktif = getActiveGelombang($conn);

// Flash messages dari PRG redirect (cegah duplikasi submit)
$msg = '';
if (!empty($_SESSION['pend_flash_msg'])) {
    $msg = $_SESSION['pend_flash_msg'];
    unset($_SESSION['pend_flash_msg']);
}
$pend_print_id = 0;
if (!empty($_SESSION['pend_print_id'])) {
    $pend_print_id = (int)$_SESSION['pend_print_id'];
    unset($_SESSION['pend_print_id']);
}

// Data untuk preserve form saat validasi gagal
$formData   = [];
$formRaport = [];
$formAction = 'add';
$formId     = '';
$showModalOnLoad = false;
$printAfterSave = null; // row pendaftar baru yang perlu langsung dicetak

// Auto-migrate: tambah kolom tgl_kk jika belum ada
try {
    $conn->query("SELECT tgl_kk FROM pendaftar LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("ALTER TABLE pendaftar ADD COLUMN tgl_kk DATE NULL AFTER no_telp");
}
// Auto-migrate: tambah status 'lengkap' ke enum
try {
    $conn->exec("ALTER TABLE pendaftar MODIFY COLUMN status ENUM('diproses','lengkap','gugur','terima') NOT NULL DEFAULT 'diproses'");
} catch (PDOException $e) {}

// ─── POST: Tambah / Edit / Hapus ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        // Tab 1 fields selalu wajib
        $required = ['nama','nisn','tanggal_lahir','jenis_kelamin','asal_sekolah','jurusan'];
        $missing  = array_filter($required, fn($f) => empty($_POST[$f]) && $_POST[$f] !== '0');
        if ($missing) {
            $err = 'Field wajib belum diisi: ' . implode(', ', $missing);
        } else {
            $tgl_kk = $_POST['tgl_kk'] ?? '';
            $sistem = in_array($_POST['sistem_pendidikan'] ?? '', ['reguler','pkbm','khusus'])
                ? $_POST['sistem_pendidikan'] : 'reguler';

            // Cek apakah raport diisi
            if ($sistem === 'pkbm') {
                $matrix       = $_POST['pkbm_raport'] ?? [];
                $mapel_active = PKBM_MAPEL_UMUM;
                $sem_active   = array_keys(PKBM_TINGKAT);
            } else {
                $matrix       = $_POST['raport'] ?? [];
                $mapel_active = $mapel_list;
                $sem_active   = $semester_list;
            }
            $rata = rataRaportFromMatrix($matrix);
            // Mode input rata-rata langsung: pakai nilai manual jika matrix kosong
            if ($rata == 0 && isset($_POST['nilai_raport_manual'])) {
                $manual = (float)$_POST['nilai_raport_manual'];
                if ($manual > 0) $rata = min(100, max(0, $manual));
            }
            $has_raport = $rata > 0;

            // Jika raport diisi, TKA wajib (kecuali Khusus)
            if ($has_raport && $sistem !== 'khusus' && empty($_POST['nilai_tka']) && ($_POST['nilai_tka'] ?? '') !== '0') {
                $err = 'Nilai TKA wajib diisi jika raport sudah dilengkapi.';
            }

            if (!$err) {
                // Tentukan gelombang
                $gel = 0;
                $existing_status = 'diproses';
                if ($action === 'add') {
                    if (!$gelombang_aktif) {
                        $err = 'Tidak ada gelombang aktif. Atur tanggal gelombang di menu Pengaturan Gelombang.';
                    } else {
                        $gel = (int)$gelombang_aktif['gelombang'];
                    }
                } else {
                    $cur = $conn->prepare("SELECT gelombang, status FROM pendaftar WHERE id=?");
                    $cur->execute([(int)$_POST['id']]);
                    $existing = $cur->fetch();
                    $gel = (int)$existing['gelombang'];
                    $existing_status = $existing['status'] ?? 'diproses';
                }
            }

            if (!$err) {
                // Hitung data pendaftar
                if ($has_raport) {
                    $d = hitungPendaftar([
                        'nama'          => trim($_POST['nama']),
                        'nisn'          => trim($_POST['nisn']),
                        'tanggal_lahir' => $_POST['tanggal_lahir'],
                        'jenis_kelamin' => $_POST['jenis_kelamin'],
                        'asal_sekolah'  => trim($_POST['asal_sekolah']),
                        'no_telp'       => trim($_POST['no_telp'] ?? ''),
                        'tgl_kk'        => $tgl_kk,
                        'alamat'        => trim($_POST['alamat'] ?? ''),
                        'jurusan'       => $_POST['jurusan'],
                        'gelombang'     => $gel,
                        'nilai_tka'     => $sistem === 'khusus' ? 0 : (float)$_POST['nilai_tka'],
                    ], $rata, $sistem);
                    $new_status = 'lengkap';
                } else {
                    // Tab 1 only — hitung usia & lolos_usia saja
                    $lahir      = new DateTime($_POST['tanggal_lahir']);
                    $usia       = (int)$lahir->diff(new DateTime())->y;
                    $sistem     = 'reguler';
                    $d = [
                        'nama'          => trim($_POST['nama']),
                        'nisn'          => trim($_POST['nisn']),
                        'tanggal_lahir' => $_POST['tanggal_lahir'],
                        'usia'          => $usia,
                        'jenis_kelamin' => $_POST['jenis_kelamin'],
                        'asal_sekolah'  => trim($_POST['asal_sekolah']),
                        'no_telp'       => trim($_POST['no_telp'] ?? ''),
                        'tgl_kk'        => $tgl_kk,
                        'alamat'        => trim($_POST['alamat'] ?? ''),
                        'jurusan'       => $_POST['jurusan'],
                        'gelombang'     => $gel,
                        'nilai_raport'  => 0,
                        'nilai_tka'     => 0,
                        'nilai_akhir'   => 0,
                        'lolos_usia'    => $usia <= 21 ? 1 : 0,
                    ];
                    // Saat edit: hanya upgrade ke 'lengkap', jangan downgrade dari 'terima'/'gugur'
                    $new_status = ($action === 'add') ? 'diproses' : $existing_status;
                }

                if ($action === 'add') {
                    $no = generateNoPendaftaran($conn, $d['gelombang']);
                    $stmt = $conn->prepare("INSERT INTO pendaftar
                        (no_pendaftaran,gelombang,nama,nisn,tanggal_lahir,usia,jenis_kelamin,asal_sekolah,no_telp,tgl_kk,alamat,jurusan,sistem_pendidikan,nilai_raport,nilai_tka,nilai_akhir,lolos_usia,status)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$no,$d['gelombang'],$d['nama'],$d['nisn'],$d['tanggal_lahir'],$d['usia'],
                        $d['jenis_kelamin'],$d['asal_sekolah'],$d['no_telp'],$tgl_kk,$d['alamat'],$d['jurusan'],
                        $sistem,$d['nilai_raport'],$d['nilai_tka'],$d['nilai_akhir'],$d['lolos_usia'],$new_status]);
                    $id = (int)$conn->lastInsertId();
                    if ($has_raport) saveRaportMatrix($conn, $id, $matrix, $mapel_active, $sem_active);

                    log_admin_action($conn, 'TAMBAH_PENDAFTAR', "Tambah pendaftar: {$d['nama']} ({$no}) [{$new_status}]");
                    $_SESSION['pend_flash_msg'] = "Pendaftar <strong>{$d['nama']}</strong> berhasil ditambahkan dengan nomor <strong>{$no}</strong>.";
                    if (!empty($_POST['print_after_save'])) {
                        $_SESSION['pend_print_id'] = $id;
                    }
                    $glm_qs = !empty($_SESSION['pend_active_gelombang']) ? '&gelombang=' . urlencode($_SESSION['pend_active_gelombang']) : '';
                    echo '<script>window.location.replace("admin_dashboard.php?page=pendaftar' . $glm_qs . '")</script>';
                    return;
                } else {
                    $id = (int)$_POST['id'];
                    $stmt = $conn->prepare("UPDATE pendaftar SET
                        gelombang=?,nama=?,nisn=?,tanggal_lahir=?,usia=?,jenis_kelamin=?,asal_sekolah=?,
                        no_telp=?,tgl_kk=?,alamat=?,jurusan=?,sistem_pendidikan=?,nilai_raport=?,nilai_tka=?,nilai_akhir=?,lolos_usia=?,status=?
                        WHERE id=?");
                    $stmt->execute([$d['gelombang'],$d['nama'],$d['nisn'],$d['tanggal_lahir'],$d['usia'],
                        $d['jenis_kelamin'],$d['asal_sekolah'],$d['no_telp'],$tgl_kk,$d['alamat'],$d['jurusan'],
                        $sistem,$d['nilai_raport'],$d['nilai_tka'],$d['nilai_akhir'],$d['lolos_usia'],$new_status,$id]);
                    if ($has_raport) saveRaportMatrix($conn, $id, $matrix, $mapel_active, $sem_active);

                    log_admin_action($conn, 'EDIT_PENDAFTAR', "Edit pendaftar ID:{$id} — {$d['nama']} [{$new_status}]");
                    $_SESSION['pend_flash_msg'] = "Data <strong>{$d['nama']}</strong> berhasil diperbarui.";
                    $glm_qs = !empty($_SESSION['pend_active_gelombang']) ? '&gelombang=' . urlencode($_SESSION['pend_active_gelombang']) : '';
                    echo '<script>window.location.replace("admin_dashboard.php?page=pendaftar' . $glm_qs . '")</script>';
                    return;
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $row = $conn->prepare("SELECT nama, no_pendaftaran FROM pendaftar WHERE id=?");
        $row->execute([$id]);
        $del = $row->fetch();
        $conn->prepare("DELETE FROM pendaftar WHERE id=?")->execute([$id]);
        log_admin_action($conn, 'HAPUS_PENDAFTAR', "Hapus: {$del['nama']} ({$del['no_pendaftaran']})");
        $_SESSION['pend_flash_msg'] = "Pendaftar <strong>{$del['nama']}</strong> berhasil dihapus.";
        $glm_qs = !empty($_SESSION['pend_active_gelombang']) ? '&gelombang=' . urlencode($_SESSION['pend_active_gelombang']) : '';
        echo '<script>window.location.replace("admin_dashboard.php?page=pendaftar' . $glm_qs . '")</script>';
        return;
    }

    // Preserve form data so modal reopens with filled values when validation fails
    if ($err) {
        $formData        = $_POST;
        $formRaport      = $_POST['raport'] ?? [];
        $formAction      = $action;
        $formId          = $_POST['id'] ?? '';
        $showModalOnLoad = true;
    }
}

// ─── Session-based active gelombang (persist setelah reload/tutup tab) ──────
if (isset($_GET['gelombang'])) {
    $glm_val = $_GET['gelombang'];
    if (in_array($glm_val, ['1','2',''], true)) {
        $_SESSION['pend_active_gelombang'] = $glm_val;
    }
}
$active_glm = $_SESSION['pend_active_gelombang'] ?? '';

// ─── Filter & paginasi ──────────────────────────────────────────────────────
$fJurusan  = $_GET['jurusan']  ?? '';
$fGelombang= $active_glm; // pakai session, bukan GET langsung
$fStatus   = $_GET['status']   ?? '';
$fCari     = trim($_GET['cari']?? '');
$page_num  = max(1, (int)($_GET['p'] ?? 1));
$per_page  = 20;

$where = ['1=1']; $params = [];
if ($fJurusan)   { $where[] = 'jurusan=?';  $params[] = $fJurusan; }
if ($fGelombang) { $where[] = 'gelombang=?';$params[] = $fGelombang; }
if ($fStatus)    { $where[] = 'status=?';   $params[] = $fStatus; }
if ($fCari)      { $where[] = '(nama LIKE ? OR nisn LIKE ? OR no_pendaftaran LIKE ?)'; $params = array_merge($params, ["%$fCari%","%$fCari%","%$fCari%"]); }

$whereStr = implode(' AND ', $where);
$countStmt = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE $whereStr");
$countStmt->execute($params);
$total_rows = $countStmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$offset = ($page_num - 1) * $per_page;

$dataStmt = $conn->prepare("SELECT * FROM pendaftar WHERE $whereStr ORDER BY nilai_akhir DESC, usia DESC LIMIT $per_page OFFSET $offset");
$dataStmt->execute($params);
$rows = $dataStmt->fetchAll();

// Hitung jumlah pendaftar per gelombang untuk badge tab
$s1 = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=1");
$s1->execute(); $glm_counts[1] = (int)$s1->fetchColumn();
$s2 = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE gelombang=2");
$s2->execute(); $glm_counts[2] = (int)$s2->fetchColumn();

// Untuk JS edit form, kita perlu data raport per pendaftar — load semua sekaligus untuk halaman ini
$raport_per_pendaftar = [];
if ($rows) {
    $ids = array_column($rows, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT pendaftar_id, mata_pelajaran, semester, nilai FROM pendaftar_raport WHERE pendaftar_id IN ($in)");
    $stmt->execute($ids);
    foreach ($stmt as $r) {
        $raport_per_pendaftar[$r['pendaftar_id']][$r['mata_pelajaran']][(int)$r['semester']] = (float)$r['nilai'];
    }
}
// Load print data jika ada flash print_id dari PRG
if ($pend_print_id > 0) {
    $ps = $conn->prepare("SELECT * FROM pendaftar WHERE id=?");
    $ps->execute([$pend_print_id]);
    $printAfterSave = $ps->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Auto-open edit modal jika redirect dari Ranking
$editFromRanking = null;
$edit_id_get = (int)($_GET['edit_id'] ?? 0);
if ($edit_id_get > 0) {
    $s = $conn->prepare("SELECT * FROM pendaftar WHERE id=?");
    $s->execute([$edit_id_get]);
    $editFromRanking = $s->fetch();
    if ($editFromRanking) {
        $rs = $conn->prepare("SELECT mata_pelajaran, semester, nilai FROM pendaftar_raport WHERE pendaftar_id=?");
        $rs->execute([$edit_id_get]);
        $rm = [];
        foreach ($rs as $r) { $rm[$r['mata_pelajaran']][(int)$r['semester']] = (float)$r['nilai']; }
        $editFromRanking['raport_matrix'] = $rm;
    }
}
?>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Gelombang Tabs -->
<div class="d-flex align-items-center justify-content-between mb-0 flex-wrap gap-2">
    <ul class="nav nav-tabs border-bottom-0" id="gelombangTabs" style="flex-wrap:nowrap;">
        <?php
        $tab_opts = ['' => 'Semua', '1' => 'Gelombang 1', '2' => 'Gelombang 2'];
        foreach ($tab_opts as $gval => $glabel):
            $isActive = ($active_glm === (string)$gval);
            $badge = $gval !== '' ? '<span class="badge bg-secondary ms-1">' . ($glm_counts[(int)$gval] ?? 0) . '</span>' : '';
        ?>
        <li class="nav-item">
            <a class="nav-link <?= $isActive ? 'active fw-semibold' : '' ?> glm-tab-link"
               href="#" data-glm="<?= $gval ?>" data-current="<?= $active_glm ?>">
                <?= $glabel ?><?= $badge ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <!-- Badge status aktif -->
    <?php if ($active_glm !== ''): ?>
    <span class="badge bg-primary fs-6 py-2 px-3">
        <i class="bi bi-funnel-fill me-1"></i>Gelombang <?= $active_glm ?> aktif
    </span>
    <?php endif; ?>
</div>

<!-- Tombol Tambah + Filter -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center justify-content-between border rounded-bottom p-2 bg-white">
    <div class="d-flex gap-2">
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPendaftar" onclick="resetForm()">
            <i class="bi bi-plus-lg me-1"></i>Tambah Pendaftar
        </button>
        <button class="btn btn-outline-secondary btn-sm" onclick="loadTemplate()">
            <i class="bi bi-file-earmark-text me-1"></i>Template
        </button>
    </div>
    <form class="d-flex flex-wrap gap-2" method="GET">
        <input type="hidden" name="page" value="pendaftar">
        <input type="hidden" name="gelombang" value="<?= htmlspecialchars($active_glm) ?>">
        <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama / NISN..." value="<?= htmlspecialchars($fCari) ?>" style="width:180px">
        <select name="jurusan" class="form-select form-select-sm" style="width:auto">
            <option value="">Semua Jurusan</option>
            <?php foreach ($jurusan_list as $j): ?>
            <option value="<?= htmlspecialchars($j) ?>" <?= $fJurusan===$j?'selected':'' ?>><?= $short[$j] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <option value="">Semua Status</option>
            <option value="diproses" <?= $fStatus==='diproses'?'selected':'' ?>>Diproses</option>
            <option value="lengkap"  <?= $fStatus==='lengkap' ?'selected':'' ?>>Lengkap</option>
            <option value="terima"   <?= $fStatus==='terima'  ?'selected':'' ?>>Terima</option>
            <option value="gugur"    <?= $fStatus==='gugur'   ?'selected':'' ?>>Gugur</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="?page=pendaftar&gelombang=<?= $active_glm ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
    </form>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center small">
        <span>Total: <strong><?= $total_rows ?></strong> pendaftar</span>
        <span>Halaman <?= $page_num ?> / <?= $total_pages ?></span>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>No. Daftar</th><th>Nama</th><th>NISN</th><th class="text-center">L/P</th>
                <th>Jurusan</th><th class="text-center">Glm</th>
                <th class="text-center">Raport</th><th class="text-center">TKA</th>
                <th class="text-center">Nilai Akhir</th><th class="text-center">Usia</th>
                <th>Status</th><th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="12" class="text-center py-4 text-muted">Tidak ada data.</td></tr>
        <?php else: foreach ($rows as $r):
            $badge = match($r['status']) { 'terima'=>'bg-success', 'gugur'=>'bg-danger', default=>'bg-warning text-dark' };
            $gugur = !$r['lolos_usia'];
            // Sertakan matrix raport ke data row
            $r_with_raport = $r;
            $r_with_raport['raport_matrix'] = $raport_per_pendaftar[$r['id']] ?? [];
        ?>
            <tr class="<?= $gugur ? 'table-secondary text-muted' : '' ?>">
                <td><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                <td>
                    <?= htmlspecialchars($r['nama']) ?>
                    <?php if ($gugur): ?><i class="bi bi-exclamation-circle text-danger" title="Gugur: usia > 21"></i><?php endif; ?>
                    <?php if (($r['sistem_pendidikan'] ?? '') === 'khusus'): ?>
                      <span class="badge bg-warning text-dark ms-1" title="Daftar Khusus — 100% Raport">Khusus</span>
                    <?php elseif (($r['sistem_pendidikan'] ?? '') === 'pkbm'): ?>
                      <span class="badge bg-info text-dark ms-1">PKBM</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['nisn']) ?></td>
                <td class="text-center"><?= $r['jenis_kelamin'] ?></td>
                <td><?= $short[$r['jurusan']] ?? $r['jurusan'] ?></td>
                <td class="text-center"><?= $r['gelombang'] ?></td>
                <td class="text-center"><?= number_format($r['nilai_raport'], 2) ?></td>
                <td class="text-center"><?= number_format($r['nilai_tka'], 2) ?></td>
                <td class="text-center fw-bold text-success"><?= number_format($r['nilai_akhir'], 2) ?></td>
                <td class="text-center"><?= $r['usia'] ?></td>
                <td><span class="badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-info me-1" onclick='printBukti(<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Cetak Bukti"><i class="bi bi-printer"></i></button>
                    <button class="btn btn-sm btn-outline-primary" onclick='editForm(<?= json_encode($r_with_raport, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' title="Edit"><i class="bi bi-pencil"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus pendaftar ini? Detail raport akan ikut terhapus.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex justify-content-center">
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i===$page_num?'active':'' ?>">
                <a class="page-link" href="?page=pendaftar&p=<?= $i ?>&jurusan=<?= urlencode($fJurusan) ?>&gelombang=<?= $fGelombang ?>&status=<?= $fStatus ?>&cari=<?= urlencode($fCari) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah/Edit Pendaftar -->
<div class="modal fade" id="modalPendaftar" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalTitle">Tambah Pendaftar</h5>
      </div>
      <form method="POST" id="formPendaftar" novalidate>
        <input type="hidden" name="action" id="formAction" value="<?= htmlspecialchars($formAction) ?>">
        <input type="hidden" name="id" id="formId" value="<?= htmlspecialchars($formId) ?>">

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs px-3 pt-2" role="tablist">
          <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabDiri"><i class="bi bi-person me-1"></i> Data Diri</button></li>
          <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRaport"><i class="bi bi-table me-1"></i> Detail Raport</button></li>
        </ul>

        <div class="modal-body">
        <div class="tab-content">

          <!-- ── Tab Data Diri ───────────────────────────────────────────── -->
          <div class="tab-pane fade show active" id="tabDiri">
            <?php if ($gelombang_aktif): ?>
            <div class="alert alert-success small d-flex align-items-center gap-3 py-2">
              <i class="bi bi-broadcast fs-5"></i>
              <div class="flex-fill">
                <strong>Gelombang Aktif: <?= $gelombang_aktif['gelombang'] ?></strong>
                <span class="text-muted">— Sistem otomatis menetapkan gelombang ini untuk pendaftar baru.</span>
              </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning small">
              <i class="bi bi-exclamation-triangle me-1"></i>Tidak ada gelombang aktif saat ini. Atur tanggal di menu <strong>Pengaturan Gelombang</strong>.
            </div>
            <?php endif; ?>

            <div class="row g-3">
              <div class="col-md-7">
                <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" id="fNama" class="form-control" value="<?= htmlspecialchars($formData['nama'] ?? '') ?>" required>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">NISN <span class="text-danger">*</span></label>
                <input type="text" name="nisn" id="fNisn" class="form-control" value="<?= htmlspecialchars($formData['nisn'] ?? '') ?>" maxlength="20" required>
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">L/P <span class="text-danger">*</span></label>
                <select name="jenis_kelamin" id="fJK" class="form-select" required>
                  <option value="L" <?= ($formData['jenis_kelamin'] ?? 'L') === 'L' ? 'selected' : '' ?>>L</option>
                  <option value="P" <?= ($formData['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>P</option>
                </select>
              </div>
              <div class="col-md-7">
                <label class="form-label fw-semibold">Jurusan Pilihan <span class="text-danger">*</span></label>
                <select name="jurusan" id="fJurusan" class="form-select" required>
                  <?php foreach ($jurusan_list as $j): ?>
                  <option value="<?= htmlspecialchars($j) ?>" <?= ($formData['jurusan'] ?? '') === $j ? 'selected' : '' ?>><?= htmlspecialchars($j) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_lahir" id="fTgl" class="form-control" value="<?= htmlspecialchars($formData['tanggal_lahir'] ?? '') ?>" required onchange="hitungUsia()">
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">Umur</label>
                <input type="text" id="previewUsia" class="form-control bg-light" readonly placeholder="auto" style="font-size:.82rem;">
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold">Asal Sekolah <span class="text-danger">*</span></label>
                <input type="text" name="asal_sekolah" id="fAsal" class="form-control" value="<?= htmlspecialchars($formData['asal_sekolah'] ?? '') ?>" placeholder="contoh: SMPN 1 Jakarta" required>
              </div>
              <div class="col-md-2">
                <label class="form-label fw-semibold">No. Telepon</label>
                <input type="text" name="no_telp" id="fTelp" class="form-control" value="<?= htmlspecialchars($formData['no_telp'] ?? '') ?>" placeholder="08...">
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">
                  Tanggal KK
                  <i class="bi bi-info-circle text-muted ms-1" title="Tanggal penerbitan Kartu Keluarga (KK) DKI Jakarta. Harus ≤ 15 Juni 2025." style="cursor:help;font-style:normal;"></i>
                </label>
                <input type="date" name="tgl_kk" id="fTglKk" class="form-control" value="<?= htmlspecialchars($formData['tgl_kk'] ?? '') ?>" onchange="cekCutoffKk(this.value)">
                <div id="kkWarning" class="text-danger small mt-1 d-none"><i class="bi bi-x-circle me-1"></i>KK melebihi cut-off 15 Juni 2025.</div>
                <small class="text-muted">Cut-off: 15 Juni 2025</small>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold">Alamat</label>
                <textarea name="alamat" id="fAlamat" class="form-control" rows="2"><?= htmlspecialchars($formData['alamat'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Ringkasan Auto-Calc (dihitung dari Tab "Data Raport") -->
            <hr>
            <div class="row g-2 mt-2">
              <div class="col-md-3">
                <div class="card bg-light border-0 h-100">
                  <div class="card-body py-2 px-3 text-center">
                    <small class="text-muted d-block">Rata-rata Raport</small>
                    <strong class="text-success fs-5" id="displayRata">—</strong>
                    <small class="text-muted d-block" style="font-size:.7rem;">auto dari matrix</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light border-0 h-100">
                  <div class="card-body py-2 px-3 text-center">
                    <small class="text-muted d-block">Nilai TKA</small>
                    <strong class="text-primary fs-5" id="displayTka">—</strong>
                    <small class="text-muted d-block" style="font-size:.7rem;">dari tab Data Raport</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white border-0 h-100">
                  <div class="card-body py-2 px-3 text-center">
                    <small class="opacity-75 d-block">Nilai Akhir</small>
                    <strong class="fs-5" id="displayAkhir">—</strong>
                    <small class="opacity-75 d-block" style="font-size:.7rem;" id="formulaLabel">R×70% + T×30%</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-light border-0 h-100">
                  <div class="card-body py-2 px-3 text-center">
                    <small class="text-muted d-block">Status</small>
                    <strong class="text-warning fs-6">Diproses</strong>
                    <small class="text-muted d-block" style="font-size:.7rem;">otomatis saat tambah</small>
                  </div>
                </div>
              </div>
            </div>

            <div class="alert alert-info mt-3 mb-0 small py-2">
              <i class="bi bi-arrow-right-circle me-1"></i>
              Lanjut ke tab <strong>"Data Raport"</strong> untuk input Tanggal Lahir, Asal Sekolah, Nilai TKA, dan detail nilai raport.
            </div>
          </div>

          <!-- ── Tab Data Raport ─────────────────────────────────────────── -->
          <div class="tab-pane fade" id="tabRaport">

            <!-- Sistem Penilaian -->
            <div class="p-3 mb-3 rounded border bg-light d-flex align-items-center gap-4 flex-wrap">
              <span class="fw-semibold small"><i class="bi bi-mortarboard text-success me-1"></i>Sistem Penilaian:</span>
              <div class="form-check mb-0">
                <input class="form-check-input" type="radio" name="sistem_pendidikan" id="sistemReguler" value="reguler"
                       <?= ($formData['sistem_pendidikan'] ?? 'reguler') === 'reguler' ? 'checked' : '' ?>
                       onchange="switchSistem('reguler')">
                <label class="form-check-label" for="sistemReguler">
                  <strong>Reguler</strong> <small class="text-muted">— Raport 70% + TKA 30%</small>
                </label>
              </div>
              <div class="form-check mb-0">
                <input class="form-check-input" type="radio" name="sistem_pendidikan" id="sistemPKBM" value="pkbm"
                       <?= ($formData['sistem_pendidikan'] ?? '') === 'pkbm' ? 'checked' : '' ?>
                       onchange="switchSistem('pkbm')">
                <label class="form-check-label" for="sistemPKBM">
                  <strong>PKBM</strong> <small class="text-muted">— Paket B Setara SMP</small>
                </label>
              </div>
              <div class="form-check mb-0">
                <input class="form-check-input" type="radio" name="sistem_pendidikan" id="sistemKhusus" value="khusus"
                       <?= ($formData['sistem_pendidikan'] ?? '') === 'khusus' ? 'checked' : '' ?>
                       onchange="switchSistem('khusus')">
                <label class="form-check-label" for="sistemKhusus">
                  <strong class="text-warning">Daftar Khusus</strong>
                  <small class="text-muted">— 100% Raport, tanpa TKA (usia ≥ <?= KHUSUS_MIN_USIA ?> thn)</small>
                </label>
              </div>
            </div>
            <!-- notifKhusus: aktif = sedang pilih Khusus; notifKhususWarn: usia ≥17 tapi belum pilih Khusus -->
            <div id="notifKhusus" class="alert alert-success py-2 small mb-3 d-none">
              <i class="bi bi-check-circle-fill me-1"></i>
              <strong>Daftar Khusus aktif</strong> — Nilai akhir dihitung 100% dari rata-rata raport. TKA tidak diperlukan.
            </div>
            <div id="notifKhususWarn" class="alert alert-warning py-2 small mb-3 d-none">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <strong>Usia ≥ <?= KHUSUS_MIN_USIA ?> tahun</strong> — Disarankan pilih <strong>Daftar Khusus</strong>, namun Anda masih bisa memilih Reguler atau PKBM jika sesuai.
            </div>

            <!-- Nilai TKA -->
            <div class="row g-3 mb-3">
              <div class="col-md-4" id="wrapTka">
                <label class="form-label fw-semibold">Nilai TKA <span class="text-danger" id="tkaStar">*</span></label>
                <input type="number" name="nilai_tka" id="fTka" class="form-control" value="<?= htmlspecialchars($formData['nilai_tka'] ?? '') ?>" min="0" max="100" step="0.01" placeholder="0-100" onchange="updatePreviewNilai()" oninput="updatePreviewNilai()">
                <small class="text-muted" id="tkaNote">Bobot 30% pada Nilai Akhir</small>
              </div>
            </div>

            <hr>
            <h6 class="fw-bold mb-2"><i class="bi bi-table me-2"></i>Detail Nilai Raport per Mata Pelajaran</h6>

            <!-- Mode Input Toggle -->
            <div class="d-flex align-items-center gap-3 mb-3 p-2 rounded border bg-white">
              <span class="small fw-semibold text-muted"><i class="bi bi-pencil-square me-1 text-primary"></i>Mode Input:</span>
              <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="input_mode" id="modeMatrix" value="matrix" checked onchange="switchInputMode('matrix')">
                <label class="form-check-label small" for="modeMatrix">Per Mata Pelajaran</label>
              </div>
              <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="input_mode" id="modeManual" value="manual" onchange="switchInputMode('manual')">
                <label class="form-check-label small" for="modeManual"><strong>Langsung (Rata-rata)</strong> <span class="text-muted">— jika raport sudah ada nilai rata-ratanya</span></label>
              </div>
            </div>

            <!-- Input Rata-rata Langsung (tersembunyi default) -->
            <div id="wrapManualRata" class="mb-3" style="display:none;">
              <div class="row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Nilai Rata-rata Raport <span class="text-danger">*</span></label>
                  <input type="number" name="nilai_raport_manual" id="fManualRata"
                         class="form-control form-control-lg text-center fw-bold"
                         min="0" max="100" step="0.01" placeholder="0 – 100"
                         value="<?= ($formData['nilai_raport'] ?? 0) > 0 && empty($formRaport) ? htmlspecialchars($formData['nilai_raport']) : '' ?>"
                         oninput="updateRataRataManual()">
                  <div class="form-text">Skala 0–100. Nilai ini langsung dipakai sebagai rata-rata raport.</div>
                </div>
              </div>
            </div>

            <!-- Toolbar Shortcut -->
            <div class="card mb-2 border-0 bg-light" id="raportToolbar">
              <div class="card-body py-2 px-3">
                <div class="d-flex flex-wrap align-items-center gap-2">
                  <span class="small fw-semibold text-muted me-2"><i class="bi bi-lightning-fill text-warning"></i> Shortcut:</span>

                  <!-- Isi semua dengan nilai sama -->
                  <div class="input-group input-group-sm" style="width:auto;">
                    <input type="number" id="fillAllVal" class="form-control form-control-sm" placeholder="Isi semua" min="0" max="100" step="0.01" style="width:90px;">
                    <button type="button" class="btn btn-outline-success" onclick="fillAll()" title="Isi semua cell kosong"><i class="bi bi-grid-3x3-gap"></i> Isi Semua</button>
                  </div>

                  <!-- Fill down (Ctrl+D) -->
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="fillDown()" title="Salin dari atas (Ctrl+D)">
                    <i class="bi bi-arrow-down-square"></i> Fill Down
                  </button>

                  <!-- Fill right (Ctrl+R) -->
                  <button type="button" class="btn btn-outline-primary btn-sm" onclick="fillRight()" title="Salin dari kiri (Ctrl+R)">
                    <i class="bi bi-arrow-right-square"></i> Fill Right
                  </button>

                  <!-- Copy column (semester) -->
                  <div class="input-group input-group-sm" style="width:auto;">
                    <span class="input-group-text">Copy Smt</span>
                    <select id="copyFromSmt" class="form-select form-select-sm" style="width:70px;">
                      <?php foreach ($semester_list as $s): ?><option value="<?= $s ?>"><?= $s ?></option><?php endforeach; ?>
                    </select>
                    <span class="input-group-text">→</span>
                    <select id="copyToSmt" class="form-select form-select-sm" style="width:70px;">
                      <?php foreach ($semester_list as $s): ?><option value="<?= $s ?>" <?= $s===2?'selected':'' ?>><?= $s ?></option><?php endforeach; ?>
                    </select>
                    <button type="button" class="btn btn-outline-secondary" onclick="copySemester()" title="Salin semua nilai semester ke semester lain"><i class="bi bi-clipboard-plus"></i></button>
                  </div>

                  <!-- Clear all -->
                  <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllRaport()" title="Kosongkan semua nilai">
                    <i class="bi bi-eraser"></i> Hapus Semua
                  </button>

                  <!-- Undo / Redo -->
                  <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-dark" id="btnUndo" onclick="undo()" disabled title="Undo (Ctrl+Z)">
                      <i class="bi bi-arrow-counterclockwise"></i> Undo
                    </button>
                    <button type="button" class="btn btn-outline-dark" id="btnRedo" onclick="redo()" disabled title="Redo (Ctrl+Y)">
                      <i class="bi bi-arrow-clockwise"></i> Redo
                    </button>
                  </div>

                  <!-- Help -->
                  <button type="button" class="btn btn-outline-info btn-sm ms-auto" data-bs-toggle="collapse" data-bs-target="#helpRaportCollapse">
                    <i class="bi bi-keyboard"></i> Cara Pakai
                  </button>
                </div>
              </div>
            </div>

            <div class="collapse mb-2" id="helpRaportCollapse">
              <div class="card card-body py-2 small border-info">
                <table class="table table-sm mb-0" style="font-size:.8rem;">
                  <tr><td><kbd>↑↓←→</kbd></td><td>Pindah antar cell</td><td><kbd>Enter</kbd></td><td>Turun / wrap ke kolom berikut</td></tr>
                  <tr><td><kbd>Tab</kbd></td><td>Pindah kanan</td><td><kbd>Shift+Tab</kbd></td><td>Pindah kiri</td></tr>
                  <tr><td><kbd>Ctrl+D</kbd></td><td>Copy dari atas</td><td><kbd>Ctrl+R</kbd></td><td>Copy dari kiri</td></tr>
                  <tr><td><kbd>Ctrl+V</kbd></td><td>Paste dari Excel (TSV)</td><td><kbd>Esc</kbd></td><td>Kosongkan cell</td></tr>
                  <tr class="table-warning"><td><kbd>Ctrl+Z</kbd></td><td>Undo</td><td><kbd>Ctrl+Y</kbd></td><td>Redo</td></tr>
                </table>
              </div>
            </div>
            <p class="text-muted small mb-2">
              <i class="bi bi-info-circle me-1"></i>
              Skala 0–100. Gunakan <kbd>↑↓←→</kbd> · <kbd>Enter</kbd> turun · <kbd>Tab</kbd> kanan · <kbd>Ctrl+V</kbd> paste Excel.
            </p>

            <div id="matrixRegular">
            <div class="table-responsive">
            <table class="table table-bordered mb-0 align-middle" id="tabelRaport">
              <thead>
                <tr class="text-center">
                  <th style="min-width:180px; background:#1a3c34; color:#fff;">Mata Pelajaran</th>
                  <?php foreach ($semester_list as $s): ?>
                  <th style="width:80px; background:#1a3c34; color:#fff;">Smt <?= $s ?></th>
                  <?php endforeach; ?>
                  <th style="width:80px; background:#198754; color:#fff;">Rata-rata</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($mapel_list as $i => $mp): ?>
                <tr>
                  <td class="fw-semibold small" style="background:#f8f9fa;"><?= htmlspecialchars($mp) ?></td>
                  <?php foreach ($semester_list as $s):
                      $col_idx = array_search($s, $semester_list, true);
                  ?>
                  <td class="p-0">
                    <input type="number" name="raport[<?= htmlspecialchars($mp) ?>][<?= $s ?>]"
                           class="form-control form-control-sm text-center raport-cell border-0 rounded-0"
                           data-row="<?= $i ?>" data-col="<?= $col_idx ?>"
                           min="0" max="100" step="0.01" placeholder="—"
                           value="<?= htmlspecialchars((string)($formRaport[$mp][$s] ?? '')) ?>"
                           oninput="clampCell(this); updateRataRata()"
                           onfocus="this.select(); this.parentElement.style.background='#fff3cd';"
                           onblur="this.parentElement.style.background=''">
                  </td>
                  <?php endforeach; ?>
                  <td class="text-center fw-semibold text-success" id="avgMp<?= $i ?>">—</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="table-success">
                  <th class="text-end">Rata-rata Raport (auto):</th>
                  <th colspan="<?= count($semester_list) ?>" class="text-end">→</th>
                  <th class="text-center fs-5" id="rataTotal">—</th>
                </tr>
              </tfoot>
            </table>
            </div>
            </div><!-- /matrixRegular -->

            <!-- Matrix PKBM -->
            <div id="matrixPKBM" style="display:none;">
            <div class="table-responsive">
            <table class="table table-bordered mb-0 align-middle">
              <thead>
                <tr class="text-center">
                  <th style="min-width:220px;background:#1a3c34;color:#fff;">Mata Pelajaran</th>
                  <?php foreach (PKBM_TINGKAT as $tlabel): ?>
                  <th style="width:140px;background:#1a3c34;color:#fff;"><?= htmlspecialchars($tlabel) ?></th>
                  <?php endforeach; ?>
                  <th style="width:80px;background:#198754;color:#fff;">Rata-rata</th>
                </tr>
              </thead>
              <tbody>
                <tr class="table-secondary"><td colspan="4" class="fw-bold small ps-2 py-1"><i class="bi bi-bookmark-fill text-success me-1"></i>Kelompok Umum</td></tr>
                <?php foreach (PKBM_MAPEL_UMUM as $pi => $pmp): ?>
                <tr>
                  <td class="fw-semibold small" style="background:#f8f9fa;"><?= htmlspecialchars($pmp) ?></td>
                  <?php foreach (array_keys(PKBM_TINGKAT) as $tki => $tk): ?>
                  <td class="p-0">
                    <input type="number" name="pkbm_raport[<?= htmlspecialchars($pmp) ?>][<?= $tk ?>]"
                           class="form-control form-control-sm text-center pkbm-cell border-0 rounded-0"
                           data-pkbm-row="<?= $pi ?>" data-pkbm-col="<?= $tki ?>"
                           min="0" max="100" step="0.01" placeholder="—"
                           value="<?= htmlspecialchars((string)($formRaport[$pmp][$tk] ?? '')) ?>"
                           oninput="clampCell(this); updateRataRataPKBM()"
                           onfocus="this.select(); this.parentElement.style.background='#fff3cd';"
                           onblur="this.parentElement.style.background=''">
                  </td>
                  <?php endforeach; ?>
                  <td class="text-center fw-semibold text-success" id="pkbmAvgRow<?= $pi ?>">—</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="table-success">
                  <th class="text-end">Rata-rata Raport (auto):</th>
                  <th colspan="2" class="text-end">→</th>
                  <th class="text-center fs-5" id="rataTotalPKBM">—</th>
                </tr>
              </tfoot>
            </table>
            </div>
            </div><!-- /matrixPKBM -->

          </div>

        </div>
        </div>

        <div class="modal-footer">
          <input type="hidden" name="print_after_save" id="printAfterSave" value="">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="saveTemplate()" title="Simpan jurusan & asal sekolah sebagai template">
            <i class="bi bi-bookmark-plus"></i>
          </button>
          <!-- Tombol Tab 1 -->
          <button type="button" class="btn btn-outline-info" id="btnTab1Print" onclick="submitWithPrint()" title="Simpan Tab 1 lalu cetak bukti">
            <i class="bi bi-printer me-1"></i>Simpan & Cetak
          </button>
          <button type="submit" class="btn btn-success" id="btnSubmit">
            <i class="bi bi-floppy me-1" id="btnSubmitIcon"></i>
            <span id="btnSubmitText">Simpan Tab 1</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
<?php if ($printAfterSave): ?>
// Auto-print setelah simpan berhasil
window.addEventListener('load', () => printBukti(<?= json_encode($printAfterSave) ?>));
<?php endif; ?>

const MAPEL_LIST    = <?= json_encode($mapel_list) ?>;
const SEMESTER_LIST = <?= json_encode($semester_list) ?>;
const ROW_COUNT  = MAPEL_LIST.length;
const COL_COUNT  = SEMESTER_LIST.length;
const HISTORY_LIMIT = 100;
const PKBM_MAPEL = <?= json_encode(PKBM_MAPEL) ?>;
const PKBM_TINGKAT_KEYS = <?= json_encode(array_keys(PKBM_TINGKAT)) ?>;

function getSistem() {
    return document.querySelector('input[name="sistem_pendidikan"]:checked')?.value || 'reguler';
}

function switchSistem(sistem) {
    const isReg    = (sistem === 'reguler');
    const isPKBM   = (sistem === 'pkbm');
    const isKhusus = (sistem === 'khusus');
    const isManualMode = document.getElementById('modeManual')?.checked;

    if (!isManualMode) {
        document.getElementById('matrixRegular').style.display = (isReg || isKhusus) ? '' : 'none';
        document.getElementById('matrixPKBM').style.display    = isPKBM ? '' : 'none';
    }

    // TKA field: sembunyikan untuk Daftar Khusus
    const wrapTka = document.getElementById('wrapTka');
    if (wrapTka) {
        wrapTka.style.display = isKhusus ? 'none' : '';
        document.getElementById('fTka').required = !isKhusus;
        if (isKhusus) document.getElementById('fTka').value = '';
    }

    // Notif: aktif jika Khusus; warn jika bukan Khusus tapi usia >= batas
    const notif     = document.getElementById('notifKhusus');
    const notifWarn = document.getElementById('notifKhususWarn');
    const usiaEl    = document.getElementById('previewUsia');
    const usiaVal   = usiaEl ? parseInt(usiaEl.value) || 0 : 0;
    if (notif)     notif.classList.toggle('d-none', !isKhusus);
    if (notifWarn) notifWarn.classList.toggle('d-none', isKhusus || usiaVal < KHUSUS_MIN_USIA);

    // Label formula
    const lbl = document.getElementById('formulaLabel');
    if (lbl) lbl.textContent = isKhusus ? '100% Raport' : 'R×70% + T×30%';

    if (isManualMode) updateRataRataManual();
    else if (isReg || isKhusus) updateRataRata();
    else updateRataRataPKBM();
}

function updateRataRataPKBM() {
    let totalSum = 0, totalCount = 0;
    PKBM_MAPEL.forEach((mp, i) => {
        let sum = 0, cnt = 0;
        PKBM_TINGKAT_KEYS.forEach(tk => {
            const cell = document.querySelector(`input[name="pkbm_raport[${mp}][${tk}]"]`);
            if (cell && cell.value !== '') {
                const v = parseFloat(cell.value) || 0;
                sum += v; cnt++; totalSum += v; totalCount++;
            }
        });
        const el = document.getElementById('pkbmAvgRow' + i);
        if (el) el.textContent = cnt > 0 ? (sum / cnt).toFixed(2) : '—';
    });
    const rata = totalCount > 0 ? totalSum / totalCount : 0;
    const elTotal = document.getElementById('rataTotalPKBM');
    if (elTotal) elTotal.textContent = totalCount > 0 ? rata.toFixed(2) : '—';
    document.getElementById('displayRata').textContent = totalCount > 0 ? rata.toFixed(2) : '—';
    updatePreviewNilai();
}

function switchInputMode(mode) {
    const isManual  = mode === 'manual';
    const sistem    = getSistem();
    const isPKBM    = sistem === 'pkbm';
    const toolbar   = document.getElementById('raportToolbar');
    const helpBlock = document.getElementById('helpRaportCollapse');

    document.getElementById('wrapManualRata').style.display = isManual ? '' : 'none';
    document.getElementById('matrixRegular').style.display  = (!isManual && !isPKBM) ? '' : 'none';
    document.getElementById('matrixPKBM').style.display     = (!isManual && isPKBM)  ? '' : 'none';
    if (toolbar)   toolbar.style.display   = isManual ? 'none' : '';
    if (helpBlock) helpBlock.classList.add('collapse');

    if (isManual) updateRataRataManual();
    else isPKBM ? updateRataRataPKBM() : updateRataRata();
}

function updateRataRataManual() {
    const v = parseFloat(document.getElementById('fManualRata').value) || 0;
    const disp = document.getElementById('displayRata');
    const tot  = document.getElementById('rataTotal');
    if (disp) disp.textContent = v > 0 ? v.toFixed(2) : '—';
    if (tot)  tot.textContent  = v > 0 ? v.toFixed(2) : '—';
    updatePreviewNilai();
}

function saveTemplate() {
    const tpl = {
        jurusan:       document.getElementById('fJurusan').value,
        asal_sekolah:  document.getElementById('fAsal').value,
        sistem: document.querySelector('input[name="sistem_pendidikan"]:checked')?.value || 'reguler',
    };
    localStorage.setItem('ppdb_template', JSON.stringify(tpl));
    const btn = document.querySelector('[onclick="saveTemplate()"]');
    if (btn) { btn.innerHTML = '<i class="bi bi-bookmark-check-fill text-success"></i>'; setTimeout(() => { btn.innerHTML = '<i class="bi bi-bookmark-plus"></i>'; }, 1500); }
}

function loadTemplate() {
    let tpl = {};
    try { tpl = JSON.parse(localStorage.getItem('ppdb_template') || '{}'); } catch(e) {}
    if (!tpl.jurusan && !tpl.asal_sekolah) { alert('Belum ada template tersimpan. Isi form lalu klik tombol 🔖 untuk menyimpan template.'); return; }
    resetForm();
    if (tpl.jurusan)      document.getElementById('fJurusan').value = tpl.jurusan;
    if (tpl.asal_sekolah) document.getElementById('fAsal').value    = tpl.asal_sekolah;
    if (tpl.sistem) {
        const r = document.querySelector(`input[name="sistem_pendidikan"][value="${tpl.sistem}"]`);
        if (r) { r.checked = true; switchSistem(tpl.sistem); }
    }
    new bootstrap.Modal(document.getElementById('modalPendaftar')).show();
}

// ── History Stack untuk Undo / Redo ─────────────────────────────────────
let undoStack = [];
let redoStack = [];
let focusSnapshot = null;  // snapshot saat cell di-focus, di-push ke undo saat input pertama

function getCell(row, col) {
    return document.querySelector(`.raport-cell[data-row="${row}"][data-col="${col}"]`);
}

function moveTo(row, col) {
    const r = Math.max(0, Math.min(ROW_COUNT - 1, row));
    const c = Math.max(0, Math.min(COL_COUNT - 1, col));
    const cell = getCell(r, c);
    if (cell) cell.focus();
}

function getSnapshot() {
    const snap = [];
    document.querySelectorAll('.raport-cell').forEach(c => snap.push(c.value));
    return snap;
}

function applySnapshot(snap) {
    const cells = document.querySelectorAll('.raport-cell');
    cells.forEach((c, i) => c.value = snap[i] ?? '');
    updateRataRata();
}

function pushHistory() {
    undoStack.push(getSnapshot());
    if (undoStack.length > HISTORY_LIMIT) undoStack.shift();
    redoStack = [];  // clear redo setelah aksi baru
    updateUndoButtons();
}

function updateUndoButtons() {
    const u = document.getElementById('btnUndo');
    const r = document.getElementById('btnRedo');
    if (u) u.disabled = undoStack.length === 0;
    if (r) r.disabled = redoStack.length === 0;
}

function undo() {
    if (undoStack.length === 0) return;
    redoStack.push(getSnapshot());
    applySnapshot(undoStack.pop());
    updateUndoButtons();
}

function redo() {
    if (redoStack.length === 0) return;
    undoStack.push(getSnapshot());
    applySnapshot(redoStack.pop());
    updateUndoButtons();
}

function resetHistory() {
    undoStack = [];
    redoStack = [];
    focusSnapshot = null;
    updateUndoButtons();
}

// ── Hook input cell: capture snapshot on first edit after focus ─────────
document.addEventListener('focusin', function(e) {
    if (e.target.classList && e.target.classList.contains('raport-cell')) {
        focusSnapshot = getSnapshot();
    }
});

document.addEventListener('input', function(e) {
    if (e.target.classList && e.target.classList.contains('raport-cell') && focusSnapshot) {
        undoStack.push(focusSnapshot);
        if (undoStack.length > HISTORY_LIMIT) undoStack.shift();
        redoStack = [];
        focusSnapshot = null;  // hanya push sekali per fokus
        updateUndoButtons();
    }
});

// ── Excel-like keyboard navigation ──────────────────────────────────────
document.addEventListener('keydown', function(e) {
    // Ctrl+Z / Ctrl+Y untuk undo / redo (berlaku di seluruh modal)
    const inModal = document.getElementById('modalPendaftar').classList.contains('show');
    if (inModal && e.ctrlKey) {
        if (e.key.toLowerCase() === 'z' && !e.shiftKey) { e.preventDefault(); undo(); return; }
        if (e.key.toLowerCase() === 'y' || (e.key.toLowerCase() === 'z' && e.shiftKey)) { e.preventDefault(); redo(); return; }
    }

    const t = e.target;
    if (!t.classList || !t.classList.contains('raport-cell')) return;

    const row = parseInt(t.dataset.row), col = parseInt(t.dataset.col);

    // Escape: clear cell (track undo)
    if (e.key === 'Escape') {
        e.preventDefault();
        if (t.value !== '') {
            pushHistory();
            t.value = '';
            updateRataRata();
        }
        return;
    }

    // Enter / Shift+Enter: pindah vertikal, wrap ke kolom berikutnya saat sampai ujung
    if (e.key === 'Enter') {
        e.preventDefault();
        if (e.shiftKey) {
            // Naik: kalau sudah di atas, wrap ke bottom kolom sebelumnya
            if (row > 0) moveTo(row - 1, col);
            else if (col > 0) moveTo(ROW_COUNT - 1, col - 1);
        } else {
            // Turun: kalau sudah di bawah, wrap ke top kolom berikutnya
            if (row < ROW_COUNT - 1) moveTo(row + 1, col);
            else if (col < COL_COUNT - 1) moveTo(0, col + 1);
        }
        return;
    }

    // Tab handled natively (next/prev focusable), tapi kita override agar tetap di matrix
    if (e.key === 'Tab') {
        const nextCol = col + (e.shiftKey ? -1 : 1);
        if (nextCol >= 0 && nextCol < COL_COUNT) {
            e.preventDefault(); moveTo(row, nextCol);
        }
        // else: biarkan native (keluar dari matrix)
        return;
    }

    // Arrow keys
    if (e.key === 'ArrowUp')    { e.preventDefault(); moveTo(row - 1, col); return; }
    if (e.key === 'ArrowDown')  { e.preventDefault(); moveTo(row + 1, col); return; }
    if (e.key === 'ArrowLeft' && t.selectionStart === 0)  { e.preventDefault(); moveTo(row, col - 1); return; }
    if (e.key === 'ArrowRight' && t.selectionEnd === t.value.length) { e.preventDefault(); moveTo(row, col + 1); return; }

    // Ctrl+D: fill down (copy from cell above)
    if (e.ctrlKey && e.key.toLowerCase() === 'd') {
        e.preventDefault();
        const above = getCell(row - 1, col);
        if (above && above.value !== '' && t.value !== above.value) {
            pushHistory();
            t.value = above.value;
            updateRataRata();
        }
        return;
    }

    // Ctrl+R: fill right (copy from cell left)
    if (e.ctrlKey && e.key.toLowerCase() === 'r') {
        e.preventDefault();
        const left = getCell(row, col - 1);
        if (left && left.value !== '' && t.value !== left.value) {
            pushHistory();
            t.value = left.value;
            updateRataRata();
        }
        return;
    }
});

// ── Paste dari Excel (Tab-separated values atau newline) ────────────────
document.addEventListener('paste', function(e) {
    const t = e.target;
    if (!t.classList || !t.classList.contains('raport-cell')) return;

    const data = (e.clipboardData || window.clipboardData).getData('text');
    if (!data) return;

    // Deteksi multi-cell paste (mengandung tab atau newline)
    if (!data.includes('\t') && !data.includes('\n')) return; // single value, biarkan native

    e.preventDefault();
    pushHistory();  // simpan state sebelum paste

    const startRow = parseInt(t.dataset.row), startCol = parseInt(t.dataset.col);
    const lines = data.replace(/\r/g, '').split('\n').filter(l => l.length > 0);

    lines.forEach((line, ri) => {
        const cells = line.split('\t');
        cells.forEach((val, ci) => {
            const cell = getCell(startRow + ri, startCol + ci);
            if (cell && val.trim() !== '') {
                const num = parseFloat(val.trim().replace(',', '.'));
                if (!isNaN(num)) cell.value = num;
            }
        });
    });
    updateRataRata();
});

// ── Shortcut buttons ────────────────────────────────────────────────────
function fillAll() {
    const v = document.getElementById('fillAllVal').value;
    if (v === '' || isNaN(parseFloat(v))) {
        alert('Masukkan nilai dulu di kotak "Isi Semua".');
        return;
    }
    pushHistory();
    document.querySelectorAll('.raport-cell').forEach(c => {
        if (c.value === '') c.value = v;
    });
    updateRataRata();
}

function fillDown() {
    const focused = document.activeElement;
    if (!focused || !focused.classList.contains('raport-cell')) {
        alert('Klik cell tujuan dulu, lalu tekan Fill Down.');
        return;
    }
    const row = parseInt(focused.dataset.row), col = parseInt(focused.dataset.col);
    const above = getCell(row - 1, col);
    if (above && above.value !== '' && focused.value !== above.value) {
        pushHistory();
        focused.value = above.value;
        updateRataRata();
    }
}

function fillRight() {
    const focused = document.activeElement;
    if (!focused || !focused.classList.contains('raport-cell')) {
        alert('Klik cell tujuan dulu, lalu tekan Fill Right.');
        return;
    }
    const row = parseInt(focused.dataset.row), col = parseInt(focused.dataset.col);
    const left = getCell(row, col - 1);
    if (left && left.value !== '' && focused.value !== left.value) {
        pushHistory();
        focused.value = left.value;
        updateRataRata();
    }
}

function copySemester() {
    const from = parseInt(document.getElementById('copyFromSmt').value);
    const to   = parseInt(document.getElementById('copyToSmt').value);
    if (from === to) { alert('Pilih semester berbeda.'); return; }
    const fromCol = SEMESTER_LIST.indexOf(from);
    const toCol   = SEMESTER_LIST.indexOf(to);
    if (fromCol < 0 || toCol < 0) return;

    pushHistory();
    for (let r = 0; r < ROW_COUNT; r++) {
        const src = getCell(r, fromCol);
        const dst = getCell(r, toCol);
        if (src && dst && src.value !== '') dst.value = src.value;
    }
    updateRataRata();
}

function clearAllRaport() {
    if (!confirm('Yakin kosongkan semua nilai raport?')) return;
    pushHistory();
    document.querySelectorAll('.raport-cell').forEach(c => c.value = '');
    updateRataRata();
}

const KHUSUS_MIN_USIA = <?= KHUSUS_MIN_USIA ?>;

function hitungUsia() {
    const tgl = document.getElementById('fTgl').value;
    if (!tgl) return;
    const lahir = new Date(tgl), now = new Date();

    let years  = now.getFullYear() - lahir.getFullYear();
    let months = now.getMonth()    - lahir.getMonth();
    let days   = now.getDate()     - lahir.getDate();

    if (days < 0) {
        months--;
        days += new Date(now.getFullYear(), now.getMonth(), 0).getDate();
    }
    if (months < 0) { years--; months += 12; }

    const el = document.getElementById('previewUsia');
    el.value = `${years} thn ${months} bln ${days} hr`;
    el.className = 'form-control bg-light ' + (years > 21 ? 'text-danger fw-bold' : 'text-success');

    // Tampilkan warning jika usia >= batas tapi bukan Khusus (tidak paksa switch)
    const sistemSaat = getSistem();
    const notifWarn  = document.getElementById('notifKhususWarn');
    if (notifWarn) notifWarn.classList.toggle('d-none', sistemSaat === 'khusus' || years < KHUSUS_MIN_USIA);
}

function clampCell(el) {
    const v = parseFloat(el.value);
    if (!isNaN(v)) {
        if (v > 100) el.value = 100;
        else if (v < 0) el.value = 0;
    }
}

function updateRataRata() {
    let totalSum = 0, totalCount = 0;
    MAPEL_LIST.forEach((mp, i) => {
        let sum = 0, cnt = 0;
        SEMESTER_LIST.forEach(s => {
            const cell = document.querySelector(`input[name="raport[${mp}][${s}]"]`);
            if (cell && cell.value !== '') {
                sum += parseFloat(cell.value) || 0; cnt++;
                totalSum += parseFloat(cell.value) || 0; totalCount++;
            }
        });
        document.getElementById('avgMp' + i).textContent = cnt > 0 ? (sum / cnt).toFixed(2) : '—';
    });
    const rata = totalCount > 0 ? (totalSum / totalCount) : 0;
    document.getElementById('rataTotal').textContent = totalCount > 0 ? rata.toFixed(2) : '—';
    document.getElementById('displayRata').textContent = totalCount > 0 ? rata.toFixed(2) : '—';
    updatePreviewNilai();
}

function updatePreviewNilai() {
    const rataText = document.getElementById('displayRata').textContent;
    const rata  = parseFloat(rataText) || 0;
    const tka   = parseFloat(document.getElementById('fTka').value) || 0;
    const isKhusus = getSistem() === 'khusus';

    document.getElementById('displayTka').textContent = isKhusus ? 'N/A' : (tka > 0 ? tka.toFixed(2) : '—');
    const lbl = document.getElementById('formulaLabel');
    if (lbl) lbl.textContent = isKhusus ? '100% Raport' : 'R×70% + T×30%';

    if (rata > 0) {
        const nilai = isKhusus ? rata : (rata * 0.70) + (tka * 0.30);
        document.getElementById('displayAkhir').textContent = nilai.toFixed(2);
    } else {
        document.getElementById('displayAkhir').textContent = '—';
    }
}

// ── Tab-aware submit button ──────────────────────────────────────────────
function updateSubmitBtn() {
    const onTab1  = document.getElementById('tabDiri').classList.contains('active');
    const icon    = document.getElementById('btnSubmitIcon');
    const text    = document.getElementById('btnSubmitText');
    const btnPrint = document.getElementById('btnTab1Print');
    if (onTab1) {
        icon.className = 'bi bi-floppy me-1';
        text.textContent = 'Simpan Tab 1';
        if (btnPrint) btnPrint.style.display = '';
    } else {
        icon.className = 'bi bi-check-circle me-1';
        text.textContent = 'Simpan Lengkap';
        if (btnPrint) btnPrint.style.display = 'none';
    }
}

// Form submit: Tab 1 langsung submit (tidak perlu pindah ke Tab 2 dulu)
// Tab 2: submit juga langsung
let _pend_submitting = false;
document.getElementById('formPendaftar').addEventListener('submit', function(e) {
    // Cegah double-submit (klik berkali-kali)
    if (_pend_submitting) { e.preventDefault(); return; }

    // Validasi field wajib
    const fields = [
        { el: document.getElementById('fNama'),    label: 'Nama Lengkap' },
        { el: document.getElementById('fNisn'),    label: 'NISN' },
        { el: document.getElementById('fJurusan'), label: 'Jurusan' },
        { el: document.getElementById('fTgl'),     label: 'Tanggal Lahir' },
        { el: document.getElementById('fAsal'),    label: 'Asal Sekolah' },
    ];
    const kosong = fields.filter(f => !f.el.value.trim());
    if (kosong.length > 0) {
        e.preventDefault();
        kosong.forEach(f => {
            f.el.classList.add('is-invalid');
            f.el.addEventListener('input', () => f.el.classList.remove('is-invalid'), { once: true });
        });
        const isTab1Field = ['fNama','fNisn','fJurusan','fTgl','fAsal'].includes(kosong[0].el.id);
        if (isTab1Field) new bootstrap.Tab(document.querySelector('[data-bs-target="#tabDiri"]')).show();
        kosong[0].el.focus();
        return;
    }

    // Semua valid — kunci tombol supaya tidak double-submit
    _pend_submitting = true;
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...';
    const btnPrint = document.getElementById('btnTab1Print');
    if (btnPrint) btnPrint.disabled = true;
});

// Update label tombol saat tab berubah
document.querySelectorAll('[data-bs-target="#tabDiri"], [data-bs-target="#tabRaport"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', updateSubmitBtn);
});

function resetForm() {
    document.getElementById('modalTitle').textContent = 'Tambah Pendaftar';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    // Explicit clear — jangan pakai form.reset() karena akan restore nilai PHP yang di-preserve
    document.getElementById('fNama').value    = '';
    document.getElementById('fNisn').value    = '';
    document.getElementById('fJK').value      = 'L';
    document.getElementById('fJurusan').selectedIndex = 0;
    document.getElementById('fTelp').value    = '';
    document.getElementById('fAlamat').value  = '';
    document.getElementById('fTgl').value     = '';
    document.getElementById('fAsal').value    = '';
    document.getElementById('fTka').value     = '';
    document.getElementById('previewUsia').value = '';
    document.getElementById('previewUsia').className = 'form-control bg-light';
    document.querySelectorAll('.raport-cell').forEach(c => c.value = '');
    document.querySelectorAll('.pkbm-cell').forEach(c => c.value = '');
    document.querySelectorAll('[id^="avgMp"], [id^="pkbmAvgRow"]').forEach(el => el.textContent = '—');
    ['rataTotal','rataTotalPKBM','displayRata','displayTka','displayAkhir'].forEach(id => {
        const el = document.getElementById(id); if (el) el.textContent = '—';
    });
    // Reset sistem ke Reguler
    const rReg = document.getElementById('sistemReguler');
    if (rReg) { rReg.checked = true; switchSistem('reguler'); }
    // Reset field KK
    const fTglKk = document.getElementById('fTglKk');
    if (fTglKk) fTglKk.value = '';
    document.getElementById('kkWarning')?.classList.add('d-none');
    document.getElementById('printAfterSave').value = '';
    resetHistory();
    new bootstrap.Tab(document.querySelector('[data-bs-target="#tabDiri"]')).show();
    updateSubmitBtn();
}

function cekCutoffKk(val) {
    const warn = document.getElementById('kkWarning');
    if (!warn) return;
    if (val && val > '2025-06-15') {
        warn.classList.remove('d-none');
    } else {
        warn.classList.add('d-none');
    }
}

function submitWithPrint() {
    document.getElementById('printAfterSave').value = '1';
    document.getElementById('formPendaftar').requestSubmit();
}

<?php if ($editFromRanking): ?>
window.addEventListener('load', function() {
    editForm(<?= json_encode($editFromRanking, JSON_HEX_APOS|JSON_HEX_QUOT) ?>);
});
<?php endif; ?>

<?php if ($showModalOnLoad): ?>
// Reopen modal after validation error — preserve all data
window.addEventListener('load', function() {
    const modalEl = document.getElementById('modalPendaftar');
    const modal   = new bootstrap.Modal(modalEl);
    document.getElementById('modalTitle').textContent =
        <?= $formAction === 'edit' ? "'Edit Pendaftar'" : "'Tambah Pendaftar'" ?>;
    modal.show();
    modalEl.addEventListener('shown.bs.modal', function() {
        hitungUsia();
        updateRataRata();
        <?php if ($formAction === 'edit'): ?>
        // Scroll to tab where the error likely is
        new bootstrap.Tab(document.querySelector('[data-bs-target="#tabDiri"]')).show();
        <?php endif; ?>
    }, { once: true });
});
<?php endif; ?>

function editForm(d) {
    document.getElementById('modalTitle').textContent = 'Edit Pendaftar — ' + d.no_pendaftaran;
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value   = d.id;
    document.getElementById('fNama').value    = d.nama;
    document.getElementById('fNisn').value    = d.nisn;
    document.getElementById('fJK').value      = d.jenis_kelamin;
    document.getElementById('fTgl').value     = d.tanggal_lahir;
    document.getElementById('fAsal').value    = d.asal_sekolah;
    document.getElementById('fTelp').value    = d.no_telp || '';
    document.getElementById('fAlamat').value  = d.alamat || '';
    document.getElementById('fJurusan').value = d.jurusan;
    document.getElementById('fTka').value     = d.nilai_tka;
    const fTglKk = document.getElementById('fTglKk');
    if (fTglKk) { fTglKk.value = d.tgl_kk || ''; cekCutoffKk(d.tgl_kk || ''); }

    // Set sistem penilaian
    const sistem = d.sistem_pendidikan || 'reguler';
    const radioEl = document.querySelector(`input[name="sistem_pendidikan"][value="${sistem}"]`);
    if (radioEl) radioEl.checked = true;
    switchSistem(sistem);

    // Populate matrix
    const matrix = d.raport_matrix || {};
    document.querySelectorAll('.raport-cell').forEach(c => c.value = '');
    document.querySelectorAll('.pkbm-cell').forEach(c => c.value = '');
    if (sistem === 'pkbm') {
        PKBM_MAPEL.forEach(mp => {
            PKBM_TINGKAT_KEYS.forEach(tk => {
                const v = (matrix[mp] && matrix[mp][tk] !== undefined) ? matrix[mp][tk] : '';
                const cell = document.querySelector(`input[name="pkbm_raport[${mp}][${tk}]"]`);
                if (cell) cell.value = v;
            });
        });
        updateRataRataPKBM();
    } else {
        MAPEL_LIST.forEach(mp => {
            SEMESTER_LIST.forEach(s => {
                const v = (matrix[mp] && matrix[mp][s] !== undefined) ? matrix[mp][s] : '';
                const cell = document.querySelector(`input[name="raport[${mp}][${s}]"]`);
                if (cell) cell.value = v;
            });
        });
        updateRataRata();
    }
    hitungUsia();
    resetHistory();
    updateSubmitBtn();
    new bootstrap.Modal(document.getElementById('modalPendaftar')).show();
}

function printBukti(r) {
    const jk   = r.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    const tgl  = r.tanggal_lahir ? new Date(r.tanggal_lahir).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '-';
    const tglKk= r.tgl_kk ? new Date(r.tgl_kk).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : '-';
    const daft = r.tanggal_daftar ? new Date(r.tanggal_daftar).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}) : new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'});
    const sistemLabel = r.sistem_pendidikan === 'pkbm' ? 'PKBM (Paket B)' : r.sistem_pendidikan === 'khusus' ? 'Daftar Khusus (100% Raport)' : 'Reguler (SMP)';
    const html = `<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<title>Bukti Pendaftaran - ${r.nama}</title>
<style>
  body{font-family:Arial,sans-serif;font-size:13px;margin:0;padding:24px;color:#111;}
  .header{text-align:center;border-bottom:3px double #333;padding-bottom:12px;margin-bottom:16px;}
  .header h2{margin:4px 0;font-size:16px;text-transform:uppercase;letter-spacing:.5px;}
  .header p{margin:2px 0;font-size:12px;}
  table.info{width:100%;border-collapse:collapse;margin-bottom:16px;}
  table.info td{padding:4px 8px;vertical-align:top;}
  table.info td:first-child{width:170px;font-weight:bold;color:#333;}
  .badge{display:inline-block;padding:3px 10px;border-radius:4px;font-weight:bold;font-size:12px;}
  .badge-terima{background:#198754;color:#fff;}
  .badge-gugur{background:#dc3545;color:#fff;}
  .badge-diproses{background:#ffc107;color:#000;}
  .section-title{font-size:12px;font-weight:bold;text-transform:uppercase;letter-spacing:.5px;
    background:#f0f0f0;padding:5px 8px;margin:16px 0 8px;border-left:3px solid #333;}
  .berkas-table{width:100%;border-collapse:collapse;margin-bottom:10px;font-size:12px;}
  .berkas-table th{background:#f0f0f0;padding:5px 8px;text-align:left;font-size:11px;border:1px solid #ccc;}
  .berkas-table td{padding:5px 8px;border:1px solid #ddd;vertical-align:middle;}
  .berkas-table td.centang{text-align:center;width:36px;}
  .berkas-box{display:inline-block;width:14px;height:14px;border:1.5px solid #555;vertical-align:middle;}
  .status-ok{color:#198754;font-weight:700;}
  .status-fail{color:#dc3545;font-weight:700;}
  .yn-wrap{display:flex;gap:12px;align-items:center;}
  .yn-item{display:flex;align-items:center;gap:5px;}
  .daftar-ulang{border:1.5px solid #333;border-radius:4px;padding:7px 10px;font-size:11.5px;margin-bottom:14px;background:#fffbea;}
  .berkas-box{width:16px;height:16px;border:1.5px solid #333;flex-shrink:0;margin-top:1px;}
  .berkas-label{font-size:12px;line-height:1.4;}
  .berkas-sub{font-size:10px;color:#666;display:block;}
  .footer{margin-top:24px;display:flex;justify-content:space-between;}
  .ttd{text-align:center;width:200px;}
  .ttd .name-line{margin-top:56px;border-top:1px solid #333;padding-top:4px;font-size:11px;}
  .note{font-size:11px;color:#666;margin-bottom:12px;padding:6px 8px;border:1px dashed #bbb;border-radius:4px;}
  @media print{body{padding:0;}}
</style></head>
<body>
<div class="header">
  <h2>SMKS Laboratorium Jakarta</h2>
  <p>Jl. Laboratorium No. 1, Jakarta Selatan</p>
  <h2 style="margin-top:8px;font-size:15px;">BUKTI TANDA DAFTAR SPMB</h2>
  <p style="font-size:11px;">Tahun Pelajaran ${new Date().getFullYear()}/${new Date().getFullYear()+1}</p>
</div>

<table class="info">
  <tr><td>No. Pendaftaran</td><td>: <strong style="font-size:14px;">${r.no_pendaftaran || '-'}</strong></td></tr>
  <tr><td>Nama Lengkap</td><td>: <strong>${r.nama}</strong></td></tr>
  <tr><td>NISN</td><td>: ${r.nisn}</td></tr>
  <tr><td>Tanggal Lahir</td><td>: ${tgl}</td></tr>
  <tr><td>Jenis Kelamin</td><td>: ${jk}</td></tr>
  <tr><td>Jurusan Pilihan</td><td>: <strong>${r.jurusan}</strong></td></tr>
  <tr><td>Gelombang</td><td>: Gelombang ${r.gelombang}</td></tr>
  <tr><td>Asal Sekolah</td><td>: ${r.asal_sekolah || '-'}</td></tr>
  <tr><td>Sistem Penilaian</td><td>: ${sistemLabel}</td></tr>
  <tr><td>Tanggal KK</td><td>: ${tglKk}</td></tr>
  <tr><td>Tanggal Daftar</td><td>: ${daft}</td></tr>
</table>

<div class="section-title">&#9745; Kelengkapan Berkas — Diisi Petugas Fase 1</div>
<table class="berkas-table">
  <thead>
    <tr><th style="width:36px;text-align:center;">&#10003;</th><th>Berkas</th><th>Keterangan</th></tr>
  </thead>
  <tbody>
    <tr>
      <td class="centang"><div class="berkas-box"></div></td>
      <td><strong>Kartu Keluarga (KK) DKI Jakarta</strong><br><small>Asli + fotokopi</small></td>
      <td>
        ${r.tgl_kk
          ? (r.tgl_kk <= '2025-06-15'
              ? '<span class="status-ok">&#10003; Memenuhi syarat</span><br><small>Tgl KK: ' + tglKk + '</small>'
              : '<span class="status-fail">&#10007; Melebihi cut-off 15 Juni 2025</span><br><small>Tgl KK: ' + tglKk + '</small>')
          : '<small style="color:#888;">Tgl KK belum diisi</small>'}
      </td>
    </tr>
    <tr>
      <td class="centang"><div class="berkas-box"></div></td>
      <td><strong>Hasil TKA</strong><br><small>Fotokopi (reguler &amp; PKBM)</small></td>
      <td></td>
    </tr>
    <tr>
      <td class="centang"><div class="berkas-box"></div></td>
      <td><strong>Akta Kelahiran</strong><br><small>Fotokopi</small></td>
      <td></td>
    </tr>
    <tr>
      <td class="centang">—</td>
      <td><strong>Buta Warna</strong></td>
      <td>
        <div class="yn-wrap">
          <div class="yn-item"><div class="berkas-box"></div> <span>Ya</span></div>
          <div class="yn-item"><div class="berkas-box"></div> <span>Tidak</span></div>
        </div>
      </td>
    </tr>
  </tbody>
</table>

<div class="daftar-ulang">
  <strong>&#9888; Penting:</strong> Jika siswa/siswi <strong>lulus seleksi</strong>, formulir ini wajib dibawa kembali saat <strong>daftar ulang</strong>. Formulir tanpa tanda tangan panitia tidak berlaku.
</div>

<p class="note">Bukti ini hanya sah sebagai tanda daftar dan bukan merupakan jaminan penerimaan.</p>
<div class="footer">
  <div class="ttd"><div class="name-line">Orang Tua / Wali</div></div>
  <div class="ttd"><div class="name-line">Panitia SPMB</div></div>
</div>
</body></html>`;
    const w = window.open('', '_blank', 'width=720,height=960');
    w.document.write(html);
    w.document.close();
    w.onload = () => w.print();
}

// ── Gelombang Tab Switching (langsung, tanpa konfirmasi) ─────────────────────
document.querySelectorAll('.glm-tab-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const target = this.dataset.glm;
        if (target === <?= json_encode($active_glm) ?>) return;
        const url = new URL(window.location.href);
        url.searchParams.set('gelombang', target);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    });
});
</script>

