<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

// AJAX refresh
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    $rows = [];
    try {
        $stmt = $conn->query("
            SELECT nama, nisn, jurusan, nilai_raport, nilai_tka, nilai_akhir, gelombang, is_pinned
            FROM pendaftar WHERE status = 'terima'
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
    echo json_encode(['groups' => $result, 'time' => date('H:i:s'), 'date' => date('d F Y')]);
    exit;
}

// SSR awal
$groups_init = [];
try {
    $stmt = $conn->query("
        SELECT nama, nisn, jurusan, nilai_raport, nilai_tka, nilai_akhir, gelombang, is_pinned
        FROM pendaftar WHERE status = 'terima'
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
:root {
    --bg:       #060818;
    --bg2:      #0d0b26;
    --bg3:      #121030;
    --border:   rgba(99,102,241,.22);
    --accent:   #6366f1;
    --accent2:  #a5b4fc;
    --green:    #6ee7b7;
    --cyan:     #a5f3fc;
    --gold:     #fbbf24;
}
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; overflow:hidden; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: var(--bg);
    color: #e2e8f0;
    display: flex;
    flex-direction: column;
}

/* ── TOPBAR ── */
.topbar {
    background: linear-gradient(135deg, #0c0a2e, #1a1654);
    padding: 0 28px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 2px solid var(--border);
    flex-shrink: 0;
    gap: 12px;
}
.school { display:flex; align-items:center; gap:10px; }
.logo-circle {
    width:38px; height:38px; border-radius:50%;
    background: linear-gradient(135deg,#6366f1,#8b5cf6);
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; font-weight:900; color:#fff; flex-shrink:0;
}
.school-name { font-size:1rem; font-weight:700; color:#e0e7ff; line-height:1.15; }
.school-name small { display:block; font-size:.6rem; opacity:.5; letter-spacing:.8px; text-transform:uppercase; }
.center-badge {
    display:flex; align-items:center; gap:8px;
    font-size:.9rem; font-weight:600; color:var(--accent2);
}
.center-badge i { color:var(--gold); font-size:1.1rem; }
.clock { font-size:2rem; font-weight:900; color:var(--cyan); font-variant-numeric:tabular-nums; line-height:1; }
.date-str { font-size:.62rem; opacity:.45; text-align:right; margin-top:2px; }

/* ── GRID AREA ── */
.grid-area {
    flex: 1;
    display: flex;
    gap: 1px;
    background: var(--border); /* gap color */
    overflow: hidden;
    min-height: 0;
}

/* setiap kolom jurusan */
.jur-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: var(--bg);
    overflow: hidden;
    min-width: 0;
}

.jur-col-header {
    padding: 8px 12px;
    background: linear-gradient(135deg, rgba(99,102,241,.25), rgba(99,102,241,.08));
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}
.jur-short {
    font-size: .95rem; font-weight: 800; color: var(--accent2);
    background: rgba(99,102,241,.2); border: 1px solid rgba(99,102,241,.35);
    padding: 2px 10px; border-radius: 6px;
}
.jur-count {
    margin-left: auto;
    font-size: .65rem; font-weight: 600;
    background: rgba(16,185,129,.15); color: #6ee7b7;
    border: 1px solid rgba(16,185,129,.25); padding: 1px 7px; border-radius: 20px;
}

/* ── TABLE HEADER (sticky) ── */
.col-table-wrap {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: rgba(99,102,241,.25) transparent;
}
.col-table-wrap::-webkit-scrollbar { width: 3px; }
.col-table-wrap::-webkit-scrollbar-thumb { background: rgba(99,102,241,.3); border-radius:4px; }

table.rt {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
table.rt thead th {
    position: sticky; top: 0; z-index: 2;
    background: #0f0e2a;
    font-size: .58rem; text-transform: uppercase; letter-spacing: 1.2px;
    color: #6366f1; font-weight: 600;
    padding: 6px 8px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}
table.rt thead th.col-no   { width: 34px; text-align:center; }

table.rt thead th.col-val  { width: 64px; text-align:right; }

table.rt tbody tr {
    border-bottom: 1px solid rgba(255,255,255,.04);
    transition: background .12s;
}
table.rt tbody tr:hover { background: rgba(99,102,241,.08); }
table.rt tbody tr.pinned { background: rgba(251,191,36,.04); }

table.rt tbody td {
    padding: 7px 8px;
    font-size: .78rem;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
table.rt tbody td.col-no { text-align:center; padding: 5px 4px; }
table.rt tbody td.col-val { text-align:right; }

/* rank circle */
.rk {
    display:inline-flex; align-items:center; justify-content:center;
    width:26px; height:26px; border-radius:50%;
    font-weight:800; font-size:.75rem;
    background: rgba(99,102,241,.15); color: var(--accent2);
    border: 1px solid rgba(99,102,241,.25);
}
.rk.g  { background:linear-gradient(135deg,#b45309,#fbbf24); color:#fff; border-color:#fbbf24; }
.rk.s  { background:linear-gradient(135deg,#475569,#94a3b8); color:#fff; border-color:#94a3b8; }
.rk.br { background:linear-gradient(135deg,#7c2d12,#ea580c); color:#fff; border-color:#ea580c; }

.nm  { font-weight:600; color:#e2e8f0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:block; }
.nsn { font-size:.62rem; color:#475569; font-variant-numeric:tabular-nums; }
.pin-badge { font-size:.55rem; background:rgba(251,191,36,.15); color:var(--gold); border:1px solid rgba(251,191,36,.3); padding:0 4px; border-radius:3px; margin-left:4px; vertical-align:middle; }
.va  { font-weight:700; font-variant-numeric:tabular-nums; }
.va.na { color:var(--green); font-size:.88rem; }
.va.rp { color:#e2e8f0; }
.va.tk { color:#93c5fd; }
.glm-b {
    font-size:.58rem; padding:1px 5px; border-radius:3px; font-weight:700;
    background:rgba(99,102,241,.2); color:var(--accent2); border:1px solid rgba(99,102,241,.3);
}
.glm-b.g2 { background:rgba(245,158,11,.12); color:#fcd34d; border-color:rgba(245,158,11,.3); }

/* empty */
.empty-col {
    flex:1; display:flex; align-items:center; justify-content:center;
    font-size:.8rem; opacity:.25; flex-direction:column; gap:6px;
}

/* ── TICKER ── */
.ticker {
    height: 28px; line-height: 28px;
    background: rgba(99,102,241,.1);
    border-top: 1px solid var(--border);
    overflow: hidden; flex-shrink: 0;
}
.ticker-inner {
    display:inline-block; white-space:nowrap;
    animation: marquee 50s linear infinite;
    font-size: .72rem; color: var(--accent2); padding-left:100%;
}
@keyframes marquee { 0%{transform:translateX(0)} 100%{transform:translateX(-100%)} }

/* ── RESPONSIVE: 1 kolom lebar jika hanya 1 jurusan ── */
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="school">
        <div class="logo-circle">L</div>
        <div class="school-name">
            SMKS Laboratorium Jakarta
            <small>Sistem Penerimaan Peserta Didik Baru</small>
        </div>
    </div>
    <div class="center-badge">
        <i class="bi bi-trophy-fill"></i>
        Peringkat Siswa Diterima
    </div>
    <div>
        <div class="clock" id="clock">--:--:--</div>
        <div class="date-str" id="dateStr">—</div>
    </div>
</div>

<!-- GRID SEMUA JURUSAN -->
<div class="grid-area" id="gridArea">
    <!-- diisi JS -->
</div>

<!-- TICKER -->
<div class="ticker"><span class="ticker-inner" id="tickerText">&nbsp;</span></div>

<script>
let groups = <?= json_encode($groups_init, JSON_UNESCAPED_UNICODE) ?>;

// Clock
(function tick() {
    document.getElementById('clock').textContent =
        new Date().toLocaleTimeString('id-ID',{hour12:false});
    setTimeout(tick, 1000);
})();

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
            const glm   = `<span class="glm-b ${s.gelombang==2?'g2':''}">G${s.gelombang}</span>`;
            return `<tr class="${s.is_pinned==1?'pinned':''}">
                <td class="col-no"><span class="rk ${rkCls}">${s.peringkat}</span></td>
                <td>
                    <span class="nm">${esc(s.nama)}${pin}</span>
                    <span class="nsn">${esc(s.nisn)||'—'}</span>
                </td>
                <td class="col-val"><span class="va rp">${fmt(s.nilai_raport)}</span></td>
                <td class="col-val"><span class="va tk">${fmt(s.nilai_tka)}</span></td>
                <td class="col-val"><span class="va na">${fmt(s.nilai_akhir)}</span></td>
                <td class="col-val">${glm}</td>
            </tr>`;
        }).join('');

        const empty = g.students.length === 0
            ? `<div class="empty-col"><i class="bi bi-person-dash"></i><span>Belum ada</span></div>`
            : `<div class="col-table-wrap">
                <table class="rt">
                    <thead><tr>
                        <th class="col-no">#</th>
                        <th class="col-nama">Nama / NISN</th>
                        <th class="col-val">Raport</th>
                        <th class="col-val">TKA</th>
                        <th class="col-val">Akhir</th>
                        <th class="col-val">Glm</th>
                    </tr></thead>
                    <tbody>${rows}</tbody>
                </table>
               </div>`;

        return `<div class="jur-col">
            <div class="jur-col-header">
                <span class="jur-short">${esc(g.short)}</span>
                <span class="jur-count">${g.students.length} diterima</span>
            </div>
            ${empty}
        </div>`;
    }).join('');

    // Ticker
    const total = groups.reduce((s,g)=>s+g.students.length,0);
    const parts = groups.map(g=>`${g.short}: ${g.students.length}`).join('  ·  ');
    document.getElementById('tickerText').textContent =
        `✦  Total diterima: ${total} siswa  ✦  ${parts}  ✦`;
}

// Auto-refresh
function fetchData(){
    fetch('ranking_display.php?json=1')
        .then(r=>r.json())
        .then(d=>{
            groups = d.groups;
            if(d.date) document.getElementById('dateStr').textContent = d.date;
            render();
        })
        .catch(()=>{});
}

render();
fetchData();
setInterval(fetchData, 5000);
</script>
</body>
</html>
