<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

// Baca setting display dari DB — harus di atas semua handler agar $glm_where tersedia
$rd_speed     = 0.7;
$rd_pause     = 2500;
$rd_glm       = 0; // 0=semua, 1=G1 saja, 2=G2 saja
$rd_published = 0; // 0=sementara, 1=final/resmi
try {
    $st = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('ranking_scroll_speed','ranking_pause_ms','ranking_display_gelombang','ranking_published')");
    foreach ($st as $r) {
        if ($r['setting_key'] === 'ranking_scroll_speed')      $rd_speed     = (float)$r['setting_value'];
        if ($r['setting_key'] === 'ranking_pause_ms')          $rd_pause     = (int)$r['setting_value'];
        if ($r['setting_key'] === 'ranking_display_gelombang') $rd_glm       = (int)$r['setting_value'];
        if ($r['setting_key'] === 'ranking_published')         $rd_published = (int)$r['setting_value'];
    }
} catch (Throwable $e) {}
$glm_where = $rd_glm > 0 ? " AND gelombang = $rd_glm" : "";

// AJAX refresh
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    $rows = [];
    try {
        $stmt = $conn->query("
            SELECT nama, nisn, jurusan, nilai_raport, nilai_tka, nilai_akhir, gelombang, is_pinned
            FROM pendaftar WHERE status = 'terima' AND is_undur_diri=0{$glm_where}
            ORDER BY jurusan ASC, nilai_akhir DESC, usia DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    $grouped = [];
    foreach (JURUSAN_LIST as $j) $grouped[$j] = [];
    foreach ($rows as $r) { if (isset($grouped[$r['jurusan']])) $grouped[$r['jurusan']][] = $r; }

    $result = [];
    foreach ($grouped as $jur => $list) {
        $rank = 1; foreach ($list as &$item) { $item['peringkat'] = $rank++; } unset($item);
        $result[] = ['jurusan' => $jur, 'short' => JURUSAN_SHORT[$jur] ?? $jur, 'students' => $list];
    }
    echo json_encode(['groups' => $result, 'time' => date('H:i:s'), 'date' => date('d F Y'), 'published' => $rd_published]);
    exit;
}

// SSR awal
$groups_init = [];
try {
    $stmt = $conn->query("
        SELECT nama, nisn, jurusan, nilai_raport, nilai_tka, nilai_akhir, gelombang, is_pinned
        FROM pendaftar WHERE status = 'terima' AND is_undur_diri=0{$glm_where}
        ORDER BY jurusan ASC, nilai_akhir DESC, usia DESC
    ");
    $tmp = []; foreach (JURUSAN_LIST as $j) $tmp[$j] = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { if (isset($tmp[$r['jurusan']])) $tmp[$r['jurusan']][] = $r; }
    foreach ($tmp as $jur => $list) {
        if (!$list) continue;
        $rank = 1; foreach ($list as &$item) { $item['peringkat'] = $rank++; } unset($item);
        $groups_init[] = ['jurusan' => $jur, 'short' => JURUSAN_SHORT[$jur] ?? $jur, 'students' => $list];
    }
} catch (Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Peringkat Siswa Diterima — SMKS Laboratorium Jakarta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; overflow:hidden; }
body {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    background: #eef2f7;
    color: #1e293b;
    display: flex;
    flex-direction: column;
}

/* ── TOP TITLE ── */
.top-title {
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    color: #fff;
    text-align: center;
    font-size: 1.35rem;
    font-weight: 800;
    padding: 12px 20px;
    letter-spacing: .3px;
    flex-shrink: 0;
}

/* ── GRID AREA ── */
.grid-area {
    flex: 1;
    display: flex;
    gap: 10px;
    padding: 10px;
    background: #dde3ed;
    overflow: hidden;
    min-height: 0;
}

/* setiap kolom jurusan */
.jur-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    min-width: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,.10);
}

