<?php
// Guard eksplisit — Database Manager hanya untuk Superadmin Utama (akun id=1)
if (empty($_SESSION['is_super']) || !is_primary_super()) {
    echo '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>Akses ditolak. Halaman ini hanya untuk <strong>Superadmin Utama</strong>.</div>';
    return;
}

$DB_PROTECTED = ['admins', 'site_settings', 'gelombang', 'tahapan', 'meja'];
$all_tables   = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// ── SQL Runner (tidak terikat tabel) ───────────────────────────────────────────
$sql_result = null; $sql_cols = []; $sql_error = ''; $sql_text = ''; $sql_note = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'run_sql') {
    $sql_text  = trim($_POST['sql'] ?? '');
    $sql_clean = rtrim($sql_text, "; \t\r\n");

    if ($sql_clean === '') {
        $sql_error = 'Query kosong.';
    } elseif (strpos($sql_clean, ';') !== false) {
        $sql_error = 'Hanya satu statement per eksekusi (titik koma di tengah tidak didukung).';
    } else {
        $kw = strtoupper((string)strtok($sql_clean, " \t\n\r("));
        $is_read = in_array($kw, ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'], true);
        log_admin_action($conn, 'DB_SQL', mb_substr($sql_clean, 0, 500));

        if ($is_read) {
            // Read query: render hasil inline (idempotent — aman tanpa PRG)
            try {
                $st  = $conn->query($sql_clean);
                $all = $st->fetchAll(PDO::FETCH_ASSOC);
                $sql_note = number_format(count($all)) . ' baris';
                if (count($all) > 200) {
                    $sql_note = 'menampilkan 200 dari ' . number_format(count($all)) . ' baris';
                    $all = array_slice($all, 0, 200);
                }
                $sql_result = $all;
                $sql_cols   = $all ? array_keys($all[0]) : [];
            } catch (Throwable $e) {
                $sql_error = $e->getMessage();
            }
        } elseif (empty($_POST['confirm_write'])) {
            $sql_error = 'Query ini MENGUBAH data — centang konfirmasi terlebih dahulu.';
        } else {
            // Write query: PRG dengan flash
            try {
                $n = $conn->exec($sql_clean);
                $_SESSION['dbm_flash'] = ['type' => 'success', 'msg' => 'Query berhasil dieksekusi — <strong>' . (int)$n . '</strong> baris terpengaruh.'];
                while (ob_get_level() > 0) ob_end_clean();
                $sq_tbl = $_POST['table'] ?? '';
                header('Location: superadmin_dashboard.php?page=database_manager'
                    . ($sq_tbl && in_array($sq_tbl, $all_tables, true) ? '&tbl=' . urlencode($sq_tbl) : ''));
                exit;
            } catch (Throwable $e) {
                $sql_error = $e->getMessage();
            }
        }
    }
}

// ── POST handler (aksi per tabel) ──────────────────────────────────────────────
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'run_sql') {
    $action  = $_POST['action'] ?? '';
    $tbl     = $_POST['table']  ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!in_array($tbl, $all_tables, true)) {
        $flash = ['type' => 'danger', 'msg' => 'Tabel tidak valid.'];
    } else {
        // Deteksi PK sekali untuk semua aksi yang butuh
        $pk_col_det = 'id';
        foreach ($conn->query("DESCRIBE `$tbl`")->fetchAll(PDO::FETCH_ASSOC) as $c) {
            if ($c['Key'] === 'PRI') { $pk_col_det = $c['Field']; break; }
        }

        if ($action === 'truncate') {
            if (in_array($tbl, $DB_PROTECTED)) {
                $flash = ['type' => 'danger', 'msg' => "Tabel <strong>$tbl</strong> dilindungi dan tidak bisa di-truncate."];
            } elseif ($confirm !== $tbl) {
                $flash = ['type' => 'warning', 'msg' => 'Konfirmasi nama tabel tidak cocok. Tidak jadi di-truncate.'];
            } else {
                try {
                    $conn->exec("SET FOREIGN_KEY_CHECKS=0");
                    $conn->exec("TRUNCATE TABLE `$tbl`");
                    $conn->exec("SET FOREIGN_KEY_CHECKS=1");
                    log_admin_action($conn, 'DB_TRUNCATE', "Tabel: $tbl");
                    $flash = ['type' => 'success', 'msg' => "Tabel <strong>$tbl</strong> berhasil dikosongkan."];
                } catch (PDOException $e) {
                    $flash = ['type' => 'danger', 'msg' => 'Gagal: ' . htmlspecialchars($e->getMessage())];
                }
            }

        } elseif ($action === 'delete_row') {
            $row_id = (int)($_POST['row_id'] ?? 0);
            if ($row_id <= 0) {
                $flash = ['type' => 'danger', 'msg' => 'ID tidak valid.'];
            } else {
                try {
                    $conn->prepare("DELETE FROM `$tbl` WHERE `$pk_col_det` = ?")->execute([$row_id]);
                    log_admin_action($conn, 'DB_DELETE_ROW', "Tabel: $tbl, $pk_col_det=$row_id");
                    $flash = ['type' => 'success', 'msg' => "Baris $pk_col_det=$row_id dihapus dari <strong>$tbl</strong>."];
                } catch (PDOException $e) {
                    $flash = ['type' => 'danger', 'msg' => 'Gagal: ' . htmlspecialchars($e->getMessage())];
                }
            }

        } elseif ($action === 'delete_selected') {
            $ids = array_filter(array_map('intval', (array)($_POST['selected_ids'] ?? [])));
            if (empty($ids)) {
                $flash = ['type' => 'warning', 'msg' => 'Tidak ada baris yang dipilih.'];
            } else {
                try {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $conn->prepare("DELETE FROM `$tbl` WHERE `$pk_col_det` IN ($placeholders)")->execute($ids);
                    log_admin_action($conn, 'DB_DELETE_SELECTED', "Tabel: $tbl, " . count($ids) . " baris dihapus");
                    $flash = ['type' => 'success', 'msg' => count($ids) . " baris berhasil dihapus dari <strong>$tbl</strong>."];
                } catch (PDOException $e) {
                    $flash = ['type' => 'danger', 'msg' => 'Gagal: ' . htmlspecialchars($e->getMessage())];
                }
            }

        } elseif ($action === 'insert_row') {
            $fields = $_POST['fields'] ?? [];
            $desc = $conn->query("DESCRIBE `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
            $valid_cols = [];
            foreach ($desc as $c) {
                if ($c['Key'] === 'PRI' && stripos($c['Extra'] ?? '', 'auto_increment') !== false) continue;
                if (array_key_exists($c['Field'], $fields)) $valid_cols[] = $c['Field'];
            }
            if (empty($valid_cols)) {
                $flash = ['type' => 'danger', 'msg' => 'Tidak ada kolom yang valid.'];
            } else {
                try {
                    $cols_str = implode(', ', array_map(fn($c) => "`$c`", $valid_cols));
                    $phs = implode(', ', array_fill(0, count($valid_cols), '?'));
                    $vals = array_map(fn($c) => ($fields[$c] === '' ? null : $fields[$c]), $valid_cols);
                    $conn->prepare("INSERT INTO `$tbl` ($cols_str) VALUES ($phs)")->execute($vals);
                    log_admin_action($conn, 'DB_INSERT_ROW', "Tabel: $tbl");
                    $flash = ['type' => 'success', 'msg' => "Baris baru berhasil ditambahkan ke <strong>$tbl</strong>."];
                } catch (PDOException $e) {
                    $flash = ['type' => 'danger', 'msg' => 'Gagal: ' . htmlspecialchars($e->getMessage())];
                }
            }

        } elseif ($action === 'update_row') {
            $row_id = $_POST['row_id'] ?? '';
            $fields = $_POST['fields'] ?? [];
            $desc = $conn->query("DESCRIBE `$tbl`")->fetchAll(PDO::FETCH_ASSOC);
            $valid_cols = [];
            foreach ($desc as $c) {
                if ($c['Key'] === 'PRI') continue;
                if (array_key_exists($c['Field'], $fields)) $valid_cols[] = $c['Field'];
            }
            if ($row_id === '' || empty($valid_cols)) {
                $flash = ['type' => 'danger', 'msg' => 'Data tidak valid.'];
            } else {
                try {
                    $set_str = implode(', ', array_map(fn($c) => "`$c`=?", $valid_cols));
                    $vals    = array_map(fn($c) => ($fields[$c] === '' ? null : $fields[$c]), $valid_cols);
                    $vals[]  = $row_id;
                    $conn->prepare("UPDATE `$tbl` SET $set_str WHERE `$pk_col_det`=?")->execute($vals);
                    log_admin_action($conn, 'DB_UPDATE_ROW', "Tabel: $tbl, $pk_col_det=$row_id");
                    $flash = ['type' => 'success', 'msg' => "Baris $pk_col_det=$row_id di <strong>$tbl</strong> berhasil diupdate."];
                } catch (PDOException $e) {
                    $flash = ['type' => 'danger', 'msg' => 'Gagal: ' . htmlspecialchars($e->getMessage())];
                }
            }
        }
    }

    $_SESSION['dbm_flash'] = $flash;
    $redir_url = '?page=database_manager' . ($tbl ? '&tbl=' . urlencode($tbl) : '');
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . $redir_url);
    exit;
}

