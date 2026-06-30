<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin/_constants.php';

$today = date('Y-m-d');
$antrian_aktif = [];
$meja_list     = [];

try {
    $stmt = $conn->prepare("
        SELECT a.nomor, a.jenis, a.dipanggil_at,
               m.id AS meja_id, m.nomor_meja, m.nama AS nama_meja, m.jurusan_du
        FROM antrian a JOIN meja m ON m.id = a.meja_id
        WHERE a.tanggal = ? AND a.jenis = 'daftar_ulang' AND a.status = 'dipanggil'
        ORDER BY a.dipanggil_at DESC LIMIT 10");
    $stmt->execute([$today]);
    $antrian_aktif = $stmt->fetchAll();

    $meja_list = $conn->query("SELECT * FROM meja WHERE is_active=1 AND jenis_du=1 ORDER BY nomor_meja")->fetchAll();
} catch (Throwable $e) {}

// Statistik hari ini
$du_stat = ['total' => 0, 'selesai' => 0, 'menunggu' => 0];
try {
    $ss = $conn->prepare("SELECT COUNT(*) t, SUM(status='selesai') s, SUM(status='menunggu') m FROM antrian WHERE tanggal=? AND jenis='daftar_ulang'");
    $ss->execute([$today]);
    $r = $ss->fetch();
    $du_stat = ['total' => (int)$r['t'], 'selesai' => (int)$r['s'], 'menunggu' => (int)$r['m']];
} catch (Throwable) {}

// AJAX refresh
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'list'  => $antrian_aktif,
        'stat'  => $du_stat,
        'time'  => date('H:i:s'),
    ]);
    exit;
}