.jur-col-header {
    padding: 10px 14px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.jur-name {
    font-size: 1rem; font-weight: 800;
    color: #ffffff;
    letter-spacing: .3px;
    text-align: center;
}

/* ── TABLE ── */
.col-table-wrap {
    flex: 1;
    overflow: hidden;          /* STATIS — tidak scroll, semua baris tampil sekaligus */
    font-size: 16px;           /* anchor; auto-fit JS memperkecil bila 25 baris belum muat */
}

table.rt {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
table.rt thead th {
    position: sticky; top: 0; z-index: 2;
    background: #f1f5f9;
    font-size: .58em; text-transform: uppercase; letter-spacing: 1px;
    color: #64748b; font-weight: 700;
    padding: .35em .6em;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}
table.rt thead th.col-no  { width: 9%;  text-align:center; }
table.rt thead th.col-val { width: 16%; text-align:right; }

table.rt tbody tr {
    border-bottom: 1px solid #f1f5f9;
}
table.rt tbody tr:nth-child(even) { background: #f8fafc; }
table.rt tbody tr.pinned { background: #fffbeb; }

table.rt tbody td {
    padding: .2em .55em;
    font-size: .85em;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
table.rt tbody td.col-no  { text-align:center; padding: .2em .15em; }
table.rt tbody td.col-val { text-align:right; }

/* rank circle */
.rk {
    display:inline-flex; align-items:center; justify-content:center;
    width:1.7em; height:1.7em; border-radius:50%;
    font-weight:800; font-size:.82em;
    background: #e0e7ff; color: #3730a3;
}
.rk.g  { background:linear-gradient(135deg,#d97706,#fbbf24); color:#fff; }
.rk.s  { background:linear-gradient(135deg,#64748b,#94a3b8); color:#fff; }
.rk.br { background:linear-gradient(135deg,#b45309,#f97316); color:#fff; }

.nm  { font-weight:600; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
.nsn { font-size:.72em; color:#94a3b8; font-variant-numeric:tabular-nums; }
.pin-badge {
    font-size:.6em; background:#fef3c7; color:#b45309;
    border:1px solid #fde68a; padding:0 4px; border-radius:3px;
    margin-left:4px; vertical-align:middle;
}
.va { font-weight:700; font-variant-numeric:tabular-nums; }
.va.na { color:#059669; font-size:1.05em; }
.va.rp { color:#374151; }
.va.tk { color:#2563eb; }

/* empty */
.empty-col {
    flex:1; display:flex; align-items:center; justify-content:center;
    font-size:.8rem; color:#94a3b8; flex-direction:column; gap:6px;
}
</style>
</head>
<body>

<div class="top-title" id="pageTitle">
    <?= $rd_published
        ? 'Hasil Penerimaan Siswa Baru SMKS Laboratorium Jakarta'
        : 'Peringkat Sementara Sistem Penerimaan Siswa Baru SMKS Laboratorium Jakarta' ?>
</div>

<!-- GRID SEMUA JURUSAN -->
<div class="grid-area" id="gridArea">
    <!-- diisi JS -->
</div>

<script>
let groups = <?= json_encode($groups_init, JSON_UNESCAPED_UNICODE) ?>;

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmt(v){ return (v===null||v===undefined||v==='')?'—':parseFloat(v).toFixed(2); }

function render() {
    const grid = document.getElementById('gridArea');
    if (!groups.length) {
        grid.innerHTML = '<div class="empty-col" style="background:var(--bg);width:100%;"><i class="bi bi-inbox" style="font-size:2rem;"></i><span>Belum ada siswa yang diterima</span></div>';
        return;
    }
    grid.innerHTML = groups.map(g => {
        const rows = g.students.map(s => {
            const rkCls = s.peringkat===1?'g':s.peringkat===2?'s':s.peringkat===3?'br':'';
            const pin   = s.is_pinned==1 ? '<span class="pin-badge">PIN</span>' : '';
            return `<tr class="${s.is_pinned==1?'pinned':''}">
                <td class="col-no"><span class="rk ${rkCls}">${s.peringkat}</span></td>
                <td><span class="nm">${esc(s.nama)}${pin}</span><span class="nsn">${esc(s.nisn)||'—'}</span></td>
                <td class="col-val"><span class="va rp">${fmt(s.nilai_raport)}</span></td>
                <td class="col-val"><span class="va tk">${fmt(s.nilai_tka)}</span></td>
                <td class="col-val"><span class="va na">${fmt(s.nilai_akhir)}</span></td>
            </tr>`;
        }).join('');

        const body = g.students.length === 0
            ? `<div class="empty-col"><i class="bi bi-person-dash"></i><span>Belum ada</span></div>`
            : `<div class="col-table-wrap">
                <table class="rt">
                    <thead><tr>
                        <th class="col-no">#</th>
                        <th>Nama / NISN</th>
                        <th class="col-val">Raport</th>
                        <th class="col-val">TKA</th>
                        <th class="col-val">Akhir</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>
               </div>`;

        return `<div class="jur-col">
            <div class="jur-col-header">
                <span class="jur-name">${esc(g.jurusan)}</span>
            </div>${body}
        </div>`;
    }).join('');

    fitColumns();   // sesuaikan ukuran setelah konten dirender
}

// ── Auto-fit: perkecil font tiap kolom sampai semua baris muat (TANPA scroll) ──
// Banyak siswa → font otomatis mengecil agar 25 baris muat penuh; sedikit siswa
// → tetap maksimal (18px). Tidak ada lagi gerak naik-turun.
function fitColumns() {
    document.querySelectorAll('.col-table-wrap').forEach(w => {
        let fs = 18;                       // px awal (maksimal)
        w.style.fontSize = fs + 'px';
        let guard = 0;
        while (w.scrollHeight > w.clientHeight + 1 && fs > 6 && guard++ < 60) {
            fs -= 0.5;
            w.style.fontSize = fs + 'px';
        }
    });
}
window.addEventListener('resize', fitColumns);

// ── Auto-refresh data ──────────────────────────────────────────────────────
function fetchData(){
    fetch('ranking_display.php?json=1')
        .then(r=>r.json())
        .then(d=>{
            groups = d.groups;
            render();
            const titleEl = document.getElementById('pageTitle');
            if (titleEl) {
                titleEl.textContent = d.published
                    ? 'Hasil Penerimaan Siswa Baru SMKS Laboratorium Jakarta'
                    : 'Peringkat Sementara Sistem Penerimaan Siswa Baru SMKS Laboratorium Jakarta';
            }
        })
        .catch(()=>{});
}

render();
fetchData();
setInterval(fetchData, 5000);
</script>
</body>
</html>