if (isset($_SESSION['dbm_flash'])) {
    $flash = $_SESSION['dbm_flash'];
    unset($_SESSION['dbm_flash']);
}

$active_table = isset($_GET['tbl']) && in_array($_GET['tbl'], $all_tables, true) ? $_GET['tbl'] : null;
$view = ($_GET['view'] ?? 'data') === 'struktur' ? 'struktur' : 'data';

// Index & foreign key info untuk view Struktur + badge FK
$tbl_indexes = []; $tbl_fks = []; $fk_map = [];
if ($active_table) {
    try { $tbl_indexes = $conn->query("SHOW INDEX FROM `$active_table`")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable) {}
    try {
        $fkq = $conn->prepare("SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL");
        $fkq->execute([$active_table]);
        $tbl_fks = $fkq->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tbl_fks as $fk) $fk_map[$fk['COLUMN_NAME']] = $fk;
    } catch (Throwable) {}
}

$table_stats = [];
foreach ($all_tables as $t) {
    $table_stats[$t] = (int)$conn->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}

$rows = []; $columns = []; $pk_col = 'id';
$total_rows = 0;
$cur_page        = max(1, (int)($_GET['p'] ?? 1));
$search          = trim($_GET['q'] ?? '');
$_limit          = (int)($_GET['limit'] ?? 25);
$per_page        = in_array($_limit, [25, 50, 75, 100, 200]) ? $_limit : 25;
$sort_col_input  = trim($_GET['sort'] ?? '');
$sort_dir_input  = strtoupper(trim($_GET['dir'] ?? ''));
$sort_col        = ''; // set after columns loaded
$sort_dir        = 'DESC';

