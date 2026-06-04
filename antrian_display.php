<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

$today = date('Y-m-d');

// Stats pendaftar per jurusan (JURUSAN_LIST = array nama panjang, JURUSAN_SHORT = nama panjang => kode)
$stats_jur = [];
foreach (JURUSAN_LIST as $nama_lengkap) {
    $kode = JURUSAN_SHORT[$nama_lengkap] ?? $nama_lengkap;
    $s = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE jurusan=?");
    $s->execute([$nama_lengkap]);
    $stats_jur[$kode] = (int)$s->fetchColumn();
}
$stats_total = array_sum($stats_jur);

// Ambil data antrian yang sedang dipanggil
$stmt = $conn->prepare("
    SELECT a.nomor, a.fase, a.dipanggil_at,
           m.id AS meja_id, m.nomor_meja, m.nama AS nama_meja, m.fase AS meja_fase
    FROM antrian a
    JOIN meja m ON m.id = a.meja_id
    WHERE a.tanggal = ? AND a.status = 'dipanggil'
    ORDER BY a.dipanggil_at DESC
    LIMIT 20
");
$stmt->execute([$today]);
$antrian_aktif = $stmt->fetchAll();

// Ambil meja aktif
$meja_list = $conn->query("SELECT * FROM meja WHERE is_active=1 ORDER BY nomor_meja")->fetchAll();

$latest = $antrian_aktif[0] ?? null;

$meja_colors = ['#7c3aed','#059669','#dc2626','#d97706','#2563eb','#0891b2','#c026d3','#65a30d'];
$meja_color_map = [];
foreach ($meja_list as $i => $m) {
    $meja_color_map[$m['id']] = $meja_colors[$i % count($meja_colors)];
}

// Nomor menunggu berikutnya — sertakan info meja
$next_stmt = $conn->prepare("
    SELECT a.nomor, a.fase, a.meja_id, m.nomor_meja, m.nama AS nama_meja
    FROM antrian a JOIN meja m ON m.id=a.meja_id
    WHERE a.tanggal=? AND a.fase=1 AND a.status='menunggu'
    ORDER BY a.nomor ASC LIMIT 5");
$next_stmt->execute([$today]);
$next_f1 = $next_stmt->fetchAll();

$next_stmt2 = $conn->prepare("
    SELECT a.nomor, a.fase, a.meja_id, m.nomor_meja, m.nama AS nama_meja
    FROM antrian a JOIN meja m ON m.id=a.meja_id
    WHERE a.tanggal=? AND a.fase=2 AND a.status='menunggu'
    ORDER BY a.nomor ASC LIMIT 3");
$next_stmt2->execute([$today]);
$next_f2 = $next_stmt2->fetchAll();

// AJAX refresh
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'latest'  => $antrian_aktif[0] ?? null,
        'list'    => array_slice($antrian_aktif, 0, 10),
        'colors'  => $meja_color_map,
        'meja'    => $meja_list,
        'next_f1'     => $next_f1,
        'next_f2'     => $next_f2,
        'stats_jur'   => $stats_jur,
        'stats_total' => $stats_total,
        'time'        => date('H:i:s'),
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Display Antrian — SPMB SMK Lab Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            background: #0f0c29;
            color: #fff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid rgba(124,58,237,.4);
            flex-shrink: 0;
        }
        .topbar .school-name { font-size: 1.3rem; font-weight: 700; letter-spacing: .3px; }
        .topbar .school-name small {
            display: block; font-size: .75rem; font-weight: 400;
            opacity: .6; letter-spacing: .5px; text-transform: uppercase;
        }
        .topbar .clock { font-size: 2.6rem; font-weight: 900; font-variant-numeric: tabular-nums; color: #a5f3fc; }
        .topbar .date-str { font-size: .8rem; opacity: .55; text-align: right; }

        /* ── MAIN AREA ── */
        .main-area {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            overflow: hidden;
        }

        /* ── LEFT: CURRENT NUMBER ── */
        .current-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px;
            background: linear-gradient(135deg, #1e1b4b 0%, #0f0c29 100%);
            position: relative;
        }
        .current-label { font-size: .85rem; text-transform: uppercase; letter-spacing: 3px; opacity: .5; margin-bottom: 8px; }
        .current-prefix {
            font-size: 3.2rem; font-weight: 900; letter-spacing: 10px;
            color: #a5f3fc; opacity: .7; margin-bottom: -8px;
            text-transform: uppercase;
        }
        .current-number {
            font-size: 13rem; font-weight: 900; line-height: 1; color: #fff;
            text-shadow: 0 0 80px rgba(124,58,237,.8), 0 0 160px rgba(124,58,237,.4);
            font-variant-numeric: tabular-nums;
        }
        .current-number.flash { animation: numberFlash .6s ease; }
        @keyframes numberFlash {
            0%   { transform: scale(1);    opacity: 1; }
            25%  { transform: scale(1.08); opacity: .7; color: #fde68a; }
            100% { transform: scale(1);    opacity: 1; }
        }
        .current-desk {
            margin-top: 20px; font-size: 1.4rem; font-weight: 600;
            padding: 10px 32px; border-radius: 50px;
            background: rgba(124,58,237,.25); border: 1px solid rgba(124,58,237,.5); color: #c4b5fd;
        }
        .current-fase { margin-top: 10px; font-size: .8rem; opacity: .55; text-transform: uppercase; letter-spacing: 1.5px; }
        .no-call { font-size: 1.4rem; opacity: .3; font-style: italic; }
        .pulse-ring {
            position: absolute; width: 400px; height: 400px; border-radius: 50%;
            border: 3px solid rgba(124,58,237,.3); animation: pulseRing 2.5s ease-out infinite;
        }
        .pulse-ring:nth-child(2) { animation-delay: .8s; }
        .pulse-ring:nth-child(3) { animation-delay: 1.6s; }
        @keyframes pulseRing {
            0%   { transform: scale(.9); opacity: .6; }
            100% { transform: scale(1.6); opacity: 0; }
        }

        /* ── RIGHT PANEL ── */
        .right-panel {
            background: #13102e;
            border-left: 1px solid rgba(255,255,255,.07);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── SELANJUTNYA — baris vertikal ── */
        .next-section {
            padding: 10px 12px 8px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }
        .section-label {
            font-size: .62rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: .38;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .next-cards { display: flex; flex-direction: column; gap: 5px; }
        .next-card {
            display: flex; align-items: center; gap: 10px;
            border-radius: 10px; padding: 7px 10px;
            border: 1px solid; overflow: hidden;
        }
        .next-card.f1 { background: rgba(5,150,105,.12); border-color: rgba(5,150,105,.28); }
        .next-card.f2 { background: rgba(124,58,237,.12); border-color: rgba(124,58,237,.28); }
        .next-card-num {
            font-size: 1.35rem; font-weight: 900;
            font-variant-numeric: tabular-nums; white-space: nowrap; flex-shrink: 0;
        }
        .next-card.f1 .next-card-num { color: #34d399; }
        .next-card.f2 .next-card-num { color: #c4b5fd; }
        .next-card-info { flex: 1; min-width: 0; }
        .next-card-meja {
            font-size: .8rem; font-weight: 600; color: #fff;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .next-card-fase {
            display: inline-block; font-size: .6rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .4px;
            padding: 2px 6px; border-radius: 4px; margin-top: 2px;
        }
        .next-card.f1 .next-card-fase { background: rgba(5,150,105,.25); color: #6ee7b7; }
        .next-card.f2 .next-card-fase { background: rgba(124,58,237,.25); color: #c4b5fd; }
        .next-empty { font-size: .8rem; opacity: .3; padding: 4px 0; }

        /* ── STATS JURUSAN ── */
        .stats-section {
            padding: 10px 16px 12px;
            border-bottom: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-top: 8px; }
        .stat-jur-item {
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(255,255,255,.06); border-radius: 10px; padding: 10px 14px;
            gap: 8px;
        }
        .stat-jur-kode { font-size: 1rem; font-weight: 700; color: #a5f3fc; letter-spacing: .5px; }
        .stat-jur-count { font-size: 2rem; font-weight: 900; color: #fff; line-height: 1; }
        .stat-total-row {
            margin-top: 8px; display: flex; justify-content: space-between; align-items: center;
            background: rgba(124,58,237,.22); border-radius: 10px; padding: 10px 14px;
        }
        .stat-total-label { font-size: .85rem; color: #c4b5fd; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .stat-total-num   { font-size: 2.2rem; font-weight: 900; color: #c4b5fd; line-height: 1; }

        /* ── RIWAYAT ── */
        .recent-header {
            padding: 12px 14px 8px;
            font-size: .62rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: .38;
            border-bottom: 1px solid rgba(255,255,255,.06);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .recent-list { flex: 1; overflow-y: auto; padding: 6px 10px; }
        .recent-list::-webkit-scrollbar { width: 4px; }
        .recent-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 4px; }

        .recent-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px; border-radius: 10px; margin-bottom: 6px;
        }
        .recent-item.first-item { background: rgba(124,58,237,.2); border: 1px solid rgba(124,58,237,.3); }
        .recent-num { font-size: 1.7rem; font-weight: 900; line-height: 1; min-width: 58px; text-align: center; font-variant-numeric: tabular-nums; }
        .recent-info { flex: 1; }
        .recent-desk { font-size: 1rem; font-weight: 600; }
        .recent-time { font-size: .8rem; opacity: .45; margin-top: 3px; }
        .fase-badge {
            font-size: .6rem; padding: 2px 6px; border-radius: 4px;
            font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
        }
        .fase-1 { background: rgba(5,150,105,.25); color: #6ee7b7; }
        .fase-2 { background: rgba(124,58,237,.25); color: #c4b5fd; }

        /* ── LOKET FOOTER ── */
        .loket-footer {
            background: #0d0b22;
            border-top: 2px solid rgba(124,58,237,.3);
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
            overflow-x: auto;
        }
        .loket-footer::-webkit-scrollbar { height: 3px; }
        .loket-footer::-webkit-scrollbar-thumb { background: rgba(124,58,237,.4); border-radius: 4px; }
        .loket-footer-label {
            font-size: .55rem; text-transform: uppercase; letter-spacing: 1.5px;
            opacity: .35; white-space: nowrap; padding-right: 8px;
            border-right: 1px solid rgba(255,255,255,.1); margin-right: 4px;
            flex-shrink: 0;
        }
        .meja-card {
            flex: 1; min-width: 130px;
            border-radius: 8px; padding: 6px 10px;
            font-size: .78rem; border: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; gap: 8px;
        }
        .meja-card-left { flex-shrink: 0; }
        .meja-card .meja-num { font-weight: 700; font-size: .85rem; white-space: nowrap; }
        .meja-card .meja-serving {
            font-size: 1.3rem; font-weight: 900;
            font-variant-numeric: tabular-nums; line-height: 1;
        }
        .meja-card .meja-idle { font-size: .75rem; opacity: .3; }

        /* ── TICKER ── */
        .ticker {
            background: rgba(255,255,255,.04); border-top: 1px solid rgba(255,255,255,.05);
            padding: 8px 24px; font-size: .95rem; opacity: .45;
            white-space: nowrap; overflow: hidden; flex-shrink: 0;
        }

        /* ── BEEP INDICATOR ── */
        #beep-indicator {
            position: fixed; top: 50%; left: 50%;
            transform: translate(-50%,-50%) scale(0);
            background: rgba(253,230,138,.95); color: #78350f;
            font-size: 1.2rem; font-weight: 700; padding: 16px 40px;
            border-radius: 16px; opacity: 0; z-index: 999; pointer-events: none;
            transition: all .25s;
        }
        #beep-indicator.show { transform: translate(-50%,-50%) scale(1); opacity: 1; }

        /* ── TTS OVERLAY ── */
        #tts-overlay {
            position: fixed; inset: 0; z-index: 9999;
            background: rgba(15,12,41,.92);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 20px; cursor: pointer;
        }
        #tts-overlay .overlay-icon { font-size: 5rem; color: #a5f3fc; }
        #tts-overlay h2 { font-size: 1.6rem; font-weight: 700; color: #fff; }
        #tts-overlay p  { font-size: .95rem; opacity: .6; color: #fff; }
        #tts-overlay .click-hint {
            background: rgba(124,58,237,.35); border: 1px solid rgba(124,58,237,.6);
            color: #c4b5fd; padding: 12px 36px; border-radius: 50px;
            font-size: 1rem; font-weight: 600; margin-top: 10px;
            animation: pulse-btn 1.8s ease-in-out infinite;
        }
        @keyframes pulse-btn {
            0%,100% { box-shadow: 0 0 0 0 rgba(124,58,237,.5); }
            50%      { box-shadow: 0 0 0 12px rgba(124,58,237,.0); }
        }

        /* ── TTS TOGGLE BUTTON ── */
        #tts-toggle {
            position: fixed; bottom: 48px; right: 16px; z-index: 990;
            background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
            color: #fff; padding: 10px 14px; border-radius: 50px;
            font-size: .82rem; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 8px;
            backdrop-filter: blur(8px); transition: all .2s;
        }
        #tts-toggle:hover { background: rgba(255,255,255,.18); }
        #tts-toggle.muted { opacity: .45; }
        #tts-toggle i { font-size: 1.1rem; }

        /* ── VOICE PICKER ── */
        #voice-picker-btn {
            position: fixed; bottom: 96px; right: 16px; z-index: 990;
            background: rgba(124,58,237,.2); border: 1px solid rgba(124,58,237,.45);
            color: #c4b5fd; padding: 9px 14px; border-radius: 50px;
            font-size: .78rem; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 8px;
            backdrop-filter: blur(8px); transition: all .2s;
        }
        #voice-picker-btn:hover { background: rgba(124,58,237,.35); }
        #voice-picker-panel {
            position: fixed; bottom: 140px; right: 16px; z-index: 991;
            background: #1e1b4b; border: 1px solid rgba(124,58,237,.4);
            border-radius: 14px; width: 300px; max-height: 360px;
            overflow-y: auto; display: none;
            box-shadow: 0 12px 40px rgba(0,0,0,.5);
        }
        #voice-picker-panel.show { display: block; }
        #voice-picker-panel::-webkit-scrollbar { width: 4px; }
        #voice-picker-panel::-webkit-scrollbar-thumb { background: rgba(124,58,237,.4); border-radius: 4px; }
        .vp-header { padding: 12px 14px 8px; border-bottom: 1px solid rgba(255,255,255,.07);
            font-size: .8rem; font-weight: 700; color: #c4b5fd; }
        .vp-group-label { font-size: .6rem; text-transform: uppercase; letter-spacing: 1.5px;
            opacity: .4; padding: 10px 14px 4px; font-weight: 700; }
        .vp-item { padding: 8px 12px; cursor: pointer; border-radius: 8px;
            margin: 2px 6px; transition: background .15s; }
        .vp-item:hover { background: rgba(255,255,255,.08); }
        .vp-item.active { background: rgba(124,58,237,.35); border: 1px solid rgba(124,58,237,.5); }
        .vp-name { font-size: .82rem; font-weight: 500; color: #fff; }
        .vp-meta { font-size: .68rem; opacity: .45; margin-top: 1px; }
        .vp-meta .neural-tag { background: rgba(16,185,129,.2); color: #6ee7b7;
            border-radius: 4px; padding: 0 5px; font-size: .62rem; font-weight: 700; margin-left: 4px; }
        .vp-test { padding: 10px 14px; border-top: 1px solid rgba(255,255,255,.07); }
        .vp-test button { width: 100%; background: rgba(124,58,237,.25); border: 1px solid rgba(124,58,237,.4);
            color: #c4b5fd; padding: 7px 12px; border-radius: 8px; font-size: .8rem;
            font-weight: 600; cursor: pointer; transition: background .15s; }
        .vp-test button:hover { background: rgba(124,58,237,.4); }
    </style>
</head>
<body>

<!-- TTS Unlock Overlay -->
<div id="tts-overlay" onclick="unlockAudio()">
    <div class="overlay-icon"><i class="bi bi-volume-up-fill"></i></div>
    <h2>Display Antrian</h2>
    <p>Klik di mana saja untuk mengaktifkan pengumuman suara</p>
    <div class="click-hint"><i class="bi bi-hand-index-thumb me-2"></i>Klik untuk Mulai</div>
</div>

<!-- TTS Toggle -->
<button id="tts-toggle" onclick="toggleTTS()" title="Matikan Suara">
    <i class="bi bi-volume-up-fill" id="tts-icon"></i>
    <span id="tts-label">Suara ON</span>
</button>

<!-- Voice Picker -->
<button id="voice-picker-btn" onclick="toggleVoicePicker()" title="Pilih Suara">
    <i class="bi bi-mic-fill"></i>
    <span id="voice-picker-label">Pilih Suara</span>
</button>
<div id="voice-picker-panel">
    <div class="vp-header"><i class="bi bi-mic-fill me-2"></i>Pilih Suara Pengumuman</div>
    <div id="voice-list"></div>
    <div class="vp-test">
        <button onclick="testCurrentVoice()"><i class="bi bi-play-fill me-1"></i>Tes Suara Terpilih</button>
    </div>
</div>

<div id="beep-indicator"><i class="bi bi-bell-fill me-2"></i>Nomor baru dipanggil!</div>

<!-- Topbar -->
<div class="topbar">
    <div>
        <div class="school-name">
            SMKS Laboratorium Jakarta
            <small>Sistem Antrian SPMB <?= date('Y') ?>/<?= date('Y')+1 ?></small>
        </div>
    </div>
    <div class="text-end">
        <div class="clock" id="clock">--:--:--</div>
        <div class="date-str" id="date-str"><?= date('l, d F Y') ?></div>
    </div>
</div>

<!-- Main -->
<div class="main-area">

    <!-- Left: Current Number -->
    <div class="current-panel" id="current-panel">
        <?php if ($latest): ?>
        <div class="pulse-ring"></div>
        <div class="pulse-ring"></div>
        <div class="pulse-ring"></div>
        <div class="current-label">Nomor Antrian</div>
        <div class="current-prefix">SSG</div>
        <div class="current-number" id="current-number"><?= str_pad($latest['nomor'], 3, '0', STR_PAD_LEFT) ?></div>
        <div class="current-desk" id="current-desk">
            <?= htmlspecialchars($latest['nama_meja'] ?: 'Loket ' . $latest['nomor_meja']) ?>
        </div>
        <div class="current-fase" id="current-fase">
            Fase <?= $latest['fase'] === 1 ? '1 — Cek Berkas' : '2 — Input Data' ?>
        </div>
        <?php else: ?>
        <div class="no-call"><i class="bi bi-hourglass-split me-2"></i>Menunggu antrian...</div>
        <?php endif; ?>
    </div>

    <!-- Right Panel -->
    <div class="right-panel">

        <!-- Selanjutnya -->
        <div class="next-section">
            <div class="section-label"><i class="bi bi-skip-forward-fill"></i> Selanjutnya — Siap Dipanggil</div>
            <div class="next-cards" id="next-cards">
            <?php
            $all_next = [...$next_f1, ...$next_f2];
            if (!empty($all_next)):
                foreach ($next_f1 as $n):
                    $ml = htmlspecialchars($n['nama_meja'] ?: 'Loket '.$n['nomor_meja']);
            ?>
                <div class="next-card f1">
                    <div class="next-card-num">SSG<?= str_pad($n['nomor'],3,'0',STR_PAD_LEFT) ?></div>
                    <div class="next-card-info">
                        <div class="next-card-meja"><?= $ml ?></div>
                        <div class="next-card-fase">Fase 1</div>
                    </div>
                </div>
            <?php endforeach; foreach ($next_f2 as $n):
                    $ml = htmlspecialchars($n['nama_meja'] ?: 'Loket '.$n['nomor_meja']); ?>
                <div class="next-card f2">
                    <div class="next-card-num">SSG<?= str_pad($n['nomor'],3,'0',STR_PAD_LEFT) ?></div>
                    <div class="next-card-info">
                        <div class="next-card-meja"><?= $ml ?></div>
                        <div class="next-card-fase">Fase 2</div>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <span class="next-empty">Semua antrian sudah selesai</span>
            <?php endif; ?>
            </div>
        </div>

        <!-- Stats Pendaftar per Jurusan -->
        <div class="stats-section">
            <div class="section-label"><i class="bi bi-bar-chart-fill"></i> Pendaftar per Jurusan</div>
            <div class="stats-grid" id="stats-grid">
            <?php foreach (JURUSAN_LIST as $kode => $nama): ?>
                <div class="stat-jur-item">
                    <span class="stat-jur-kode"><?= htmlspecialchars($kode) ?></span>
                    <span class="stat-jur-count"><?= $stats_jur[$kode] ?></span>
                </div>
            <?php endforeach; ?>
            </div>
            <div class="stat-total-row" id="stat-total-row">
                <span class="stat-total-label">Total Pendaftar</span>
                <span class="stat-total-num" id="stat-total"><?= $stats_total ?></span>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="recent-header"><i class="bi bi-clock-history"></i> Riwayat Dipanggil</div>
        <div class="recent-list" id="recent-list">
        <?php foreach ($antrian_aktif as $i => $a):
            $color = $meja_color_map[$a['meja_id']] ?? '#7c3aed';
            $label = $a['nama_meja'] ?: 'Loket ' . $a['nomor_meja'];
        ?>
        <div class="recent-item <?= $i===0?'first-item':'' ?>">
            <div class="recent-num" style="color:<?= $color ?>">SSG<?= str_pad($a['nomor'],3,'0',STR_PAD_LEFT) ?></div>
            <div class="recent-info">
                <div class="recent-desk"><?= htmlspecialchars($label) ?></div>
                <div class="recent-time">
                    <?= date('H:i', strtotime($a['dipanggil_at'])) ?>
                    &nbsp;<span class="fase-badge fase-<?= $a['fase'] ?>">F<?= $a['fase'] ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($antrian_aktif)): ?>
        <div style="opacity:.3;font-size:.85rem;text-align:center;margin-top:40px;">Belum ada antrian hari ini</div>
        <?php endif; ?>
        </div>

    </div>
</div>

<!-- Loket Footer -->
<div class="loket-footer">
    <div class="loket-footer-label"><i class="bi bi-grid me-1"></i>Status<br>Loket</div>
    <div id="meja-grid" style="display:flex;gap:8px;flex:1;flex-wrap:wrap;">
    <?php
    $meja_serving = [];
    foreach ($antrian_aktif as $a) {
        if (!isset($meja_serving[$a['meja_id']])) $meja_serving[$a['meja_id']] = $a;
    }
    foreach ($meja_list as $m):
        $color   = $meja_color_map[$m['id']] ?? '#7c3aed';
        $serving = $meja_serving[$m['id']] ?? null;
        $label   = $m['nama'] ?: 'Loket ' . $m['nomor_meja'];
    ?>
    <div class="meja-card" style="background:<?= $color ?>18;border-color:<?= $color ?>40;">
        <div class="meja-card-left">
            <?php if ($serving): ?>
            <div class="meja-serving" style="color:<?= $color ?>">SSG<?= str_pad($serving['nomor'],3,'0',STR_PAD_LEFT) ?></div>
            <?php else: ?>
            <div class="meja-idle">—</div>
            <?php endif; ?>
        </div>
        <div>
            <div class="meja-num" style="color:<?= $color ?>"><?= htmlspecialchars($label) ?></div>
            <span class="fase-badge fase-<?= $m['fase'] ?>">F<?= $m['fase'] ?></span>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- Ticker -->
<div class="ticker">
    <span>SPMB SMKS Laboratorium Jakarta <?= date('Y') ?>/<?= date('Y')+1 ?> &nbsp;•&nbsp;
    Harap menunggu dengan tertib &nbsp;•&nbsp;
    Siapkan berkas sebelum dipanggil &nbsp;•&nbsp;
    Fase 1: Cek Berkas &nbsp;•&nbsp; Fase 2: Input Data & Surat Tanda Daftar &nbsp;•&nbsp;
    Terima kasih atas kehadiran Anda &nbsp;•&nbsp;</span>
</div>

<script>
// ── Clock ──────────────────────────────────────────────────────────────────────
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent =
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
}
setInterval(updateClock, 1000);
updateClock();

// ── TTS (Text-to-Speech) ───────────────────────────────────────────────────────
let ttsEnabled  = true;
let ttsVoice    = null;
let ttsUnlocked = false;
const TTS_KEY   = 'ppdb_tts_voice';

// Kata kunci nama suara perempuan di berbagai OS
const FEMALE_KW = ['gadis','female','woman','zira','hazel','susan','linda',
                   'samantha','victoria','karen','moira','tessa','fiona'];
const isFemale  = v => FEMALE_KW.some(k => v.name.toLowerCase().includes(k));

function loadVoices() {
    const voices = speechSynthesis.getVoices();
    if (!voices.length) return;

    // Pulihkan pilihan tersimpan
    const saved = localStorage.getItem(TTS_KEY);
    if (saved) {
        const found = voices.find(v => v.name === saved);
        if (found) { ttsVoice = found; renderVoiceList(voices); return; }
    }

    // Auto-pilih: utamakan female id-ID neural → female id-ID → id-ID → fallback
    ttsVoice = voices.find(v => v.lang === 'id-ID' && isFemale(v) && !v.localService)
            || voices.find(v => v.lang === 'id-ID' && isFemale(v))
            || voices.find(v => v.lang === 'id-ID'  && !v.localService)
            || voices.find(v => v.lang === 'id-ID')
            || voices.find(v => v.lang.startsWith('id'))
            || null;

    renderVoiceList(voices);
}
speechSynthesis.onvoiceschanged = loadVoices;
loadVoices();

function renderVoiceList(voices) {
    const el = document.getElementById('voice-list');
    if (!el) return;

    const idVoices    = voices.filter(v => v.lang.startsWith('id'));
    const otherVoices = voices.filter(v => !v.lang.startsWith('id'));

    const makeItem = v => {
        const active  = ttsVoice && v.name === ttsVoice.name;
        const neural  = !v.localService ? '<span class="neural-tag">Neural</span>' : '';
        const femTag  = isFemale(v) ? ' · ♀' : '';
        return `<div class="vp-item ${active ? 'active' : ''}" onclick="selectVoice('${v.name.replace(/'/g,"\\'").replace(/"/g,'&quot;')}')">
            <div class="vp-name">${v.name}</div>
            <div class="vp-meta">${v.lang}${femTag}${neural}</div>
        </div>`;
    };

    let html = '';
    if (idVoices.length) {
        html += '<div class="vp-group-label">Bahasa Indonesia</div>';
        html += idVoices.map(makeItem).join('');
    }
    if (otherVoices.length) {
        html += '<div class="vp-group-label">Bahasa Lain</div>';
        html += otherVoices.map(makeItem).join('');
    }
    if (!html) html = '<div style="padding:14px;opacity:.4;font-size:.8rem;">Tidak ada suara tersedia</div>';
    el.innerHTML = html;

    // Update label tombol
    const lbl = document.getElementById('voice-picker-label');
    if (lbl && ttsVoice) {
        const shortName = ttsVoice.name.replace(/Microsoft |Google /i,'').split(' ')[0];
        lbl.textContent = shortName;
    }
}

function selectVoice(name) {
    const voices = speechSynthesis.getVoices();
    const v = voices.find(v => v.name === name);
    if (!v) return;
    ttsVoice = v;
    localStorage.setItem(TTS_KEY, name);
    renderVoiceList(voices);
    testCurrentVoice();
}

function testCurrentVoice() {
    if (!ttsUnlocked) return;
    speechSynthesis.cancel();
    speakOnce('Halo, nomor antrian, nol, nol, satu, silakan menuju, Meja satu');
}

function toggleVoicePicker() {
    document.getElementById('voice-picker-panel').classList.toggle('show');
}

// Tutup panel kalau klik di luar
document.addEventListener('click', e => {
    const panel = document.getElementById('voice-picker-panel');
    const btn   = document.getElementById('voice-picker-btn');
    if (panel && !panel.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
        panel.classList.remove('show');
    }
});

function numToDigits(n) {
    const w = ['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan'];
    return String(n).padStart(3,'0').split('').map(d => w[+d]).join(', ');
}

function speakOnce(text) {
    const u    = new SpeechSynthesisUtterance(text);
    if (ttsVoice) u.voice = ttsVoice;
    u.lang   = ttsVoice ? ttsVoice.lang : 'id-ID';
    u.rate   = 0.82;   // lebih lambat = lebih jelas & mulus
    u.pitch  = 1.0;    // natural, tidak perlu naikkan kalau sudah pilih suara perempuan
    u.volume = 1.0;
    speechSynthesis.speak(u);
}

function announceNumber(num, mejaNama, fase) {
    if (!ttsEnabled || !ttsUnlocked) return;
    speechSynthesis.cancel();
    const digits = numToDigits(num);
    const text   = `Nomor antrian, S S G, ${digits}, silakan menuju, ${mejaNama}`;
    speakOnce(text);
    setTimeout(() => speakOnce(text), 4200);
}

function unlockAudio() {
    const overlay = document.getElementById('tts-overlay');
    if (overlay) overlay.style.display = 'none';
    const u = new SpeechSynthesisUtterance('');
    speechSynthesis.speak(u);
    ttsUnlocked = true;
    loadVoices();
}

function toggleTTS() {
    ttsEnabled = !ttsEnabled;
    const btn   = document.getElementById('tts-toggle');
    const icon  = document.getElementById('tts-icon');
    const label = document.getElementById('tts-label');
    if (btn)   btn.classList.toggle('muted', !ttsEnabled);
    if (icon)  icon.className = ttsEnabled ? 'bi bi-volume-up-fill' : 'bi bi-volume-mute-fill';
    if (label) label.textContent = ttsEnabled ? 'Suara ON' : 'Suara OFF';
    if (!ttsEnabled) speechSynthesis.cancel();
}

// ── Beep ───────────────────────────────────────────────────────────────────────
function playBeep() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [880, 1100, 880].forEach((freq, i) => {
            const osc = ctx.createOscillator(), gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.value = freq; osc.type = 'sine';
            gain.gain.setValueAtTime(0, ctx.currentTime + i*.15);
            gain.gain.linearRampToValueAtTime(.4, ctx.currentTime + i*.15 + .02);
            gain.gain.linearRampToValueAtTime(0, ctx.currentTime + i*.15 + .18);
            osc.start(ctx.currentTime + i*.15);
            osc.stop(ctx.currentTime + i*.15 + .2);
        });
    } catch(e) {}
}
function showBeepIndicator() {
    const el = document.getElementById('beep-indicator');
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2500);
}

// ── State ──────────────────────────────────────────────────────────────────────
let lastNumber = <?= $latest ? $latest['nomor'] : 'null' ?>;
let lastMejaId = <?= $latest ? ($latest['meja_id'] ?? 0) : 'null' ?>;
const FASE_LABEL = {1: 'Fase 1 — Cek Berkas', 2: 'Fase 2 — Input Data'};
function pad3(n)   { return String(n).padStart(3,'0'); }
function fmtNum(n) { return 'SSG' + pad3(n); }

// ── Render Selanjutnya ────────────────────────────────────────────────────────
function renderNextCards(f1, f2) {
    const el = document.getElementById('next-cards');
    if (!el) return;
    const all = [...f1, ...f2];
    if (!all.length) {
        el.innerHTML = '<span class="next-empty">Semua antrian sudah selesai</span>';
        return;
    }
    let html = '';
    f1.forEach(n => {
        const meja = n.nama_meja || ('Loket ' + n.nomor_meja);
        html += `<div class="next-card f1">
            <div class="next-card-num">${fmtNum(n.nomor)}</div>
            <div class="next-card-info">
                <div class="next-card-meja">${meja}</div>
                <div class="next-card-fase">Fase 1</div>
            </div>
        </div>`;
    });
    f2.forEach(n => {
        const meja = n.nama_meja || ('Loket ' + n.nomor_meja);
        html += `<div class="next-card f2">
            <div class="next-card-num">${fmtNum(n.nomor)}</div>
            <div class="next-card-info">
                <div class="next-card-meja">${meja}</div>
                <div class="next-card-fase">Fase 2</div>
            </div>
        </div>`;
    });
    el.innerHTML = html;
}

// ── Render Stats Jurusan ──────────────────────────────────────────────────────
function renderStats(statsJur, statsTotal) {
    const grid  = document.getElementById('stats-grid');
    const total = document.getElementById('stat-total');
    if (grid && statsJur) {
        grid.innerHTML = Object.entries(statsJur).map(([kode, count]) =>
            `<div class="stat-jur-item">
                <span class="stat-jur-kode">${kode}</span>
                <span class="stat-jur-count">${count}</span>
            </div>`
        ).join('');
    }
    if (total && statsTotal !== undefined) total.textContent = statsTotal;
}

// ── Render Riwayat ────────────────────────────────────────────────────────────
function renderRecentList(list, colors) {
    const el = document.getElementById('recent-list');
    if (!list.length) {
        el.innerHTML = '<div style="opacity:.3;font-size:.85rem;text-align:center;margin-top:40px;">Belum ada antrian hari ini</div>';
        return;
    }
    el.innerHTML = list.map((a, i) => {
        const color = colors[a.meja_id] || '#7c3aed';
        const label = a.nama_meja || ('Loket ' + a.nomor_meja);
        const time  = a.dipanggil_at ? a.dipanggil_at.substr(11,5) : '';
        return `<div class="recent-item ${i===0?'first-item':''}">
            <div class="recent-num" style="color:${color}">${fmtNum(a.nomor)}</div>
            <div class="recent-info">
                <div class="recent-desk">${label}</div>
                <div class="recent-time">${time} &nbsp;<span class="fase-badge fase-${a.fase}">F${a.fase}</span></div>
            </div>
        </div>`;
    }).join('');
}

// ── Render Meja Grid ──────────────────────────────────────────────────────────
function renderMejaGrid(data) {
    const grid = document.getElementById('meja-grid');
    if (!grid) return;
    const serving = {};
    data.list.forEach(a => { if (!serving[a.meja_id]) serving[a.meja_id] = a; });
    grid.innerHTML = data.meja.map(m => {
        const color = data.colors[m.id] || '#7c3aed';
        const label = m.nama || ('Loket ' + m.nomor_meja);
        const s = serving[m.id];
        return `<div class="meja-card" style="background:${color}18;border-color:${color}40;">
            <div class="meja-card-left">
                ${s ? `<div class="meja-serving" style="color:${color}">${fmtNum(s.nomor)}</div>`
                    : `<div class="meja-idle">—</div>`}
            </div>
            <div>
                <div class="meja-num" style="color:${color}">${label}</div>
                <span class="fase-badge fase-${m.fase}">F${m.fase}</span>
            </div>
        </div>`;
    }).join('');
}

// ── Auto Refresh ───────────────────────────────────────────────────────────────
function refreshData() {
    fetch('?json=1')
        .then(r => r.json())
        .then(data => {
            const latest = data.latest;
            if (latest) {
                const newNum = latest.nomor, newMeja = latest.meja_id;
                if (lastNumber !== newNum || lastMejaId !== newMeja) {
                    lastNumber = newNum; lastMejaId = newMeja;
                    playBeep(); showBeepIndicator();
                    const mejaNama = latest.nama_meja || ('Meja ' + latest.nomor_meja);
                    announceNumber(newNum, mejaNama, latest.fase);
                    const numEl  = document.getElementById('current-number');
                    const deskEl = document.getElementById('current-desk');
                    const faseEl = document.getElementById('current-fase');
                    if (numEl)  { numEl.textContent = pad3(newNum); numEl.classList.add('flash'); setTimeout(() => numEl.classList.remove('flash'), 700); }
                    if (deskEl) { deskEl.textContent = latest.nama_meja || ('Loket ' + latest.nomor_meja); }
                    if (faseEl) { faseEl.textContent = FASE_LABEL[latest.fase] || ''; }
                }
            } else if (lastNumber !== null) {
                lastNumber = null;
                const panel = document.getElementById('current-panel');
                if (panel) panel.innerHTML = '<div class="no-call"><i class="bi bi-hourglass-split me-2"></i>Menunggu antrian...</div>';
            }
            renderNextCards(data.next_f1 || [], data.next_f2 || []);
            renderRecentList(data.list, data.colors);
            renderMejaGrid(data);
            renderStats(data.stats_jur, data.stats_total);
        })
        .catch(() => {});
}

// Refresh setiap 3 detik
setInterval(refreshData, 3000);
</script>
</body>
</html>
