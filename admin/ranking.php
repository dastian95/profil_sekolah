<?php
// ── Endpoint ringan: cek apakah data berubah ─────────────────────────────
if (isset($_GET['rank_hash'])) {
    header('Content-Type: application/json');
    try {
        $gel_h = (int)($_GET['gelombang'] ?? 1) ?: 1;
        $row_h = $conn->prepare("SELECT COUNT(*) as n, MAX(updated_at) as ts FROM pendaftar WHERE gelombang=?");
        $row_h->execute([$gel_h]);
        $h = $row_h->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['hash' => md5($h['n'] . $h['ts'])]);
    } catch (Throwable $e) {
        echo json_encode(['hash' => '']);
    }
    exit;
}

$jurusan_list = JURUSAN_LIST;
$short        = JURUSAN_SHORT;

$msg = '';
if (!empty($_SESSION['flash_ranking'])) {
    $msg = $_SESSION['flash_ranking'];
    unset($_SESSION['flash_ranking']);
}

// Auto-migrate (idempotent): flag "Ditahan" (status kedua) + kolom kunci gelombang
try { $conn->exec("ALTER TABLE pendaftar ADD COLUMN is_ditahan TINYINT(1) NOT NULL DEFAULT 0"); } catch (Throwable) {}
try { $conn->exec("ALTER TABLE gelombang ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0"); } catch (Throwable) {}
try { $conn->exec("ALTER TABLE gelombang ADD COLUMN locked_at DATETIME NULL"); } catch (Throwable) {}
// Auto-migrate: flag "Mengundurkan Diri / Diterima di Sekolah Lain"
try { $conn->exec("ALTER TABLE pendaftar ADD COLUMN is_undur_diri TINYINT(1) NOT NULL DEFAULT 0"); } catch (Throwable) {}

// Ambil konfigurasi gelombang
$gel_rows = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();
$gel_map  = [];
foreach ($gel_rows as $g) $gel_map[$g['gelombang']] = $g;

// ── Pindah Semua Gugur ke Gelombang lain (superadmin) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pindah_glm_semua'
    && !empty($_SESSION['is_super'])) {
    $dari_glm = (int)($_POST['dari_glm'] ?? 1);
    $ke_glm   = (int)($_POST['ke_glm']   ?? 2);
    $jur_fil  = $_POST['jurusan'] ?? '';
    $tahunP   = date('Y');
    $prefP    = "SPMB-{$tahunP}-G{$ke_glm}-";

    $whr = ['gelombang=?', "status='gugur'"];
    $prm = [$dari_glm];
    if ($jur_fil) { $whr[] = 'jurusan=?'; $prm[] = $jur_fil; }
    $rows = $conn->prepare("SELECT id, nama, no_pendaftaran FROM pendaftar WHERE " . implode(' AND ', $whr));
    $rows->execute($prm);
    $semua = $rows->fetchAll();

    $pindah = 0;
    foreach ($semua as $row) {
        $lastP = $conn->prepare("SELECT MAX(CAST(RIGHT(no_pendaftaran,4) AS UNSIGNED)) FROM pendaftar WHERE gelombang=? AND no_pendaftaran LIKE ?");
        $lastP->execute([$ke_glm, $prefP . '%']);
        $noBaru = $prefP . str_pad((int)$lastP->fetchColumn() + 1, 4, '0', STR_PAD_LEFT);
        $conn->prepare("UPDATE pendaftar SET gelombang=?, no_pendaftaran=?, jalur='reguler', status='diproses', catatan=NULL WHERE id=?")
             ->execute([$ke_glm, $noBaru, $row['id']]);
        $pindah++;
    }
    // Ranking dihitung di latar (halaman ranking memoke recalc saat dibuka)
    log_admin_action($conn, 'PINDAH_GLM_SEMUA', "Pindah {$pindah} pendaftar gugur Glm {$dari_glm} → Glm {$ke_glm}" . ($jur_fil ? " ({$jur_fil})" : ''));
    $_SESSION['flash_ranking'] = "<div class='alert alert-success'><strong>{$pindah}</strong> pendaftar gugur dipindahkan ke Gelombang {$ke_glm}.</div>";
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . "?page=ranking&gelombang={$ke_glm}");
    exit;
}

// ── Pindah Gelombang (superadmin) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pindah_glm'
    && !empty($_SESSION['is_super'])) {
    $pgl_id   = (int)$_POST['id'];
    $ke_glm   = (int)($_POST['ke_glm'] ?? 2);
    $fGelRed  = (int)($_POST['gelombang'] ?? $ke_glm);
    $row      = $conn->prepare("SELECT nama, no_pendaftaran, gelombang, jurusan FROM pendaftar WHERE id=?");
    $row->execute([$pgl_id]);
    $cur = $row->fetch();
    if ($cur && (int)$cur['gelombang'] !== $ke_glm) {
        // generateNoPendaftaran ada di pendaftar.php — buat inline di sini
        $tahunP = date('Y');
        $prefP  = "SPMB-{$tahunP}-G{$ke_glm}-";
        $lastP  = $conn->prepare("SELECT MAX(CAST(RIGHT(no_pendaftaran,4) AS UNSIGNED)) FROM pendaftar WHERE gelombang=? AND no_pendaftaran LIKE ?");
        $lastP->execute([$ke_glm, $prefP . '%']);
        $seqP   = (int)$lastP->fetchColumn() + 1;
        $noBaru = $prefP . str_pad($seqP, 4, '0', STR_PAD_LEFT);
        $conn->prepare("UPDATE pendaftar SET gelombang=?, no_pendaftaran=?, jalur='reguler', status='diproses', catatan=NULL WHERE id=?")
             ->execute([$ke_glm, $noBaru, $pgl_id]);
        // Ranking kedua gelombang dihitung di latar (recalc saat halaman dibuka)
        log_admin_action($conn, 'PINDAH_GELOMBANG',
            "Ranking: pindah {$cur['nama']} ({$cur['no_pendaftaran']}) → Glm {$ke_glm} ({$noBaru})");
        $_SESSION['flash_ranking'] = "<div class='alert alert-success'>Pendaftar <strong>{$cur['nama']}</strong> dipindahkan ke Gelombang {$ke_glm} (<strong>{$noBaru}</strong>).</div>";
    }
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . "?page=ranking&gelombang={$ke_glm}");
    exit;
}