if ($active_table) {
    $columns = $conn->query("DESCRIBE `$active_table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $c) {
        if ($c['Key'] === 'PRI') { $pk_col = $c['Field']; break; }
    }
    $col_names = array_column($columns, 'Field');
    $sort_col  = in_array($sort_col_input, $col_names, true) ? $sort_col_input : $pk_col;
    $sort_dir  = $sort_dir_input === 'ASC' ? 'ASC' : 'DESC';

    $text_cols = array_filter($columns, fn($c) => preg_match('/char|text|enum/i', $c['Type']));
    $where = ''; $params = [];
    if ($search && $text_cols) {
        $parts = [];
        foreach ($text_cols as $c) { $parts[] = "`{$c['Field']}` LIKE ?"; $params[] = "%$search%"; }
        $where = 'WHERE ' . implode(' OR ', $parts);
    }
    $cs = $conn->prepare("SELECT COUNT(*) FROM `$active_table` $where");
    $cs->execute($params);
    $total_rows = (int)$cs->fetchColumn();
    $offset = ($cur_page - 1) * $per_page;
    $ds = $conn->prepare("SELECT * FROM `$active_table` $where ORDER BY `$sort_col` $sort_dir LIMIT $per_page OFFSET $offset");
    $ds->execute($params);
    $rows = $ds->fetchAll(PDO::FETCH_ASSOC);
}
$total_pages = $total_rows ? (int)ceil($total_rows / $per_page) : 1;
?>

