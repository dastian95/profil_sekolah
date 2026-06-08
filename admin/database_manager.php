<?php
// Hanya bisa diakses via superadmin_dashboard.php (session sudah dicek di sana)

// Tabel yang TIDAK boleh di-truncate (data kritis)
const DB_PROTECTED = ['admins', 'site_settings', 'gelombang', 'tahapan', 'meja'];

// Tabel yang bisa dipilih (whitelist dari SHOW TABLES)
$all_tables_stmt = $conn->query("SHOW TABLES");
$all_tables = $all_tables_stmt->fetchAll(PDO::FETCH_COLUMN);

// ── POST handler ───────────────────────────────────────────────────────────────
$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']    ?? '';
    $tbl       = $_POST['table']     ?? '';
    $confirm   = $_POST['confirm']   ?? '';

    if (!in_array($tbl, $all_tables, true)) {
        $flash = ['type' => 'danger', 'msg' => 'Tabel tidak valid.'];
    } elseif ($action === 'truncate') {
        if (in_array($tbl, DB_PROTECTED)) {
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
        $pk     = $_POST['pk'] ?? 'id';
        if ($row_id <= 0) {
            $flash = ['type' => 'danger', 'msg' => 'ID tidak valid.'];
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM `$tbl` WHERE `$pk` = ?");
                $stmt->execute([$row_id]);
                log_admin_action($conn, 'DB_DELETE_ROW', "Tabel: $tbl, $pk=$row_id");
                $flash = ['type' => 'success', 'msg' => "Baris dengan $pk=$row_id dihapus dari <strong>$tbl</strong>."];
            } catch (PDOException $e) {
                $flash = ['type' => 'danger', 'msg' => 'Gagal: ' . htmlspecialchars($e->getMessage())];
            }
        }
    }
    // Redirect to same table to avoid repost
    $redir_tbl = $tbl ?: ($active_table ?? '');
    header("Location: ?page=database_manager" . ($redir_tbl ? "&tbl=$redir_tbl" : '') . "&flash=" . urlencode($flash['type'] . ':' . strip_tags($flash['msg'])));
    exit;
}

// Flash dari redirect
if (isset($_GET['flash']) && !$flash) {
    $parts = explode(':', $_GET['flash'], 2);
    if (count($parts) === 2) $flash = ['type' => $parts[0], 'msg' => htmlspecialchars($parts[1])];
}

// Tabel aktif yang dipilih
$active_table = isset($_GET['tbl']) && in_array($_GET['tbl'], $all_tables, true) ? $_GET['tbl'] : null;

// Stats semua tabel
$table_stats = [];
foreach ($all_tables as $t) {
    $cnt = $conn->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    $table_stats[$t] = (int)$cnt;
}

// Data tabel aktif
$rows = [];
$columns = [];
$pk_col = 'id';
$total_rows = 0;
$per_page = 25;
$cur_page = max(1, (int)($_GET['p'] ?? 1));
$search = trim($_GET['q'] ?? '');

if ($active_table) {
    // Kolom
    $col_stmt = $conn->query("DESCRIBE `$active_table`");
    $columns = $col_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $c) {
        if ($c['Key'] === 'PRI') { $pk_col = $c['Field']; break; }
    }

    // Search sederhana (hanya kolom text/varchar)
    $text_cols = array_filter($columns, fn($c) => preg_match('/char|text|enum/i', $c['Type']));
    $where = '';
    $params = [];
    if ($search && $text_cols) {
        $parts = [];
        foreach ($text_cols as $c) {
            $parts[] = "`{$c['Field']}` LIKE ?";
            $params[] = "%$search%";
        }
        $where = 'WHERE ' . implode(' OR ', $parts);
    }

    $total_rows = (int)$conn->prepare("SELECT COUNT(*) FROM `$active_table` $where")->execute($params) ?
        $conn->prepare("SELECT COUNT(*) FROM `$active_table` $where")->execute($params) &&
        $conn->query("SELECT COUNT(*) FROM `$active_table` $where" . ($params ? '' : ''))->fetchColumn() : 0;

    // Lebih simpel:
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM `$active_table` $where");
    $count_stmt->execute($params);
    $total_rows = (int)$count_stmt->fetchColumn();

    $offset = ($cur_page - 1) * $per_page;
    $data_stmt = $conn->prepare("SELECT * FROM `$active_table` $where ORDER BY `$pk_col` DESC LIMIT $per_page OFFSET $offset");
    $data_stmt->execute($params);
    $rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_pages = $total_rows ? (int)ceil($total_rows / $per_page) : 1;
?>