$latest = $antrian_aktif[0] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Display Daftar Ulang — SMKS Laboratorium Jakarta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
html, body { height:100%; font-family:'Segoe UI',-apple-system,sans-serif; background:#0f172a; color:#f8fafc; overflow:hidden; }

/* ── Header ── */
.top-bar {
    background: linear-gradient(135deg,#059669,#10b981);
    padding: 10px 28px;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.top-bar .title { font-size:1.1rem; font-weight:800; letter-spacing:.4px; color:#fff; }
.top-bar .jam   { font-size:1.05rem; font-weight:700; color:#d1fae5; font-variant-numeric:tabular-nums; }

/* ── Layout ── */
body { display:flex; flex-direction:column; }
.main { flex:1; display:grid; grid-template-columns:1fr 340px; gap:0; min-height:0; }

/* ── Panel kiri: nomor dipanggil ── */
.panel-kiri {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding:24px; background:#0f172a;
}
.label-panggil { font-size:.85rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:#6ee7b7; margin-bottom:8px; }
.nomor-box {
    background:linear-gradient(135deg,#065f46,#059669);
    border-radius:24px; padding:28px 56px; text-align:center;
    box-shadow:0 8px 40px rgba(5,150,105,.4);
    margin-bottom:16px;
}
.nomor-big { font-size:7rem; font-weight:900; line-height:1; letter-spacing:-4px; color:#fff; }
.nomor-jurusan { font-size:1.5rem; font-weight:700; color:#d1fae5; margin-top:6px; }
.nomor-meja    { font-size:1rem; color:#6ee7b7; margin-top:4px; }

.kosong-box {
    text-align:center; color:#334155;
}
.kosong-box i { font-size:4rem; }
.kosong-box p { font-size:1rem; margin-top:8px; }

/* ── Riwayat panggilan ── */
.history-item {
    display:flex; align-items:center; gap:10px;
    padding:8px 12px; border-radius:10px; margin-bottom:6px;
    background:#1e293b;
}
.history-item.latest { background:#065f46; }
.history-nomor { font-size:1.6rem; font-weight:800; color:#6ee7b7; min-width:70px; font-variant-numeric:tabular-nums; }
.history-info  { flex:1; }
.history-info .h-jur  { font-size:.78rem; color:#f0fdf4; font-weight:600; }
.history-info .h-meja { font-size:.68rem; color:#94a3b8; }
.history-waktu { font-size:.68rem; color:#64748b; font-variant-numeric:tabular-nums; }

/* ── Panel kanan: meja + stat ── */
.panel-kanan {
    background:#0f172a; border-left:1px solid #1e293b;
    display:flex; flex-direction:column; padding:16px; gap:12px; overflow-y:auto;
}
.section-title { font-size:.7rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#475569; margin-bottom:6px; }

.meja-card {
    background:#1e293b; border-radius:10px; padding:10px 14px;
    display:flex; align-items:center; justify-content:space-between;
}
.meja-card .mc-num  { font-size:1rem; font-weight:800; color:#f8fafc; }
.meja-card .mc-jur  { font-size:.72rem; color:#94a3b8; }
.meja-card .mc-badge { font-size:.65rem; padding:2px 8px; border-radius:20px; background:#065f46; color:#6ee7b7; }

.stat-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; }
.stat-box  { background:#1e293b; border-radius:10px; padding:10px; text-align:center; }
.stat-box .sv { font-size:1.8rem; font-weight:800; color:#6ee7b7; }
.stat-box .sl { font-size:.65rem; color:#64748b; text-transform:uppercase; letter-spacing:.8px; }

/* ── TTS overlay ── */
#tts-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,.85);
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    z-index:9999; gap:16px;
}
#tts-overlay button {
    padding:16px 48px; font-size:1.3rem; font-weight:700; border-radius:14px;
    background:#059669; color:#fff; border:none; cursor:pointer;
}
#tts-overlay p { color:#94a3b8; font-size:.9rem; }
</style>
</head>
<body>

<div id="tts-overlay">
    <i class="bi bi-volume-up-fill" style="font-size:3rem;color:#6ee7b7;"></i>
    <p>Klik untuk mengaktifkan suara panggilan</p>
    <button onclick="unlockAudio()"><i class="bi bi-play-circle me-2"></i>Aktifkan Suara</button>
</div>

<div class="top-bar">
    <div class="title"><i class="bi bi-person-check-fill me-2"></i>Display Antrian — Daftar Ulang SMKS Laboratorium Jakarta</div>
    <div class="jam" id="jamDisplay"><?= date('H:i:s') ?></div>
</div>

<div class="main">
    <!-- KIRI: nomor aktif -->
    <div class="panel-kiri">
        <div class="label-panggil"><i class="bi bi-megaphone-fill me-1"></i>Sedang Dipanggil</div>

        <div id="nomorArea">
        <?php if ($latest): ?>
        <div class="nomor-box">
            <div class="nomor-big" id="nomorBig"><?= str_pad($latest['nomor'], 3, '0', STR_PAD_LEFT) ?></div>
            <div class="nomor-jurusan" id="nomorJur"><?= htmlspecialchars(JURUSAN_SHORT[$latest['jurusan_du'] ?? ''] ?? ($latest['jurusan_du'] ?? '')) ?></div>
            <div class="nomor-meja" id="nomorMeja"><i class="bi bi-person-workspace me-1"></i><?= htmlspecialchars($latest['nama_meja'] ?? 'Meja '.$latest['nomor_meja']) ?></div>
        </div>
        <?php else: ?>
        <div class="kosong-box">
            <i class="bi bi-hourglass"></i>
            <p>Belum ada panggilan hari ini</p>
        </div>
        <?php endif; ?>
        </div>

        <!-- Riwayat -->
        <div style="width:100%;max-width:480px;margin-top:20px;">
            <div class="section-title">Riwayat Panggilan</div>
            <div id="riwayatList">
            <?php foreach ($antrian_aktif as $i => $a): ?>
            <div class="history-item <?= $i===0?'latest':'' ?>">
                <div class="history-nomor"><?= str_pad($a['nomor'],3,'0',STR_PAD_LEFT) ?></div>
                <div class="history-info">
                    <div class="h-jur"><?= htmlspecialchars($a['jurusan_du'] ?? '-') ?></div>
                    <div class="h-meja"><i class="bi bi-person-workspace me-1"></i><?= htmlspecialchars($a['nama_meja'] ?? 'Meja '.$a['nomor_meja']) ?></div>
                </div>
                <div class="history-waktu"><?= date('H:i', strtotime($a['dipanggil_at'])) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($antrian_aktif)): ?>
            <div style="color:#334155;text-align:center;padding:12px;font-size:.85rem;">Belum ada riwayat</div>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- KANAN: meja & statistik -->
    <div class="panel-kanan">
        <div>
            <div class="section-title">Meja Daftar Ulang</div>
            <?php foreach ($meja_list as $m): ?>
            <div class="meja-card mb-2">
                <div>
                    <div class="mc-num">Meja <?= $m['nomor_meja'] ?><?= $m['nama'] ? ' — '.htmlspecialchars($m['nama']) : '' ?></div>
                    <div class="mc-jur"><?= htmlspecialchars($m['jurusan_du'] ?? 'Semua Jurusan') ?></div>
                </div>
                <span class="mc-badge"><?= htmlspecialchars(JURUSAN_SHORT[$m['jurusan_du'] ?? ''] ?? 'DU') ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div>
            <div class="section-title">Statistik Hari Ini</div>
            <div class="stat-grid">
                <div class="stat-box">
                    <div class="sv" id="statTotal"><?= $du_stat['total'] ?></div>
                    <div class="sl">Total</div>
                </div>
                <div class="stat-box">
                    <div class="sv" id="statSelesai"><?= $du_stat['selesai'] ?></div>
                    <div class="sl">Selesai</div>
                </div>
                <div class="stat-box">
                    <div class="sv" id="statMenunggu"><?= $du_stat['menunggu'] ?></div>
                    <div class="sl">Menunggu</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ── Jam ───────────────────────────────────────────────────────────────────────
setInterval(() => {
    const now = new Date();
    const pad = n => String(n).padStart(2,'0');
    document.getElementById('jamDisplay').textContent = pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds());
}, 1000);

// ── TTS ───────────────────────────────────────────────────────────────────────
let ttsUnlocked = false;
let ttsVoice    = null;
let speakQueue  = [];
let speaking    = false;
let speakStartedAt = 0;

function loadVoices() {
    const voices = speechSynthesis.getVoices();
    ttsVoice = voices.find(v => v.lang === 'id-ID') || voices.find(v => v.lang.startsWith('id')) || null;
}
speechSynthesis.onvoiceschanged = loadVoices;
loadVoices();

function unlockAudio() {
    document.getElementById('tts-overlay').style.display = 'none';
    const u = new SpeechSynthesisUtterance('');
    speechSynthesis.speak(u);
    ttsUnlocked = true;
    loadVoices();
}

function terbilang(n) {
    n = parseInt(n, 10);
    const sat = ['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
    if (n < 12)  return sat[n];
    if (n < 20)  return terbilang(n - 10) + ' belas';
    if (n < 100) return terbilang(Math.floor(n / 10)) + ' puluh' + (n % 10 ? ' ' + terbilang(n % 10) : '');
    if (n < 200) return 'seratus' + (n % 100 ? ' ' + terbilang(n % 100) : '');
    if (n < 1000) return terbilang(Math.floor(n / 100)) + ' ratus' + (n % 100 ? ' ' + terbilang(n % 100) : '');
    return String(n);
}
function numToSpoken(n) {
    const s = String(n).padStart(3, '0');
    const parts = [];
    let i = 0;
    while (i < s.length - 1 && s[i] === '0') { parts.push('kosong'); i++; }
    const rest = parseInt(s.slice(i), 10);
    parts.push(rest === 0 ? 'kosong' : terbilang(rest));
    return parts.join(' ');
}

function announce(num, jurusanDu, mejaNama) {
    if (!ttsUnlocked) return;
    // Nomor dieja per-digit: 1 → "0 0 1"
    const nDigits = String(num).padStart(3, '0').split('').join(' ');
    // Kode jurusan diambil dari dalam kurung "(RPL)" lalu dieja: "R P L"
    const code    = (String(jurusanDu || '').match(/\(([^)]+)\)/) || ['', ''])[1];
    const jurPart = code ? code.split('').join(' ') + ', ' : '';
    const text    = `D U ${nDigits} ${jurPart}Silakan Menuju, ${mejaNama}`;
    speakQueue.push({ text, repeatLeft: 2 });
    pumpQueue();
}

function pumpQueue() {
    if (speaking || speakQueue.length === 0) return;
    speaking = true;
    speakStartedAt = Date.now();
    const item = speakQueue[0];
    const u = new SpeechSynthesisUtterance(item.text);
    if (ttsVoice) u.voice = ttsVoice;
    u.lang = ttsVoice ? ttsVoice.lang : 'id-ID';
    u.rate = 0.82; u.pitch = 1.0; u.volume = 1.0;
    const advance = () => {
        speaking = false;
        item.repeatLeft--;
        if (item.repeatLeft > 0 && speakQueue.length === 1) {
            pumpQueue();
        } else {
            speakQueue.shift();
            pumpQueue();
        }
    };
    u.onend = advance;
    u.onerror = advance;
    speechSynthesis.speak(u);
}

// Watchdog anti-macet
setInterval(() => {
    try { if (speechSynthesis.paused) speechSynthesis.resume(); } catch(e) {}
    if (speaking && Date.now() - speakStartedAt > 12000) {
        speaking = false;
        try { speechSynthesis.cancel(); } catch(e) {}
        speakQueue.shift();
        pumpQueue();
    }
}, 4000);

// ── Polling data ──────────────────────────────────────────────────────────────
let announcedKeys = null;

function keyOf(a) { return a.meja_id + '|' + a.nomor + '|' + (a.dipanggil_at || ''); }

function renderNomor(a) {
    if (!a) {
        document.getElementById('nomorArea').innerHTML = `
        <div class="kosong-box">
            <i class="bi bi-hourglass"></i>
            <p>Belum ada panggilan hari ini</p>
        </div>`;
        return;
    }
    const nStr = String(a.nomor).padStart(3,'0');
    const jur  = a.jurusan_du || '';
    const jurShort = <?= json_encode(array_map(fn($v) => $v, JURUSAN_SHORT), JSON_UNESCAPED_UNICODE) ?>;
    const jurLabel = jurShort[jur] || jur;
    const meja = a.nama_meja || ('Meja ' + a.nomor_meja);
    document.getElementById('nomorArea').innerHTML = `
    <div class="nomor-box">
        <div class="nomor-big">${nStr}</div>
        <div class="nomor-jurusan">${jurLabel}</div>
        <div class="nomor-meja"><i class="bi bi-person-workspace me-1"></i>${meja}</div>
    </div>`;
}

function renderRiwayat(list) {
    const el = document.getElementById('riwayatList');
    if (!list.length) { el.innerHTML = '<div style="color:#334155;text-align:center;padding:12px;font-size:.85rem;">Belum ada riwayat</div>'; return; }
    el.innerHTML = list.map((a, i) => `
    <div class="history-item ${i===0?'latest':''}">
        <div class="history-nomor">${String(a.nomor).padStart(3,'0')}</div>
        <div class="history-info">
            <div class="h-jur">${a.jurusan_du || '-'}</div>
            <div class="h-meja"><i class="bi bi-person-workspace me-1"></i>${a.nama_meja || 'Meja '+a.nomor_meja}</div>
        </div>
        <div class="history-waktu">${(a.dipanggil_at||'').slice(11,16)}</div>
    </div>`).join('');
}

function fetchData() {
    fetch('du_display.php?json=1')
        .then(r => r.json())
        .then(d => {
            renderNomor(d.list[0] || null);
            renderRiwayat(d.list || []);

            // Stat
            if (d.stat) {
                document.getElementById('statTotal').textContent    = d.stat.total;
                document.getElementById('statSelesai').textContent  = d.stat.selesai;
                document.getElementById('statMenunggu').textContent = d.stat.menunggu;
            }

            // Deteksi panggilan baru
            const fresh = (d.list || []).filter(a => !announcedKeys?.has(keyOf(a)));
            if (announcedKeys === null) {
                announcedKeys = new Set((d.list||[]).map(keyOf));
            } else {
                fresh.reverse().forEach(a => {
                    announcedKeys.add(keyOf(a));
                    announce(a.nomor, a.jurusan_du, a.nama_meja || ('Meja '+a.nomor_meja));
                });
                if (announcedKeys.size > 500) announcedKeys = new Set((d.list||[]).map(keyOf));
            }
        })
        .catch(() => {});
}

fetchData();
setInterval(fetchData, 4000);
</script>
</body>
</html>