<style>
.db-table-card { cursor:pointer; transition:all .15s ease; border:2px solid transparent; border-radius:10px; }
.db-table-card:hover { border-color:#7c3aed; box-shadow:0 4px 12px rgba(124,58,237,.12); }
.db-table-card.active-tbl { border-color:#7c3aed; background:#faf5ff; }
.db-table-card .tbl-name { font-weight:600; font-size:.9rem; color:#1e1b4b; }
.db-table-card .tbl-count { font-size:1.4rem; font-weight:700; color:#7c3aed; line-height:1; }
.db-table-card .tbl-type { font-size:.7rem; text-transform:uppercase; letter-spacing:.5px; }
.protected-badge { font-size:.65rem; background:#fef3c7; color:#92400e; border-radius:4px; padding:1px 5px; }
.data-table { font-size:.8rem; }
.data-table th { position:sticky; top:0; background:#f8fafc; z-index:1; white-space:nowrap; }
.data-table td { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.data-table td:hover { white-space:normal; max-width:none; }
.truncate-zone { background:#fff5f5; border:1px solid #fed7d7; border-radius:10px; padding:16px 18px; }
.col-badge { font-size:.7rem; padding:2px 6px; border-radius:4px; background:#f1f5f9; color:#475569; }
.col-pk { background:#fef9c3; color:#854d0e; }
.text-purple { color:#7c3aed; }
.bulk-bar { background:#1e1b4b; color:#fff; border-radius:10px; padding:10px 16px; display:none; align-items:center; gap:12px; margin-bottom:10px; }
.bulk-bar.show { display:flex; }
.row-cb { width:16px; height:16px; cursor:pointer; accent-color:#7c3aed; }
</style>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-database-fill me-2 text-purple"></i>Database Manager</h5>
        <small class="text-muted">Kelola isi tabel database — hanya superadmin yang bisa akses halaman ini</small>
    </div>
    <span class="badge bg-secondary"><?= count($all_tables) ?> tabel</span>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : ($flash['type'] === 'danger' ? 'x-circle-fill' : 'exclamation-triangle-fill') ?>"></i>
    <div><?= $flash['msg'] ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Overview Grid -->
<div class="row g-3 mb-4">
<?php foreach ($table_stats as $tbl => $cnt):
    $isProtected = in_array($tbl, $DB_PROTECTED); $isActive = $active_table === $tbl;
?>
<div class="col-6 col-md-4 col-lg-3">
    <a href="?page=database_manager&tbl=<?= urlencode($tbl) ?>" class="text-decoration-none">
    <div class="card db-table-card p-3 h-100 <?= $isActive ? 'active-tbl' : '' ?>">
        <div class="d-flex align-items-start justify-content-between mb-1">
            <i class="bi bi-table text-muted" style="font-size:1rem;"></i>
            <?php if ($isProtected): ?><span class="protected-badge"><i class="bi bi-lock-fill me-1"></i>Protected</span><?php endif; ?>
        </div>
        <div class="tbl-count"><?= number_format($cnt) ?></div>
        <div class="tbl-name mt-1"><?= htmlspecialchars($tbl) ?></div>
        <div class="tbl-type text-muted">baris</div>
    </div>
    </a>
</div>
<?php endforeach; ?>
</div>

<!-- ══ SQL RUNNER ══════════════════════════════════════════════════════════ -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2" style="cursor:pointer;"
         data-bs-toggle="collapse" data-bs-target="#sqlRunnerBody">
        <i class="bi bi-terminal-fill text-purple"></i>
        <span class="fw-bold">SQL Runner</span>
        <small class="text-muted">— jalankan query langsung (1 statement, semua tercatat di audit log)</small>
        <i class="bi bi-chevron-down ms-auto text-muted"></i>
    </div>
    <div class="collapse <?= ($sql_text !== '' || $sql_error || $sql_result !== null) ? 'show' : '' ?>" id="sqlRunnerBody">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="run_sql">
                <input type="hidden" name="table" value="<?= htmlspecialchars($active_table ?? '') ?>">
                <textarea name="sql" class="form-control font-monospace mb-2" rows="3" spellcheck="false"
                          placeholder="SELECT * FROM pendaftar LIMIT 10"><?= htmlspecialchars($sql_text) ?></textarea>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-sm fw-semibold text-white" style="background:linear-gradient(135deg,#7c3aed,#a855f7);">
                        <i class="bi bi-play-fill me-1"></i>Jalankan
                    </button>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="confirm_write" value="1" id="confirmWrite">
                        <label class="form-check-label small" for="confirmWrite">
                            Saya paham query ini <strong>mengubah data</strong> (wajib utk UPDATE/DELETE/INSERT/ALTER)
                        </label>
                    </div>
                    <small class="text-muted ms-auto">SELECT/SHOW/DESCRIBE/EXPLAIN bebas · hasil dibatasi 200 baris</small>
                </div>
            </form>

            <?php if ($sql_error): ?>
            <div class="alert alert-danger mt-3 mb-0 py-2 small">
                <i class="bi bi-x-circle-fill me-1"></i><?= htmlspecialchars($sql_error) ?>
            </div>
            <?php elseif ($sql_result !== null): ?>
            <div class="mt-3">
                <div class="small text-muted mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i><?= htmlspecialchars($sql_note) ?></div>
                <?php if (empty($sql_result)): ?>
                <div class="text-muted small fst-italic">Tidak ada hasil.</div>
                <?php else: ?>
                <div class="table-responsive border rounded" style="max-height:360px;overflow-y:auto;">
                    <table class="table table-sm table-hover data-table mb-0">
                        <thead><tr>
                            <?php foreach ($sql_cols as $sc): ?><th><?= htmlspecialchars($sc) ?></th><?php endforeach; ?>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($sql_result as $sr_row): ?>
                        <tr>
                            <?php foreach ($sql_cols as $sc): $sv = $sr_row[$sc]; ?>
                            <td title="<?= htmlspecialchars((string)($sv ?? '')) ?>">
                                <?= $sv === null ? '<span class="badge bg-light text-muted border">NULL</span>' : htmlspecialchars(mb_strlen((string)$sv) > 80 ? mb_substr((string)$sv, 0, 80) . '…' : (string)$sv) ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($active_table):
    $isProtected = in_array($active_table, $DB_PROTECTED);
?>

<!-- bulkForm: hanya bungkus checkbox dalam tabel, TIDAK bungkus card header -->
<form method="POST" id="bulkForm">
    <input type="hidden" name="action" value="delete_selected">
    <input type="hidden" name="table" value="<?= htmlspecialchars($active_table) ?>">
</form>

<!-- Bulk Action Bar — tombol pakai form="bulkForm" agar submit ke form di atas -->
<div class="bulk-bar" id="bulkBar">
    <i class="bi bi-check2-square fs-5"></i>
    <span id="bulkCount" class="fw-semibold">0 dipilih</span>
    <button type="button" class="btn btn-sm btn-outline-light ms-auto" onclick="clearSelection()">Batal Pilih</button>
    <button type="submit" form="bulkForm" class="btn btn-sm btn-danger"
        onclick="return confirm('Hapus ' + document.getElementById('bulkCount').textContent + '?\nAksi ini tidak bisa dibatalkan.')">
        <i class="bi bi-trash3-fill me-1"></i>Hapus yang Dipilih
    </button>
</div>

<div class="card mb-4">
    <!-- Card Header: search + limit (form GET tersendiri, TIDAK di dalam bulkForm) -->
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-table text-purple"></i>
            <span class="fw-bold"><?= htmlspecialchars($active_table) ?></span>
            <span class="badge bg-secondary">
                <?= number_format($total_rows) ?> baris<?= $search !== '' ? ' (difilter dari ' . number_format($table_stats[$active_table] ?? 0) . ')' : '' ?>
            </span>
            <?php if ($isProtected): ?><span class="protected-badge"><i class="bi bi-lock-fill me-1"></i>Protected</span><?php endif; ?>
            <!-- Toggle Data / Struktur -->
            <div class="btn-group btn-group-sm ms-2" role="group">
                <a href="?page=database_manager&tbl=<?= urlencode($active_table) ?>"
                   class="btn <?= $view === 'data' ? 'btn-primary' : 'btn-outline-primary' ?> py-0 px-2" style="font-size:.75rem;">
                    <i class="bi bi-grid-3x3 me-1"></i>Data
                </a>
                <a href="?page=database_manager&tbl=<?= urlencode($active_table) ?>&view=struktur"
                   class="btn <?= $view === 'struktur' ? 'btn-primary' : 'btn-outline-primary' ?> py-0 px-2" style="font-size:.75rem;">
                    <i class="bi bi-diagram-3 me-1"></i>Struktur
                </a>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <!-- Form GET mandiri — tidak ada hubungannya dengan bulkForm -->
            <form method="GET" id="filterForm" class="d-flex gap-1 align-items-center">
                <input type="hidden" name="page" value="database_manager">
                <input type="hidden" name="tbl" value="<?= htmlspecialchars($active_table) ?>">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari..."
                    value="<?= htmlspecialchars($search) ?>" style="width:140px;">
                <select name="limit" class="form-select form-select-sm" style="width:75px;"
                    onchange="document.getElementById('filterForm').submit()">
                    <?php foreach ([25, 50, 75, 100, 200] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $per_page === $opt ? 'selected' : '' ?>><?= $opt ?> baris</option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
            <button type="button" class="btn btn-sm btn-outline-success"
                    data-bs-toggle="modal" data-bs-target="#addRowModal">
                <i class="bi bi-plus-lg me-1"></i>Tambah Baris
            </button>
            <a href="admin/db_export.php?tbl=<?= urlencode($active_table) ?>&amp;q=<?= urlencode($search) ?>"
               class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
            <?php if (!$isProtected): ?>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#truncateModal">
                <i class="bi bi-trash3-fill me-1"></i>Kosongkan Tabel
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Struktur Kolom -->
    <div class="px-3 pt-3 pb-1">
        <div class="d-flex flex-wrap gap-1 mb-2">
        <?php foreach ($columns as $col): ?>
            <span class="col-badge <?= $col['Key'] === 'PRI' ? 'col-pk' : '' ?>">
                <?php if ($col['Key'] === 'PRI'): ?><i class="bi bi-key-fill me-1" style="font-size:.6rem;"></i><?php endif; ?>
                <?= htmlspecialchars($col['Field']) ?>
                <span class="opacity-60 ms-1" style="font-size:.65rem;"><?= htmlspecialchars(explode('(', $col['Type'])[0]) ?></span>
            </span>
            <?php if (isset($fk_map[$col['Field']])): $fkr = $fk_map[$col['Field']]; ?>
            <a href="?page=database_manager&tbl=<?= urlencode($fkr['REFERENCED_TABLE_NAME']) ?>"
               class="col-badge text-decoration-none" style="background:#dbeafe;color:#1d4ed8;"
               title="Foreign key → <?= htmlspecialchars($fkr['REFERENCED_TABLE_NAME'] . '.' . $fkr['REFERENCED_COLUMN_NAME']) ?>">
                <i class="bi bi-link-45deg"></i>FK → <?= htmlspecialchars($fkr['REFERENCED_TABLE_NAME']) ?>
            </a>
            <?php endif; ?>
        <?php endforeach; ?>
        </div>
    </div>

    <?php if ($view === 'struktur'): ?>
    <!-- ══ VIEW STRUKTUR ══════════════════════════════════════════════════ -->
    <div class="card-body">
        <h6 class="fw-bold small text-uppercase text-muted mb-2"><i class="bi bi-list-columns me-1"></i>Kolom</h6>
        <div class="table-responsive border rounded mb-4">
            <table class="table table-sm table-hover data-table mb-0">
                <thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th><th>Relasi</th></tr></thead>
                <tbody>
                <?php foreach ($columns as $col): ?>
                <tr>
                    <td class="fw-semibold">
                        <?php if ($col['Key'] === 'PRI'): ?><i class="bi bi-key-fill text-warning me-1"></i><?php endif; ?>
                        <?= htmlspecialchars($col['Field']) ?>
                    </td>
                    <td><code style="font-size:.75rem;"><?= htmlspecialchars($col['Type']) ?></code></td>
                    <td><?= $col['Null'] === 'YES' ? '<span class="badge bg-light text-muted border">YES</span>' : 'NO' ?></td>
                    <td><?= htmlspecialchars($col['Key'] ?: '—') ?></td>
                    <td><?= $col['Default'] === null ? '<span class="text-muted fst-italic">NULL</span>' : htmlspecialchars((string)$col['Default']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($col['Extra'] ?: '—') ?></td>
                    <td>
                        <?php if (isset($fk_map[$col['Field']])): $fkr = $fk_map[$col['Field']]; ?>
                        <a href="?page=database_manager&tbl=<?= urlencode($fkr['REFERENCED_TABLE_NAME']) ?>" class="badge text-decoration-none" style="background:#dbeafe;color:#1d4ed8;">
                            FK → <?= htmlspecialchars($fkr['REFERENCED_TABLE_NAME'] . '.' . $fkr['REFERENCED_COLUMN_NAME']) ?>
                        </a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($tbl_indexes)): ?>
        <h6 class="fw-bold small text-uppercase text-muted mb-2"><i class="bi bi-sort-alpha-down me-1"></i>Index</h6>
        <div class="table-responsive border rounded">
            <table class="table table-sm table-hover data-table mb-0">
                <thead><tr><th>Nama Index</th><th>Kolom</th><th>Urutan</th><th>Unik?</th></tr></thead>
                <tbody>
                <?php foreach ($tbl_indexes as $ix): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($ix['Key_name']) ?></td>
                    <td><code style="font-size:.75rem;"><?= htmlspecialchars($ix['Column_name']) ?></code></td>
                    <td><?= (int)$ix['Seq_in_index'] ?></td>
                    <td><?= (int)$ix['Non_unique'] === 0 ? '<span class="badge bg-success">Unik</span>' : '<span class="badge bg-secondary">Tidak</span>' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Data Rows — checkbox pakai form="bulkForm" agar terhubung ke bulkForm di luar card -->
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:480px; overflow-y:auto;">
            <table class="table table-hover data-table mb-0" id="dataTable">
                <thead>
                    <tr>
                        <th style="width:36px;">
                            <input type="checkbox" class="row-cb" id="checkAll" title="Pilih semua">
                        </th>
                        <?php foreach ($columns as $col):
                            $cn       = $col['Field'];
                            $is_sort  = $sort_col === $cn;
                            $next_dir = ($is_sort && $sort_dir === 'ASC') ? 'DESC' : 'ASC';
                            $sort_url = '?page=database_manager&tbl=' . urlencode($active_table)
                                . '&q=' . urlencode($search) . '&limit=' . $per_page
                                . '&sort=' . urlencode($cn) . '&dir=' . $next_dir;
                        ?>
                        <th style="cursor:pointer;user-select:none;white-space:nowrap;"
                            onclick="location='<?= htmlspecialchars($sort_url) ?>'" title="<?= htmlspecialchars($col['Type']) ?>">
                            <?= htmlspecialchars($cn) ?>
                            <?php if ($is_sort): ?>
                                <i class="bi bi-caret-<?= $sort_dir === 'ASC' ? 'up' : 'down' ?>-fill text-purple ms-1" style="font-size:.6rem;"></i>
                            <?php else: ?>
                                <i class="bi bi-arrow-down-up text-muted opacity-25 ms-1" style="font-size:.6rem;"></i>
                            <?php endif; ?>
                            <div class="text-muted fw-normal" style="font-size:.6rem;line-height:1;"><?= htmlspecialchars(explode('(', $col['Type'])[0]) ?></div>
                        </th>
                        <?php endforeach; ?>
                        <th style="width:72px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($columns) + 3 ?>" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>Tidak ada data
                </td></tr>
                <?php else: foreach ($rows as $i => $row):
                    $row_pk_val = $row[$pk_col] ?? null;
                ?>
                <tr>
                    <td>
                        <?php if ($row_pk_val !== null): ?>
                        <!-- form="bulkForm" menghubungkan checkbox ini ke bulkForm di luar -->
                        <input type="checkbox" class="row-cb row-check" name="selected_ids[]"
                            value="<?= htmlspecialchars((string)$row_pk_val) ?>" form="bulkForm">
                        <?php endif; ?>
                    </td>
                    <?php foreach ($columns as $col):
                        $val = $row[$col['Field']] ?? null;
                        $is_long = $val !== null && mb_strlen((string)$val) > 80;
                        if ($val === null) {
                            $display = '<span class="badge bg-light text-muted border" style="font-size:.65rem;">NULL</span>';
                        } elseif ($is_long) {
                            $display = htmlspecialchars(mb_substr((string)$val, 0, 80)) . '…';
                        } else {
                            $display = htmlspecialchars((string)$val);
                        }
                    ?>
                    <td>
                        <?= $display ?>
                        <?php if ($is_long): ?>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 align-baseline" style="font-size:.7rem;"
                                data-full="<?= htmlspecialchars((string)$val) ?>"
                                data-col="<?= htmlspecialchars($col['Field']) ?>"
                                onclick="showCellValue(this)">lihat</button>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                    <td>
                        <?php if ($row_pk_val !== null): ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1 me-1"
                            onclick='openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($pk_col), ENT_QUOTES) ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1"
                            onclick="deleteSingle(<?= (int)$row_pk_val ?>, '<?= htmlspecialchars($pk_col) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
        <small class="text-muted">
            <?= ($cur_page - 1) * $per_page + 1 ?>–<?= min($cur_page * $per_page, $total_rows) ?> dari <?= number_format($total_rows) ?> baris
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($pg = max(1, $cur_page - 3); $pg <= min($total_pages, $cur_page + 3); $pg++): ?>
                <li class="page-item <?= $pg === $cur_page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=database_manager&tbl=<?= urlencode($active_table) ?>&p=<?= $pg ?>&q=<?= urlencode($search) ?>&limit=<?= $per_page ?>"><?= $pg ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; // view data/struktur ?>
</div><!-- /card -->

<!-- Modal: Tambah Baris -->
<div class="modal fade" id="addRowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-purple"></i>Tambah Baris — <code><?= htmlspecialchars($active_table) ?></code></h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="insert_row">
                <input type="hidden" name="table" value="<?= htmlspecialchars($active_table) ?>">
                <div class="modal-body">
                    <div class="row g-2" id="addRowFields">
                    <?php foreach ($columns as $col):
                        $is_pk_auto = ($col['Key'] === 'PRI' && stripos($col['Extra'] ?? '', 'auto_increment') !== false);
                        if ($is_pk_auto) continue;
                        $fname = 'fields[' . htmlspecialchars($col['Field']) . ']';
                        $req   = ($col['Null'] === 'NO' && $col['Default'] === null && stripos($col['Extra'] ?? '', 'auto_increment') === false);
                        $type  = strtolower($col['Type']);
                        $def   = $col['Default'] ?? '';
                    ?>
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold mb-1">
                            <?= htmlspecialchars($col['Field']) ?>
                            <?php if ($col['Key'] === 'PRI'): ?><span class="col-pk col-badge ms-1">PK</span><?php endif; ?>
                            <small class="text-muted fw-normal"><?= htmlspecialchars(explode('(', $col['Type'])[0]) ?></small>
                        </label>
                        <?php if (strpos($type, 'enum') === 0):
                            preg_match_all("/'([^']+)'/", $col['Type'], $em); ?>
                        <select name="<?= $fname ?>" class="form-select form-select-sm" <?= $req ? 'required' : '' ?>>
                            <?php if (!$req): ?><option value="">— NULL —</option><?php endif; ?>
                            <?php foreach ($em[1] as $ev): ?>
                            <option value="<?= htmlspecialchars($ev) ?>" <?= $def === $ev ? 'selected' : '' ?>><?= htmlspecialchars($ev) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php elseif (strpos($type, 'text') !== false): ?>
                        <textarea name="<?= $fname ?>" class="form-control form-control-sm" rows="2" <?= $req ? 'required' : '' ?>><?= htmlspecialchars($def) ?></textarea>
                        <?php else:
                            if (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) $inp = 'datetime-local';
                            elseif (strpos($type, 'date') === 0) $inp = 'date';
                            elseif (preg_match('/^(int|tinyint|smallint|mediumint|bigint)/', $type)) $inp = 'number';
                            else $inp = 'text';
                        ?>
                        <input type="<?= $inp ?>" name="<?= $fname ?>" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($def) ?>" <?= $req ? 'required' : '' ?>
                               <?= $inp === 'number' ? 'step="1"' : '' ?>>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Baris -->
<div class="modal fade" id="editRowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2 text-purple"></i>Edit Baris — <code><?= htmlspecialchars($active_table) ?></code></h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editRowForm">
                <input type="hidden" name="action" value="update_row">
                <input type="hidden" name="table" value="<?= htmlspecialchars($active_table) ?>">
                <input type="hidden" name="row_id" id="editRowId">
                <div class="modal-body">
                    <div class="row g-2" id="editRowFields">
                    <?php foreach ($columns as $col):
                        $is_pk = ($col['Key'] === 'PRI');
                        $fname = 'fields[' . htmlspecialchars($col['Field']) . ']';
                        $type  = strtolower($col['Type']);
                    ?>
                    <div class="col-sm-6 edit-field-wrap" data-field="<?= htmlspecialchars($col['Field']) ?>">
                        <label class="form-label small fw-semibold mb-1">
                            <?= htmlspecialchars($col['Field']) ?>
                            <?php if ($is_pk): ?><span class="col-pk col-badge ms-1">PK</span><?php endif; ?>
                            <small class="text-muted fw-normal"><?= htmlspecialchars(explode('(', $col['Type'])[0]) ?></small>
                        </label>
                        <?php if ($is_pk): ?>
                        <input type="text" class="form-control form-control-sm edit-field" data-field="<?= htmlspecialchars($col['Field']) ?>" disabled readonly>
                        <?php elseif (strpos($type, 'enum') === 0):
                            preg_match_all("/'([^']+)'/", $col['Type'], $em); ?>
                        <select name="<?= $fname ?>" class="form-select form-select-sm edit-field" data-field="<?= htmlspecialchars($col['Field']) ?>">
                            <option value="">— NULL —</option>
                            <?php foreach ($em[1] as $ev): ?>
                            <option value="<?= htmlspecialchars($ev) ?>"><?= htmlspecialchars($ev) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php elseif (strpos($type, 'text') !== false): ?>
                        <textarea name="<?= $fname ?>" class="form-control form-control-sm edit-field" rows="2" data-field="<?= htmlspecialchars($col['Field']) ?>"></textarea>
                        <?php else:
                            if (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) $inp = 'datetime-local';
                            elseif (strpos($type, 'date') === 0) $inp = 'date';
                            elseif (preg_match('/^(int|tinyint|smallint|mediumint|bigint)/', $type)) $inp = 'number';
                            else $inp = 'text';
                        ?>
                        <input type="<?= $inp ?>" name="<?= $fname ?>" class="form-control form-control-sm edit-field"
                               data-field="<?= htmlspecialchars($col['Field']) ?>"
                               <?= $inp === 'number' ? 'step="1"' : '' ?>>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden form untuk delete single row -->
<form method="POST" id="singleDeleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete_row">
    <input type="hidden" name="table" value="<?= htmlspecialchars($active_table) ?>">
    <input type="hidden" name="row_id" id="singleDeleteId">
</form>

<!-- TRUNCATE Modal -->
<?php if (!$isProtected): ?>
<div class="modal fade" id="truncateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-danger bg-danger bg-opacity-10">
                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Kosongkan Tabel</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="truncate">
                <input type="hidden" name="table" value="<?= htmlspecialchars($active_table) ?>">
                <div class="modal-body">
                    <div class="truncate-zone mb-3">
                        <p class="mb-1 fw-semibold text-danger">Peringatan!</p>
                        <p class="mb-0 small">Semua <strong><?= number_format($total_rows) ?> baris</strong> di tabel <code><?= htmlspecialchars($active_table) ?></code> akan dihapus permanen. Aksi ini tidak bisa dibatalkan.</p>
                    </div>
                    <label class="form-label fw-semibold">Ketik nama tabel untuk konfirmasi:</label>
                    <input type="text" name="confirm" class="form-control" placeholder="<?= htmlspecialchars($active_table) ?>" autocomplete="off" required>
                    <div class="form-text text-muted">Ketik: <code><?= htmlspecialchars($active_table) ?></code></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash3-fill me-1"></i>Ya, Kosongkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: lihat nilai sel penuh -->
<div class="modal fade" id="cellViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-textarea-t me-2 text-purple"></i>Isi Kolom: <code id="cellViewCol"></code></h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="cellViewBody" class="mb-0 small" style="white-space:pre-wrap;word-break:break-word;max-height:60vh;overflow-y:auto;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
const checkAll  = document.getElementById('checkAll');
const bulkBar   = document.getElementById('bulkBar');
const bulkCount = document.getElementById('bulkCount');

function updateBulkBar() {
    if (!checkAll || !bulkBar) return;
    const checked = document.querySelectorAll('.row-check:checked');
    bulkCount.textContent = checked.length + ' dipilih';
    bulkBar.classList.toggle('show', checked.length > 0);
    checkAll.indeterminate = checked.length > 0 && checked.length < document.querySelectorAll('.row-check').length;
    checkAll.checked = checked.length > 0 && checked.length === document.querySelectorAll('.row-check').length;
}

if (checkAll) {
    checkAll.addEventListener('change', () => {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = checkAll.checked);
        updateBulkBar();
    });
}

document.querySelectorAll('.row-check').forEach(cb => cb.addEventListener('change', updateBulkBar));

function showCellValue(btn) {
    document.getElementById('cellViewCol').textContent  = btn.dataset.col || '';
    document.getElementById('cellViewBody').textContent = btn.dataset.full || '';
    new bootstrap.Modal(document.getElementById('cellViewModal')).show();
}

function clearSelection() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    checkAll.checked = false;
    updateBulkBar();
}

function deleteSingle(id, pk) {
    if (!confirm('Hapus baris ' + pk + ' = ' + id + '?\nAksi ini tidak bisa dibatalkan.')) return;
    document.getElementById('singleDeleteId').value = id;
    document.getElementById('singleDeleteForm').submit();
}

function openEditModal(row, pkCol) {
    document.getElementById('editRowId').value = row[pkCol] ?? '';
    document.querySelectorAll('#editRowModal .edit-field').forEach(el => {
        const field = el.dataset.field;
        const val   = row[field];
        if (el.tagName === 'SELECT') {
            el.value = val ?? '';
        } else if (el.tagName === 'TEXTAREA') {
            el.value = val ?? '';
        } else {
            // datetime-local needs "YYYY-MM-DDTHH:MM" format
            if (el.type === 'datetime-local' && val) {
                el.value = val.replace(' ', 'T').substring(0, 16);
            } else {
                el.value = val ?? '';
            }
        }
    });
    new bootstrap.Modal(document.getElementById('editRowModal')).show();
}
</script>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-hand-index-thumb fs-1 d-block mb-2"></i>
        <p class="mb-0">Pilih tabel di atas untuk melihat isinya</p>
    </div>
</div>
<?php endif; ?>