<style>
.db-table-card {
    cursor: pointer; transition: all .15s ease;
    border: 2px solid transparent;
    border-radius: 10px;
}
.db-table-card:hover { border-color: #7c3aed; box-shadow: 0 4px 12px rgba(124,58,237,.12); }
.db-table-card.active-tbl { border-color: #7c3aed; background: #faf5ff; }
.db-table-card .tbl-name { font-weight: 600; font-size: .9rem; color: #1e1b4b; }
.db-table-card .tbl-count { font-size: 1.4rem; font-weight: 700; color: #7c3aed; line-height: 1; }
.db-table-card .tbl-type { font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; }
.protected-badge { font-size: .65rem; background: #fef3c7; color: #92400e; border-radius: 4px; padding: 1px 5px; }
.data-table { font-size: .8rem; }
.data-table th { position: sticky; top: 0; background: #f8fafc; z-index: 1; white-space: nowrap; }
.data-table td { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.data-table td:hover { white-space: normal; max-width: none; }
.truncate-zone { background: #fff5f5; border: 1px solid #fed7d7; border-radius: 10px; padding: 16px 18px; }
.col-badge { font-size: .7rem; padding: 2px 6px; border-radius: 4px; background: #f1f5f9; color: #475569; }
.col-pk { background: #fef9c3; color: #854d0e; }
.col-null { background: #f0fdf4; color: #166534; }
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

<!-- Tabel Overview Grid -->
<div class="row g-3 mb-4">
<?php foreach ($table_stats as $tbl => $cnt):
    $isProtected = in_array($tbl, DB_PROTECTED);
    $isActive = $active_table === $tbl;
?>
<div class="col-6 col-md-4 col-lg-3">
    <a href="?page=database_manager&tbl=<?= $tbl ?>" class="text-decoration-none">
    <div class="card db-table-card p-3 h-100 <?= $isActive ? 'active-tbl' : '' ?>">
        <div class="d-flex align-items-start justify-content-between mb-1">
            <i class="bi bi-table text-muted" style="font-size:1rem;"></i>
            <?php if ($isProtected): ?>
            <span class="protected-badge"><i class="bi bi-lock-fill me-1"></i>Protected</span>
            <?php endif; ?>
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
    $isProtected = in_array($active_table, DB_PROTECTED);
?>
<!-- Detail Tabel Aktif -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-table text-purple"></i>
            <span class="fw-bold"><?= htmlspecialchars($active_table) ?></span>
            <span class="badge bg-secondary"><?= number_format($total_rows) ?> baris</span>
            <?php if ($isProtected): ?><span class="protected-badge"><i class="bi bi-lock-fill me-1"></i>Protected</span><?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <!-- Search -->
            <form method="GET" class="d-flex gap-1">
                <input type="hidden" name="page" value="database_manager">
                <input type="hidden" name="tbl" value="<?= htmlspecialchars($active_table) ?>">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>" style="width:160px;">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
            <?php if (!$isProtected): ?>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#truncateModal">
                <i class="bi bi-trash3-fill me-1"></i>Kosongkan Tabel
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Struktur Kolom -->
    <div class="px-3 pt-3 pb-1">
        <div class="d-flex flex-wrap gap-1 mb-2">
        <?php foreach ($columns as $col): ?>
            <span class="col-badge <?= $col['Key'] === 'PRI' ? 'col-pk' : '' ?> <?= $col['Null'] === 'YES' ? 'col-null' : '' ?>">
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
            <table class="table table-hover data-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <?php foreach ($columns as $col): ?>
                        <th><?= htmlspecialchars($col['Field']) ?></th>
                        <?php endforeach; ?>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="<?= count($columns) + 2 ?>" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>Tidak ada data
                </td></tr>
                <?php else: foreach ($rows as $i => $row): ?>
                <tr>
                    <td class="text-muted"><?= ($cur_page - 1) * $per_page + $i + 1 ?></td>
                    <?php foreach ($columns as $col):
                        $val = $row[$col['Field']] ?? null;
                        $display = $val === null ? '<span class="text-muted fst-italic">NULL</span>' : htmlspecialchars((string)$val);
                    ?>
                    <td title="<?= htmlspecialchars((string)($val ?? '')) ?>"><?= $display ?></td>
                    <?php endforeach; ?>
                    <td>
                        <?php $row_pk_val = $row[$pk_col] ?? null; ?>
                        <?php if ($row_pk_val !== null): ?>
                        <form method="POST" onsubmit="return confirm('Hapus baris ini?\n<?= htmlspecialchars($pk_col) ?> = <?= htmlspecialchars((string)$row_pk_val) ?>')">
                            <input type="hidden" name="action" value="delete_row">
                            <input type="hidden" name="table" value="<?= htmlspecialchars($active_table) ?>">
                            <input type="hidden" name="pk" value="<?= htmlspecialchars($pk_col) ?>">
                            <input type="hidden" name="row_id" value="<?= htmlspecialchars((string)$row_pk_val) ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
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
            Menampilkan <?= ($cur_page - 1) * $per_page + 1 ?>–<?= min($cur_page * $per_page, $total_rows) ?> dari <?= number_format($total_rows) ?> baris
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($pg = max(1, $cur_page - 3); $pg <= min($total_pages, $cur_page + 3); $pg++): ?>
                <li class="page-item <?= $pg === $cur_page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=database_manager&tbl=<?= $active_table ?>&p=<?= $pg ?>&q=<?= urlencode($search) ?>"><?= $pg ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

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

<?php else: ?>
<!-- State kosong — belum pilih tabel -->
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-hand-index-thumb fs-1 d-block mb-2"></i>
        <p class="mb-0">Pilih tabel di atas untuk melihat isinya</p>
    </div>
</div>
<?php endif; ?>

<style>
.text-purple { color: #7c3aed; }
</style>
