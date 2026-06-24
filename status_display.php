<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

// AJAX endpoint
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    try {
        $q    = trim($_GET['q'] ?? '');
        $gel  = (int)($_GET['gel'] ?? 0);
        $params = [];
        $where  = ['1=1'];
        if ($q !== '') {
            $where[] = '(nama LIKE ? OR nisn LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($gel > 0) {
            $where[] = 'gelombang = ?';
            $params[] = $gel;
        }
        $jur = trim($_GET['jur'] ?? '');
        if ($jur !== '' && in_array($jur, JURUSAN_LIST, true)) {
            $where[] = 'jurusan = ?';
            $params[] = $jur;
        }
        // Subquery hitung peringkat per-jurusan berdasarkan nilai_akhir (tidak terpengaruh filter)
        $sql  = "SELECT p.id, p.nama, p.nisn, p.jurusan, p.gelombang, p.nilai_akhir, p.nilai_raport, p.nilai_tka, p.status,
                        (SELECT COUNT(*)+1 FROM pendaftar p2
                         WHERE p2.jurusan = p.jurusan AND p2.nilai_akhir > p.nilai_akhir) AS peringkat
                 FROM pendaftar p WHERE " . implode(' AND ', $where) . "
                 ORDER BY p.jurusan ASC, p.nilai_akhir DESC LIMIT 500";
        $st   = $conn->prepare($sql);
        $st->execute($params);
        echo json_encode(['rows' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Throwable $e) {
        echo json_encode(['rows' => [], 'error' => $e->getMessage()]);
    }
    exit;
}

// Hitung total per status untuk SSR
$totals = ['terima' => 0, 'gugur' => 0, 'diproses' => 0];
try {
    $st = $conn->query("SELECT status, COUNT(*) as n FROM pendaftar GROUP BY status");
    foreach ($st as $r) {
        $k = in_array($r['status'], ['terima','gugur','diproses']) ? $r['status'] : 'diproses';
        $totals[$k] += (int)$r['n'];
    }
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cek Status Pendaftaran — SMKS Laboratorium Jakarta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; font-family:'Segoe UI',-apple-system,BlinkMacSystemFont,sans-serif; background:#eef2f7; color:#1e293b; }

.top-title {
    background: linear-gradient(135deg,#1d4ed8,#3b82f6);
    color:#fff; text-align:center;
    font-size:1rem; font-weight:700;
    padding:10px 20px; letter-spacing:.3px;
}

.search-bar {
    background:#fff;
    padding:14px 20px;
    display:flex; align-items:center; gap:10px;
    border-bottom:1px solid #e2e8f0;
    flex-wrap:wrap;
}
.search-bar input {
    flex:1; min-width:200px;
    border:1.5px solid #cbd5e1; border-radius:8px;
    padding:8px 14px; font-size:.9rem; outline:none;
    transition:border-color .15s;
}
.search-bar input:focus { border-color:#3b82f6; }
.search-bar select {
    border:1.5px solid #cbd5e1; border-radius:8px;
    padding:8px 12px; font-size:.85rem; background:#f8fafc; outline:none;
    cursor:pointer;
}
.search-bar select:focus { border-color:#3b82f6; }
.stat-chips { display:flex; gap:8px; margin-left:auto; flex-wrap:wrap; }
.chip {
    font-size:.72rem; font-weight:700; padding:4px 12px; border-radius:20px;
    display:flex; align-items:center; gap:5px;
}
.chip.terima  { background:#dcfce7; color:#15803d; }
.chip.gugur   { background:#fee2e2; color:#b91c1c; }
.chip.proses  { background:#fef9c3; color:#b45309; }

.table-wrap {
    overflow-y:auto;
    height: calc(100vh - 126px);
    scrollbar-width:thin;
    scrollbar-color:#cbd5e1 transparent;
}
table {
    width:100%; border-collapse:collapse; table-layout:fixed;
}
thead th {
    position:sticky; top:0; z-index:2;
    background:#f1f5f9;
    font-size:.62rem; text-transform:uppercase; letter-spacing:.9px;
    color:#64748b; font-weight:700;
    padding:8px 10px;
    border-bottom:2px solid #e2e8f0;
    white-space:nowrap;
}
thead th.c-no   { width:42px; text-align:center; }
thead th.c-val  { width:70px; text-align:right; }
thead th.c-stat { width:110px; text-align:center; }
thead th.c-jur  { width:120px; }

tbody tr { border-bottom:1px solid #f1f5f9; transition:background .1s; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody tr:hover { background:#eff6ff; }
tbody td { padding:9px 10px; font-size:.82rem; vertical-align:middle; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
tbody td.c-no  { text-align:center; }
tbody td.c-val { text-align:right; font-weight:600; font-variant-numeric:tabular-nums; }

.nm  { font-weight:600; color:#0f172a; }
.nsn { font-size:.63rem; color:#94a3b8; display:block; font-variant-numeric:tabular-nums; }

.badge-status {
    font-size:.68rem; font-weight:700; padding:3px 10px; border-radius:20px;
    display:inline-block;
}
.badge-status.terima  { background:#16a34a; color:#fff; }
.badge-status.gugur   { background:#dc2626; color:#fff; }
.badge-status.diproses{ background:#d1d5db; color:#374151; }
.badge-status.other   { background:#d1d5db; color:#374151; }

.val-na { color:#059669; font-weight:700; }
.val-rp { color:#374151; }
.val-tk { color:#2563eb; }

.no-result {
    text-align:center; padding:60px 20px;
    color:#94a3b8; font-size:.9rem;
}
.no-result i { font-size:2.5rem; display:block; margin-bottom:8px; }
</style>
</head>
<body>

<div class="top-title">Cek Status Pendaftaran — SMKS Laboratorium Jakarta</div>

<div class="search-bar">
    <i class="bi bi-search" style="color:#94a3b8;font-size:1.1rem;flex-shrink:0;"></i>
    <input type="text" id="searchInput" placeholder="Cari nama atau NISN..." autocomplete="off">
    <select id="gelFilter">
        <option value="0">Semua Gelombang</option>
        <option value="1">Gelombang 1</option>
        <option value="2">Gelombang 2</option>
    </select>
    <select id="jurFilter">
        <option value="">Semua Jurusan</option>
        <?php foreach (JURUSAN_LIST as $j): ?>
        <option value="<?= htmlspecialchars($j) ?>"><?= htmlspecialchars(JURUSAN_SHORT[$j] ?? $j) ?> — <?= htmlspecialchars($j) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="stat-chips">
        <span class="chip terima"><i class="bi bi-check-circle-fill"></i> Diterima: <strong id="ct"><?= $totals['terima'] ?></strong></span>
        <span class="chip gugur"><i class="bi bi-x-circle-fill"></i> Gugur: <strong id="cg"><?= $totals['gugur'] ?></strong></span>
        <span class="chip proses"><i class="bi bi-hourglass-split"></i> Diproses: <strong id="cp"><?= $totals['diproses'] ?></strong></span>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th class="c-no">#</th>
                <th>Nama / NISN</th>
                <th class="c-jur">Jurusan</th>
                <th class="c-val">Raport</th>
                <th class="c-val">TKA</th>
                <th class="c-val">Akhir</th>
                <th class="c-stat">Status</th>
            </tr>
        </thead>
        <tbody id="tbody">
            <tr><td colspan="7" class="no-result"><i class="bi bi-search"></i>Ketik nama untuk mencari, atau biarkan kosong untuk lihat semua</td></tr>
        </tbody>
    </table>
</div>

<script>
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmt(v){ return (v===null||v===undefined||v==='')?'—':parseFloat(v).toFixed(2); }

function statusBadge(s) {
    const map = { terima:'Diterima', gugur:'Gugur', diproses:'Diproses' };
    const cls = ['terima','gugur','diproses'].includes(s) ? s : 'other';
    return `<span class="badge-status ${cls}">${map[s] || s}</span>`;
}

const SHORT = <?= json_encode(JURUSAN_SHORT, JSON_UNESCAPED_UNICODE) ?>;

let timer = null;
function doSearch() {
    clearTimeout(timer);
    timer = setTimeout(fetchData, 280);
}

function fetchData() {
    const q   = document.getElementById('searchInput').value.trim();
    const gel = document.getElementById('gelFilter').value;
    const jur = document.getElementById('jurFilter').value;
    fetch(`status_display.php?json=1&q=${encodeURIComponent(q)}&gel=${gel}&jur=${encodeURIComponent(jur)}&_=${Date.now()}`, {cache:'no-store'})
        .then(r => r.json())
        .then(d => render(d.rows || []))
        .catch(() => {});
}

function render(rows) {
    const tbody = document.getElementById('tbody');
    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="no-result"><i class="bi bi-inbox"></i>Tidak ada data yang cocok</td></tr>';
        return;
    }
    tbody.innerHTML = rows.map((r) => `
        <tr>
            <td class="c-no" style="color:#94a3b8;font-size:.75rem;">${r.peringkat}</td>
            <td><span class="nm">${esc(r.nama)}</span><span class="nsn">${esc(r.nisn)||'—'}</span></td>
            <td style="font-size:.75rem;color:#475569;">${esc(SHORT[r.jurusan]||r.jurusan)}</td>
            <td class="c-val val-rp">${fmt(r.nilai_raport)}</td>
            <td class="c-val val-tk">${fmt(r.nilai_tka)}</td>
            <td class="c-val val-na">${fmt(r.nilai_akhir)}</td>
            <td style="text-align:center;">${statusBadge(r.status)}</td>
        </tr>`).join('');
}

document.getElementById('searchInput').addEventListener('input', doSearch);
document.getElementById('gelFilter').addEventListener('change', doSearch);
document.getElementById('jurFilter').addEventListener('change', doSearch);

// Load semua data saat pertama buka
fetchData();

// Auto-refresh setiap 5 detik
setInterval(fetchData, 5000);
</script>
</body>
</html>