// ── Tandai / Lepas Mengundurkan Diri ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['tandai_undur_diri','lepas_undur_diri'])) {
    $ud_id  = (int)$_POST['id'];
    $ud_val = ($_POST['action'] === 'tandai_undur_diri') ? 1 : 0;
    $row_ud = $conn->prepare("SELECT nama, gelombang FROM pendaftar WHERE id=?");
    $row_ud->execute([$ud_id]);
    $cur_ud = $row_ud->fetch();
    if ($cur_ud) {
        if ($ud_val === 1) {
            // Tandai: gugurkan slot + set flag
            $conn->prepare("UPDATE pendaftar SET is_undur_diri=1, status='gugur', catatan='Mengundurkan diri / diterima di sekolah lain' WHERE id=?")->execute([$ud_id]);
            log_admin_action($conn, 'UNDUR_DIRI', "Tandai mengundurkan diri: {$cur_ud['nama']}");
        } else {
            // Batalkan: kembalikan ke diproses agar bisa ikut Proses berikutnya
            $conn->prepare("UPDATE pendaftar SET is_undur_diri=0, status='diproses', catatan=NULL WHERE id=?")->execute([$ud_id]);
            log_admin_action($conn, 'LEPAS_UNDUR_DIRI', "Batalkan undur diri: {$cur_ud['nama']}");
        }
    }
    $fGelRedirect = (int)($_POST['gelombang'] ?? ($cur_ud['gelombang'] ?? 1));
    $fJurRedirect = urlencode($_POST['jurusan'] ?? '');
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . "?page=ranking&gelombang={$fGelRedirect}&jurusan={$fJurRedirect}");
    exit;
}

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
    } elseif (!empty($g['is_locked'])) {
        $msg = '<div class="alert alert-danger"><i class="bi bi-lock-fill me-2"></i>Gelombang terkunci — Hitung Ulang diblokir. Buka kunci dulu di Pengaturan Pendaftaran.</div>';
    } else {
        // Paksa hitung ulang transaksional (all-or-nothing) — abaikan throttle
        try {
            $total_diterima = recalc_gelombang($conn, $gelombang);
            rank_mark_run($conn, $gelombang);
            log_admin_action($conn, 'HITUNG_ULANG_RANKING',
                "Hitung ulang Gelombang {$gelombang}: {$total_diterima} diterima");
            $msg = "<div class='alert alert-success'><i class='bi bi-check-circle me-2'></i>
                Hitung ulang Gelombang <strong>{$gelombang}</strong> selesai.
                <strong>{$total_diterima}</strong> pendaftar diterima dari seluruh jurusan.</div>";
        } catch (Throwable $e) {
            $msg = "<div class='alert alert-danger'><i class='bi bi-exclamation-triangle me-2'></i>
                Gagal menghitung ulang — data lama tetap aman (otomatis dibatalkan). Coba lagi.</div>";
        }
    }

    // PRG: redirect setelah proses agar refresh tidak mengulang seleksi
    $_SESSION['flash_ranking'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . '?page=ranking&gelombang=' . $gelombang . '&just_processed=1');
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
        $stmt = $conn->prepare("SELECT * FROM pendaftar WHERE gelombang=? AND jurusan=? AND is_ditahan=0 AND is_undur_diri=0
                                ORDER BY is_pinned DESC, nilai_akhir DESC, usia DESC, id ASC");
        $stmt->execute([$fGel, $jurusan]);
        $list = $stmt->fetchAll();

        $stmtDitahan = $conn->prepare("SELECT * FROM pendaftar WHERE gelombang=? AND jurusan=? AND is_ditahan=1
                                       ORDER BY nilai_akhir DESC, usia DESC, id ASC");
        $stmtDitahan->execute([$fGel, $jurusan]);
        $list_ditahan = $stmtDitahan->fetchAll();

        $stmtUndur = $conn->prepare("SELECT * FROM pendaftar WHERE gelombang=? AND jurusan=? AND is_undur_diri=1
                                     ORDER BY nilai_akhir DESC, usia DESC, id ASC");
        $stmtUndur->execute([$fGel, $jurusan]);
        $list_undur = $stmtUndur->fetchAll();

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
        $all_data[$jurusan] = ['list' => $list, 'raport' => $raport_map, 'ditahan' => $list_ditahan, 'undur' => $list_undur];
    }
} catch (PDOException $e) {
    $db_error = $e->getMessage();
}

// ── Helper seleksi (mirror logika proses) ────────────────────────────────────
$KK_CUTOFF = '2025-06-15';
// Gugur bila usia > 21 ATAU tanggal KK melebihi cut-off
function rank_is_gugur(array $r, string $cutoff): bool {
    if (!$r['lolos_usia']) return true;
    if (!empty($r['tgl_kk']) && $r['tgl_kk'] > $cutoff) return true;
    return false;
}
function rank_gugur_reason(array $r, string $cutoff): string {
    if (!$r['lolos_usia']) return 'Usia > 21 tahun';
    if (!empty($r['tgl_kk']) && $r['tgl_kk'] > $cutoff) return 'KK melebihi cut-off';
    return 'Gugur';
}
// Urutkan kandidat (pinned selalu di atas), lalu metrik per jalur/segmen
function rank_sort(array $list, string $key): array {
    usort($list, function ($a, $b) use ($key) {
        if ($a['is_pinned'] != $b['is_pinned']) return ($b['is_pinned'] <=> $a['is_pinned']);
        if ($key === 'g1') {
            if ((float)$a['nilai_akhir'] != (float)$b['nilai_akhir'])
                return (float)$b['nilai_akhir'] <=> (float)$a['nilai_akhir'];
            // Tiebreaker deterministik: usia DESC, lalu id ASC (cegah urutan acak saat seri)
            return ($b['usia'] <=> $a['usia']) ?: ((int)$a['id'] <=> (int)$b['id']);
        }
        // Cabang jalur G2 (g2_jarak/g2_abk/g2_reguler) diarsipkan di _archive/g2_jalur/
        // — G2 memakai sistem G1 (nilai akhir). rank_sort hanya dipanggil dgn 'g1'.
        // fallback: raport → usia
        if ((float)$a['nilai_raport'] != (float)$b['nilai_raport'])
            return (float)$b['nilai_raport'] <=> (float)$a['nilai_raport'];
        return $b['usia'] <=> $a['usia'];
    });
    return $list;
}
// JSON data baris untuk modal detail (termasuk field jalur/jarak/status ortu/buta warna)
function rank_row_json(array $r, array $raport_map): string {
    return json_encode([
        'id'=>$r['id'],'no_pendaftaran'=>$r['no_pendaftaran'],'nama'=>$r['nama'],'nisn'=>$r['nisn'],
        'tanggal_lahir'=>$r['tanggal_lahir'],'usia'=>$r['usia'],'jenis_kelamin'=>$r['jenis_kelamin'],
        'asal_sekolah'=>$r['asal_sekolah'],'alamat_sekolah'=>$r['alamat_sekolah'] ?? null,'no_telp'=>$r['no_telp'],'alamat'=>$r['alamat'],
        'sistem_pendidikan'=>$r['sistem_pendidikan'],'jurusan'=>$r['jurusan'],
        'nilai_raport'=>$r['nilai_raport'],'nilai_tka'=>$r['nilai_tka'],'nilai_akhir'=>$r['nilai_akhir'],
        'lolos_usia'=>$r['lolos_usia'],'is_pinned'=>$r['is_pinned'],'status'=>$r['status'],
        'catatan'=>$r['catatan'],'gelombang'=>$r['gelombang'],
        'jalur'=>$r['jalur'] ?? 'reguler','jarak_km'=>$r['jarak_km'] ?? null,
        'status_ortu'=>$r['status_ortu'] ?? 'tidak','buta_warna'=>$r['buta_warna'] ?? 'belum',
        'kelurahan'=>$r['kelurahan'] ?? null,'nilai_khusus'=>$r['nilai_khusus'] ?? null,
        'raport'=>$raport_map[$r['id']] ?? [],
    ], JSON_UNESCAPED_UNICODE);
}
// Render satu baris <tr>. $variant: 'top' | 'below' | 'gugur'. $hiddenClass dipakai utk toggle.
function rank_render_row(array $r, int $rank, array $raport_map, int $fGel, string $fJurusan,
                         string $variant, string $hiddenClass = '', string $infoHtml = '', string $gugurReason = ''): void {
    $is_pinned = (bool)$r['is_pinned'];
    $badge = STATUS_BADGE[$r['status']] ?? 'bg-secondary';
    $label = STATUS_LABEL[$r['status']] ?? $r['status'];
    $rj = htmlspecialchars(rank_row_json($r, $raport_map), ENT_QUOTES);
    if ($variant === 'gugur') {
        $trClass = $hiddenClass; $style = 'display:none; background:#fff5f5;';
    } elseif ($variant === 'below') {
        $trClass = trim($hiddenClass . ' text-muted'); $style = 'display:none; background:#f8f9fa;';
    } else {
        $trClass = $is_pinned ? 'table-warning'
                 : ($r['status']==='terima' ? 'table-success'
                 : ($r['status']==='gugur' ? 'table-danger opacity-75' : ''));
        $style = '';
    }
    $td = $variant === 'gugur' ? 'text-danger' : '';
    ?>
    <tr class="rank-anim-row <?= $trClass ?>" data-rstat="<?= htmlspecialchars($variant==='gugur'?'gugur':$r['status']) ?>" style="<?= $style ?>">
        <td class="text-center small <?= $td ?>"><?= $variant==='gugur' ? '—' : $rank ?></td>
        <td class="small <?= $td ?>"><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
        <td class="<?= $td ?>">
            <?= htmlspecialchars($r['nama']) ?>
            <?php if ($is_pinned): ?><i class="bi bi-pin-fill text-warning ms-1" title="Pinned"></i><?php endif; ?>
            <?php if ($variant==='gugur'): ?><i class="bi bi-exclamation-triangle-fill text-danger ms-1"></i><?php endif; ?>
            <?php if ($infoHtml): ?><div class="mt-1"><?= $infoHtml ?></div><?php endif; ?>
            <?php if ($variant==='gugur' && $gugurReason): ?><div class="small text-danger"><?= htmlspecialchars($gugurReason) ?></div><?php endif; ?>
        </td>
        <td class="small <?= $td ?>"><?= htmlspecialchars($r['nisn']) ?></td>
        <td class="text-center <?= $td ?>"><?= $r['jenis_kelamin'] ?></td>
        <td class="text-center <?= $td ?>"><?= number_format($r['nilai_raport'], 2) ?></td>
        <td class="text-center <?= $td ?>"><?= number_format($r['nilai_tka'], 2) ?></td>
        <td class="text-center fw-bold <?= $td ?>"><?= number_format($r['nilai_akhir'], 2) ?></td>
        <td class="text-center small <?= $td ?> <?= $variant==='gugur'?'fw-bold':'' ?>"><?= $r['usia'] ?> thn</td>
        <td><span class="badge rank-badge <?= $variant==='gugur'?'bg-danger':$badge ?>" data-label="<?= $variant==='gugur'?'Gugur':htmlspecialchars($label) ?>"><?= $variant==='gugur'?'Gugur':$label ?></span></td>
        <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
                <button class="btn btn-xs btn-outline-<?= $variant==='gugur'?'danger':'primary' ?> py-0 px-1" style="font-size:0.75rem"
                        onclick='openViewModal(<?= $rj ?>)'><i class="bi bi-eye"></i></button>
                <?php if ($variant !== 'gugur'): ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="pin">
                    <input type="hidden" name="pendaftar_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="pin_val" value="<?= $is_pinned ? 0 : 1 ?>">
                    <input type="hidden" name="gelombang" value="<?= $fGel ?>">
                    <input type="hidden" name="jurusan" value="<?= htmlspecialchars($fJurusan) ?>">
                    <button type="submit" class="btn btn-xs py-0 px-1 <?= $is_pinned ? 'btn-warning' : 'btn-outline-secondary' ?>"
                            style="font-size:0.75rem" title="<?= $is_pinned ? 'Lepas PIN' : 'PIN (jamin diterima)' ?>"
                            onclick="return confirm('<?= $is_pinned ? 'Lepas PIN siswa ini?' : 'PIN siswa ini? Dijamin diterima & tidak tampil di publik.' ?>')">
                        <i class="bi bi-pin<?= $is_pinned ? '-fill' : '' ?>"></i>
                    </button>
                </form>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="tandai_undur_diri">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="gelombang" value="<?= $fGel ?>">
                    <input type="hidden" name="jurusan" value="<?= htmlspecialchars($fJurusan) ?>">
                    <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1"
                            style="font-size:0.75rem" title="Tandai: Diterima di Sekolah Lain / Mengundurkan Diri"
                            onclick="return confirm('Tandai <?= htmlspecialchars(addslashes($r['nama'])) ?> sebagai Mengundurkan Diri / Diterima di Sekolah Lain?\nSiswa tidak akan ikut peringkat.')">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php
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
        <?php $g_locked = !empty($g['is_locked']); ?>
        <?php if ($g_locked): ?>
        <span class="btn btn-secondary btn-sm disabled" title="Gelombang terkunci — buka kunci di Pengaturan Pendaftaran">
            <i class="bi bi-lock-fill me-1"></i>Hitung Ulang Glm <?= $fGel ?> Terkunci
        </span>
        <?php else: ?>
        <form method="POST" class="d-inline" onsubmit="return confirm('Hitung ulang ranking Gelombang <?= $fGel ?>?\nStatus semua pendaftar akan dihitung ulang berdasarkan nilai & kuota.')">
            <input type="hidden" name="action" value="proses">
            <input type="hidden" name="gelombang" value="<?= $fGel ?>">
            <button type="submit" class="btn btn-warning btn-sm">
                <i class="bi bi-arrow-repeat me-1"></i>Hitung Ulang Glm <?= $fGel ?>
            </button>
        </form>
        <?php endif; ?>
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
    <?= !empty($g['is_locked']) ? '<span class="badge bg-danger ms-2"><i class="bi bi-lock-fill me-1"></i>Terkunci</span>' : '' ?>
    <span class="ms-3 text-warning fw-semibold"><i class="bi bi-pin-fill me-1"></i>Siswa ber-PIN dijamin diterima & tidak tampil di publik</span>
    <div class="mt-1"><i class="bi bi-info-circle me-1"></i>Urutan: <strong>nilai akhir</strong> tertinggi menang, <strong>usia</strong> tertua penentu seri. KK &gt; 15 Juni 2025 = gugur.</div>
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
    $list         = $all_data[$jurusan]['list'];
    $raport_map   = $all_data[$jurusan]['raport'];
    $list_ditahan = $all_data[$jurusan]['ditahan'] ?? [];
    $list_undur   = $all_data[$jurusan]['undur'] ?? [];
    $total_j      = count($list);
    $diterima_j = count(array_filter($list, fn($r) => $r['status']==='terima'));
    $pinned_count = count(array_filter($list, fn($r) => $r['is_pinned']));

    // Eligible (lolos usia & KK) vs gugur
    $eligible  = array_values(array_filter($list, fn($r) => !rank_is_gugur($r, $KK_CUTOFF)));
    $gugur_arr = array_values(array_filter($list, fn($r) =>  rank_is_gugur($r, $KK_CUTOFF)));
    $jurusan_id = preg_replace('/[^a-z0-9]/', '', strtolower($short[$jurusan]));

    // Semua gelombang: satu daftar nilai akhir → usia
    $segments = [
        ['key'=>'g1','label'=>'','icon'=>'','color'=>'success','list'=>rank_sort($eligible,'g1'),'kuota'=>$kuota_glm],
    ];

    // Badge info per-jalur G2 (jarak/zonasi/ortu/abk) dihapus — G2 memakai sistem G1,
    // segmen selalu 'info'=>'' sehingga dulu tidak pernah menghasilkan output apa pun.
    // Kode aslinya diarsipkan di _archive/g2_jalur/.
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
                <th class="text-center">Raport</th>
                <th class="text-center">TKA</th>
                <th class="text-center">Nilai Akhir</th>
                <th class="text-center">Usia</th>
                <th>Status</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($eligible) && empty($gugur_arr)): ?>
            <tr><td colspan="11" class="text-center py-3 text-muted">Belum ada pendaftar untuk jurusan ini.</td></tr>
        <?php else:
            foreach ($segments as $seg):
                // Split top/below per kuota segmen (pinned selalu masuk top)
                $top = []; $below = []; $pin_so = 0; $norm = 0;
                foreach ($seg['list'] as $r) {
                    if ($r['is_pinned']) { $top[] = $r; $pin_so++; }
                    else { $norm++; if ($pin_so + $norm <= $seg['kuota']) $top[] = $r; else $below[] = $r; }
                }
                $segId = $jurusan_id . '-' . $seg['key'];
                if ($seg['label']): ?>
                <tr class="table-<?= $seg['color'] ?>">
                    <td colspan="11" class="py-1">
                        <span class="fw-semibold"><i class="bi <?= $seg['icon'] ?> me-1"></i><?= htmlspecialchars($seg['label']) ?></span>
                        <span class="small text-muted ms-2">Kuota <?= $seg['kuota'] ?> · <?= count($seg['list']) ?> pendaftar</span>
                    </td>
                </tr>
                <?php endif;
                if (empty($seg['list'])): ?>
                    <tr><td colspan="11" class="text-center small text-muted py-2">— tidak ada pendaftar —</td></tr>
                <?php else:
                    $rank = 0;
                    foreach ($top as $r): $rank++;
                        rank_render_row($r, $rank, $raport_map, $fGel, $fJurusan, 'top');
                    endforeach;
                    if (!empty($below)): ?>
                    <tr>
                        <td colspan="11" class="p-0">
                            <button class="btn btn-sm w-100 rounded-0 py-1 text-muted bg-light border-0 border-top border-bottom"
                                    style="font-size:0.8rem" onclick="toggleRows('below-<?= $segId ?>','chev-<?= $segId ?>')">
                                <i class="bi bi-chevron-down me-1" id="chev-<?= $segId ?>"></i>
                                ── Batas Kuota (<?= $seg['kuota'] ?> terbaik) ──
                                <span class="text-danger fw-semibold"><?= count($below) ?> tidak lolos</span> — klik untuk tampilkan
                            </button>
                        </td>
                    </tr>
                    <?php foreach ($below as $r): $rank++;
                        rank_render_row($r, $rank, $raport_map, $fGel, $fJurusan, 'below', 'below-'.$segId);
                    endforeach;
                    endif;
                endif;
            endforeach;

            // ── Gugur (Usia / KK) — collapsed, per jurusan ──────────────────
            if (!empty($gugur_arr)): ?>
            <tr>
                <td colspan="11" class="p-0">
                    <button class="btn btn-sm w-100 rounded-0 py-1 text-danger bg-danger bg-opacity-10 border-0 border-top"
                            style="font-size:0.8rem" onclick="toggleRows('gugur-<?= $jurusan_id ?>','chev-gugur-<?= $jurusan_id ?>')">
                        <i class="bi bi-chevron-down me-1" id="chev-gugur-<?= $jurusan_id ?>"></i>
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <?= count($gugur_arr) ?> Pendaftar Gugur (Usia / KK) — klik untuk tampilkan
                    </button>
                </td>
            </tr>
            <?php foreach ($gugur_arr as $r):
                rank_render_row($r, 0, $raport_map, $fGel, $fJurusan, 'gugur', 'gugur-'.$jurusan_id, '', rank_gugur_reason($r, $KK_CUTOFF));
            endforeach;
            endif;

            // ── Ditahan — tidak ikut kompetisi, collapsed ────────────────────
            if (!empty($list_ditahan)): ?>
            <tr>
                <td colspan="11" class="p-0">
                    <button class="btn btn-sm w-100 rounded-0 py-1 text-warning-emphasis bg-warning bg-opacity-10 border-0 border-top"
                            style="font-size:0.8rem" onclick="toggleRows('ditahan-<?= $jurusan_id ?>','chev-ditahan-<?= $jurusan_id ?>')">
                        <i class="bi bi-chevron-down me-1" id="chev-ditahan-<?= $jurusan_id ?>"></i>
                        <i class="bi bi-pause-circle me-1"></i>
                        <?= count($list_ditahan) ?> Pendaftar Ditahan (tidak ikut peringkat) — klik untuk tampilkan
                    </button>
                </td>
            </tr>
            <?php foreach ($list_ditahan as $r): ?>
            <tr class="ditahan-<?= $jurusan_id ?>" style="display:none; background:#fffbea;">
                <td class="text-center small text-muted">—</td>
                <td><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                <td>
                    <?= htmlspecialchars($r['nama']) ?>
                    <span class="badge bg-warning text-dark ms-1"><i class="bi bi-pause-circle me-1"></i>Ditahan</span>
                </td>
                <td><?= htmlspecialchars($r['nisn']) ?></td>
                <td class="text-center"><?= $r['jenis_kelamin'] ?></td>
                <td class="text-center"><?= number_format($r['nilai_raport'], 2) ?></td>
                <td class="text-center"><?= number_format($r['nilai_tka'], 2) ?></td>
                <td class="text-center text-muted"><?= number_format($r['nilai_akhir'], 2) ?></td>
                <td class="text-center"><?= $r['usia'] ?> thn</td>
                <td><span class="badge bg-warning text-dark"><?= STATUS_LABEL[$r['status']] ?? $r['status'] ?></span></td>
                <td></td>
            </tr>
            <?php endforeach;
            endif;

            // ── Mengundurkan Diri / Sekolah Lain — collapsed ─────────────────
            if (!empty($list_undur)): ?>
            <tr>
                <td colspan="11" class="p-0">
                    <button class="btn btn-sm w-100 rounded-0 py-1 text-danger-emphasis bg-danger bg-opacity-10 border-0 border-top"
                            style="font-size:0.8rem" onclick="toggleRows('undur-<?= $jurusan_id ?>','chev-undur-<?= $jurusan_id ?>')">
                        <i class="bi bi-chevron-down me-1" id="chev-undur-<?= $jurusan_id ?>"></i>
                        <i class="bi bi-x-circle me-1"></i>
                        <?= count($list_undur) ?> Mengundurkan Diri / Sekolah Lain (tidak ikut peringkat) — klik untuk tampilkan
                    </button>
                </td>
            </tr>
            <?php foreach ($list_undur as $r): ?>
            <tr class="undur-<?= $jurusan_id ?>" style="display:none; background:#fff5f5;">
                <td class="text-center small text-muted">—</td>
                <td><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                <td>
                    <?= htmlspecialchars($r['nama']) ?>
                    <span class="badge bg-danger ms-1"><i class="bi bi-x-circle me-1"></i>Sekolah Lain</span>
                </td>
                <td><?= htmlspecialchars($r['nisn']) ?></td>
                <td class="text-center"><?= $r['jenis_kelamin'] ?></td>
                <td class="text-center"><?= number_format($r['nilai_raport'], 2) ?></td>
                <td class="text-center"><?= number_format($r['nilai_tka'], 2) ?></td>
                <td class="text-center text-muted"><?= number_format($r['nilai_akhir'], 2) ?></td>
                <td class="text-center"><?= $r['usia'] ?> thn</td>
                <td><span class="badge bg-danger"><?= STATUS_LABEL[$r['status']] ?? $r['status'] ?></span></td>
                <td>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="lepas_undur_diri">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="gelombang" value="<?= $fGel ?>">
                        <input type="hidden" name="jurusan" value="<?= htmlspecialchars($fJurusan) ?>">
                        <button type="submit" class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size:.75rem" title="Kembalikan ke daftar">
                            <i class="bi bi-arrow-counterclockwise"></i> Batalkan
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach;
            endif;
        endif; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>
<?php endforeach; ?>

<?php if (!empty($_SESSION['is_super'])): ?>
<?php
// ── Semua Data Gugur — hanya superadmin ─────────────────────────────────────
$gugur_all = [];
try {
    $gWhere = ['gelombang=?', 'status=\'gugur\'']; $gParams = [$fGel];
    if ($fJurusan) { $gWhere[] = 'jurusan=?'; $gParams[] = $fJurusan; }
    $gStmt = $conn->prepare("SELECT * FROM pendaftar WHERE " . implode(' AND ', $gWhere) . " ORDER BY jurusan, catatan, nama");
    $gStmt->execute($gParams);
    $gugur_all = $gStmt->fetchAll();
} catch (PDOException $e) {}
?>
<?php if (!empty($gugur_all)): ?>
<div class="card mb-4 border-danger">
    <div class="card-header bg-danger bg-opacity-10 d-flex align-items-center justify-content-between">
        <span class="fw-semibold text-danger">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>Semua Data Gugur — Gelombang <?= $fGel ?>
            <span class="badge bg-danger ms-2"><?= count($gugur_all) ?></span>
        </span>
        <div class="d-flex gap-2 align-items-center">
            <?php if ((int)$fGel === 1): ?>
            <form method="POST" class="d-inline" onsubmit="return confirm('Pindahkan SEMUA <?= count($gugur_all) ?> data gugur ke Gelombang 2?\nStatus akan direset ke Diproses.')">
                <input type="hidden" name="action" value="pindah_glm_semua">
                <input type="hidden" name="dari_glm" value="1">
                <input type="hidden" name="ke_glm" value="2">
                <?php if ($fJurusan): ?><input type="hidden" name="jurusan" value="<?= htmlspecialchars($fJurusan) ?>"><?php endif; ?>
                <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-arrow-right-circle-fill me-1"></i>Pindah Semua ke G2</button>
            </form>
            <?php elseif ((int)$fGel === 2): ?>
            <form method="POST" class="d-inline" onsubmit="return confirm('Kembalikan SEMUA <?= count($gugur_all) ?> data gugur ke Gelombang 1?\nStatus akan direset ke Diproses.')">
                <input type="hidden" name="action" value="pindah_glm_semua">
                <input type="hidden" name="dari_glm" value="2">
                <input type="hidden" name="ke_glm" value="1">
                <?php if ($fJurusan): ?><input type="hidden" name="jurusan" value="<?= htmlspecialchars($fJurusan) ?>"><?php endif; ?>
                <button type="submit" class="btn btn-sm btn-secondary"><i class="bi bi-arrow-left-circle-fill me-1"></i>Kembalikan Semua ke G1</button>
            </form>
            <?php endif; ?>
            <button class="btn btn-sm btn-outline-danger" onclick="toggleRows('gugur-all-rows','chev-gugur-all')">
                <i class="bi bi-chevron-down me-1" id="chev-gugur-all"></i>Tampilkan
            </button>
        </div>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
        <thead class="table-danger">
            <tr>
                <th>#</th>
                <th>No. Daftar</th>
                <th>Nama</th>
                <th>Jurusan</th>
                <th class="text-center">Glm</th>
                <th class="text-center">Nilai Akhir</th>
                <th class="text-center">Usia</th>
                <th>Alasan Gugur</th>
                <th class="text-center">Pindah</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($gugur_all as $i => $gr): ?>
        <tr class="gugur-all-rows" style="display:none; background:#fff5f5;">
            <td class="text-muted small"><?= $i + 1 ?></td>
            <td class="small"><?= htmlspecialchars($gr['no_pendaftaran']) ?></td>
            <td><?= htmlspecialchars($gr['nama']) ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($short[$gr['jurusan']] ?? $gr['jurusan']) ?></span></td>
            <td class="text-center"><?= $gr['gelombang'] ?></td>
            <td class="text-center"><?= number_format((float)$gr['nilai_akhir'], 2) ?></td>
            <td class="text-center"><?= $gr['usia'] ?></td>
            <td class="small text-danger"><?= htmlspecialchars($gr['catatan'] ?? 'Gugur') ?></td>
            <td class="text-center">
                <?php if ((int)$gr['gelombang'] === 1): ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Pindahkan <?= htmlspecialchars(addslashes($gr['nama'])) ?> ke Gelombang 2?')">
                    <input type="hidden" name="action" value="pindah_glm">
                    <input type="hidden" name="id" value="<?= $gr['id'] ?>">
                    <input type="hidden" name="ke_glm" value="2">
                    <button type="submit" class="btn btn-xs btn-outline-warning py-0 px-1" title="Pindah ke G2" style="font-size:.75rem"><i class="bi bi-arrow-right-circle"></i> G2</button>
                </form>
                <?php elseif ((int)$gr['gelombang'] === 2): ?>
                <form method="POST" class="d-inline" onsubmit="return confirm('Kembalikan <?= htmlspecialchars(addslashes($gr['nama'])) ?> ke Gelombang 1?')">
                    <input type="hidden" name="action" value="pindah_glm">
                    <input type="hidden" name="id" value="<?= $gr['id'] ?>">
                    <input type="hidden" name="ke_glm" value="1">
                    <button type="submit" class="btn btn-xs btn-outline-secondary py-0 px-1" title="Kembalikan ke G1" style="font-size:.75rem"><i class="bi bi-arrow-left-circle"></i> G1</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>
<?php endif; // is_super ?>

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
// ── Toggle collapsed rows (generik: by class + chevron id) ───────────────────
function toggleRows(rowClass, chevId) {
    const rows = document.querySelectorAll('.' + rowClass);
    const chevron = document.getElementById(chevId);
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
                <tr><th class="text-muted fw-normal">Asal Sekolah</th><td>${esc(d.asal_sekolah)}${d.alamat_sekolah ? `<br><span class="small text-muted">${esc(d.alamat_sekolah)}</span>` : ''}</td></tr>
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
                ${d.gelombang == 2 ? `<tr><th class="text-muted fw-normal">Jalur</th><td>${jalurLabel(d.jalur)}</td></tr>` : ''}
                ${d.gelombang == 2 && d.jalur === 'reguler' ? `<tr><th class="text-muted fw-normal">Zonasi</th><td>${d.kelurahan ? esc(d.kelurahan) : '-'}</td></tr>` : ''}
                ${d.gelombang == 2 && d.jalur === 'yatim_piatu' ? `<tr><th class="text-muted fw-normal">Status Ortu</th><td>${ortuLabel(d.status_ortu)}</td></tr>` : ''}
                ${d.gelombang == 2 && d.jalur === 'abk' ? `<tr><th class="text-muted fw-normal">Nilai Khusus ABK</th><td>${d.nilai_khusus != null ? parseFloat(d.nilai_khusus).toFixed(2) : '-'}</td></tr>` : ''}
                <tr><th class="text-muted fw-normal">Tes Buta Warna</th><td>${bwLabel(d.buta_warna)}</td></tr>
            </table>
        </div>
    </div>
    <hr>
    <div class="row g-2 mb-3">
        <div class="col-md-4 text-center">
            <div class="fw-semibold text-muted small">Nilai Raport</div>
            <div class="fs-4 fw-bold">${parseFloat(d.nilai_raport).toFixed(2)}</div>
        </div>
        <div class="col-md-4 text-center">
            <div class="fw-semibold text-muted small">${(d.sistem_pendidikan==='khusus'||d.sistem_pendidikan==='pkbm') ? 'Nilai TKA (N/A)' : 'Nilai TKA'}</div>
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
function jalurLabel(j) {
    const m = {
        reguler:     '<span class="badge bg-success">Reguler</span>',
        yatim_piatu: '<span class="badge bg-danger">Yatim/Piatu</span>',
        anak_guru:   '<span class="badge bg-warning text-dark">Anak Guru</span>',
        abk:         '<span class="badge bg-info text-dark">ABK</span>',
    };
    return m[j] || '<span class="badge bg-secondary">' + (j || 'Reguler') + '</span>';
}
function ortuLabel(s) {
    const m = { tidak: 'Lengkap', yatim: 'Yatim', piatu: 'Piatu', yatim_piatu: 'Yatim & Piatu' };
    return m[s] || 'Lengkap';
}
function bwLabel(b) {
    if (b === 'normal') return '<span class="text-success">Normal</span>';
    if (b === 'buta_warna_parsial') return '<span class="text-danger">Buta Warna Parsial</span>';
    if (b === 'buta_warna_total') return '<span class="text-danger">Buta Warna Total</span>';
    return '<span class="text-muted">Belum dites</span>';
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

<?php if (!empty($_GET['just_processed'])): ?>
<style>
@keyframes badgePop {
    0%   { transform:scale(0) rotate(-20deg); opacity:0; }
    55%  { transform:scale(1.35) rotate(4deg);  opacity:1; }
    75%  { transform:scale(0.9)  rotate(-2deg); }
    100% { transform:scale(1)   rotate(0deg);   opacity:1; }
}
@keyframes badgeGlowTerima {
    0%,100% { box-shadow:none; }
    50%     { box-shadow:0 0 0 5px rgba(34,197,94,.35), 0 0 18px rgba(34,197,94,.5); }
}
@keyframes badgeShakeGugur {
    0%,100% { transform:scale(1) rotate(0deg); }
    25%     { transform:scale(1) rotate(-6deg); }
    75%     { transform:scale(1) rotate(6deg); }
}
.rank-badge { display:inline-block; }
.rank-badge.bp-hidden { opacity:0; transform:scale(0); }
.rank-badge.bp-terima {
    animation: badgePop .45s cubic-bezier(.34,1.56,.64,1) forwards,
                badgeGlowTerima .7s ease .35s;
}
.rank-badge.bp-gugur {
    animation: badgePop .35s ease forwards,
                badgeShakeGugur .4s ease .3s;
}
.rank-badge.bp-other { animation: badgePop .35s ease forwards; }
</style>
<script>
(function(){
    const url = new URL(location.href);
    url.searchParams.delete('just_processed');
    history.replaceState(null, '', url.toString());

    const badges = Array.from(document.querySelectorAll('.rank-anim-row'))
        .filter(r => getComputedStyle(r).display !== 'none')
        .map(r => ({ el: r.querySelector('.rank-badge'), stat: r.dataset.rstat }))
        .filter(x => x.el);

    // Sembunyikan semua badge dulu
    badges.forEach(x => x.el.classList.add('bp-hidden'));

    const STEP = 52;
    badges.forEach((x, i) => {
        setTimeout(() => {
            x.el.classList.remove('bp-hidden');
            if (x.stat === 'terima')      x.el.classList.add('bp-terima');
            else if (x.stat === 'gugur')  x.el.classList.add('bp-gugur');
            else                          x.el.classList.add('bp-other');
        }, 300 + i * STEP);
    });
})();
</script>
<?php endif; ?>

<script>
// ── Live polling: reload otomatis jika data berubah ──────────────────────
(function() {
    const gel = <?= (int)($fGel ?? 1) ?>;
    const dash = <?= !empty($_SESSION['is_super']) ? "'superadmin_dashboard.php'" : "'admin_dashboard.php'" ?>;
    let lastHash = null;

    function checkHash() {
        fetch('ranking_hash.php?gelombang=' + gel)
            .then(r => r.json())
            .then(d => {
                if (lastHash === null) { lastHash = d.hash; return; }
                if (d.hash !== lastHash) {
                    lastHash = d.hash;
                    // Reload halaman dengan parameter yang sama (gelombang & jurusan)
                    const url = new URL(window.location.href);
                    window.location.replace(url.href);
                }
            })
            .catch(() => {});
    }

    setInterval(checkHash, 5000);
    checkHash();
})();

// ── Engine: poke pemrosesan ranking di latar (Lapisan 2) ─────────────────────
// Halaman ini "Tampilan Utama" — sekaligus penggerak hitung ulang. Poke recalc
// throttled (server membatasi ~8 detik). Saat status berubah, poller hash di atas
// otomatis me-reload halaman → tampilan selalu menyusul hasil terbaru.
(function() {
    const gel = <?= (int)($fGel ?? 1) ?>;
    function poke() {
        fetch('rank_recalc.php?gelombang=' + gel, { cache: 'no-store' }).catch(() => {});
    }
    poke();                       // saat halaman dibuka
    setInterval(poke, 10000);     // selama halaman terbuka — dijauhkan waktunya
})();


</script>
