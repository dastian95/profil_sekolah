<?php
// Hanya bisa diakses via superadmin_dashboard.php (session sudah dicek di sana)

$DB_PROTECTED = ['admins', 'site_settings', 'gelombang', 'tahapan', 'meja'];
$all_tables   = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// ── POST handler ───────────────────────────────────────────────────────────────
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        }
    }

    $_SESSION['dbm_flash'] = $flash;
    $redir_url = '?page=database_manager' . ($tbl ? '&tbl=' . urlencode($tbl) : '');
    echo '<script>window.location.replace(' . json_encode($redir_url) . ');</script>';
    return;
}

if (isset($_SESSION['dbm_flash'])) {
    $flash = $_SESSION['dbm_flash'];
    unset($_SESSION['dbm_flash']);
}

$active_table = isset($_GET['tbl']) && in_array($_GET['tbl'], $all_tables, true) ? $_GET['tbl'] : null;

$table_stats = [];
foreach ($all_tables as $t) {
    $table_stats[$t] = (int)$conn->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}

$rows = []; $columns = []; $pk_col = 'id';
$total_rows = 0;
$cur_page = max(1, (int)($_GET['p'] ?? 1));
$search   = trim($_GET['q'] ?? '');
$per_page = in_array((int)($_GET['limit'] ?? 25), [25, 50, 75, 100, 200]) ? (int)$_GET['limit'] : 25;

if ($active_table) {
    $columns = $conn->query("DESCRIBE `$active_table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $c) {
        if ($c['Key'] === 'PRI') { $pk_col = $c['Field']; break; }
    }
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
    $ds = $conn->prepare("SELECT * FROM `$active_table` $where ORDER BY `$pk_col` DESC LIMIT $per_page OFFSET $offset");
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

<?php if ($active_table):
    $isProtected = in_array($active_table, $DB_PROTECTED);
?>

<!-- Bulk-delete form membungkus tabel + action bar -->
<form method="POST" id="bulkForm">
    <input type="hidden" name="action" value="delete_selected">
    <input type="hidden" name="table" value="<?= htmlspecialchars($active_table) ?>">

    <!-- Bulk Action Bar (muncul saat ada yang dicentang) -->
    <div class="bulk-bar" id="bulkBar">
        <i class="bi bi-check2-square fs-5"></i>
        <span id="bulkCount" class="fw-semibold">0 dipilih</span>
        <button type="button" class="btn btn-sm btn-outline-light ms-auto" onclick="clearSelection()">Batal Pilih</button>
        <button type="submit" class="btn btn-sm btn-danger"
            onclick="return confirm('Hapus ' + document.getElementById('bulkCount').textContent + '?\nAksi ini tidak bisa dibatalkan.')">
            <i class="bi bi-trash3-fill me-1"></i>Hapus yang Dipilih
        </button>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-table text-purple"></i>
                <span class="fw-bold"><?= htmlspecialchars($active_table) ?></span>
                <span class="badge bg-secondary"><?= number_format($total_rows) ?> baris</span>
                <?php if ($isProtected): ?><span class="protected-badge"><i class="bi bi-lock-fill me-1"></i>Protected</span><?php endif; ?>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <!-- Search + Limit -->
                <form method="GET" class="d-flex gap-1 align-items-center">
                    <input type="hidden" name="page" value="database_manager">
                    <input type="hidden" name="tbl" value="<?= htmlspecialchars($active_table) ?>">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>" style="width:140px;">
                    <select name="limit" class="form-select form-select-sm" style="width:75px;" onchange="this.form.submit()">
                        <?php foreach ([25, 50, 75, 100, 200] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $per_page === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
                </form>
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
            <?php endforeach; ?>
            </div>
        </div>

        <!-- Data Rows -->
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:480px; overflow-y:auto;">
                <table class="table table-hover data-table mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th style="width:36px;">
                                <input type="checkbox" class="row-cb" id="checkAll" title="Pilih semua">
                            </th>
                            <?php foreach ($columns as $col): ?>
                            <th><?= htmlspecialchars($col['Field']) ?></th>
                            <?php endforeach; ?>
                            <th style="width:60px;">Aksi</th>
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
                            <input type="checkbox" class="row-cb row-check" name="selected_ids[]" value="<?= htmlspecialchars((string)$row_pk_val) ?>">
                            <?php endif; ?>
                        </td>
                        <?php foreach ($columns as $col):
                            $val = $row[$col['Field']] ?? null;
                            $display = $val === null
                                ? '<span class="text-muted fst-italic" style="font-size:.75rem;">NULL</span>'
                                : htmlspecialchars((string)$val);
                        ?>
                        <td title="<?= htmlspecialchars((string)($val ?? '')) ?>"><?= $display ?></td>
                        <?php endforeach; ?>
                        <td>
                            <?php if ($row_pk_val !== null): ?>
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
    </div><!-- /card -->
</form><!-- /bulkForm -->

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

<script>
const checkAll  = document.getElementById('checkAll');
const bulkBar   = document.getElementById('bulkBar');
const bulkCount = document.getElementById('bulkCount');

function updateBulkBar() {
    const checked = document.querySelectorAll('.row-check:checked');
    bulkCount.textContent = checked.length + ' dipilih';
    bulkBar.classList.toggle('show', checked.length > 0);
    checkAll.indeterminate = checked.length > 0 && checked.length < document.querySelectorAll('.row-check').length;
    checkAll.checked = checked.length > 0 && checked.length === document.querySelectorAll('.row-check').length;
}

checkAll.addEventListener('change', () => {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = checkAll.checked);
    updateBulkBar();
});

document.querySelectorAll('.row-check').forEach(cb => cb.addEventListener('change', updateBulkBar));

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
</script>

<?php else: ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-hand-index-thumb fs-1 d-block mb-2"></i>
        <p class="mb-0">Pilih tabel di atas untuk melihat isinya</p>
    </div>
</div>
<?php endif; ?>
