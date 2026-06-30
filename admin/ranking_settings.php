<?php
// ── Auto-migrate default settings ────────────────────────────────────────
$defaults = [
    ['ranking_scroll_speed',      '0.7', 'text', 'Kecepatan Scroll Display',   'Ranking Display'],
    ['ranking_pause_ms',         '2500', 'text', 'Jeda di Atas/Bawah (ms)',    'Ranking Display'],
    ['ranking_display_gelombang',   '0', 'text', 'Gelombang yang ditampilkan', 'Ranking Display'],
    ['ranking_published',           '0', 'text', 'Status Publikasi Hasil',     'Ranking Display'],
];
foreach ($defaults as [$key, $val, $type, $label, $grp]) {
    $conn->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value, type, label, group_name)
                    VALUES (?, ?, ?, ?, ?)")->execute([$key, $val, $type, $label, $grp]);
}

// ── Handle publish toggle ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_publish_ranking') {
    $cur = (int)$conn->query("SELECT setting_value FROM site_settings WHERE setting_key='ranking_published'")->fetchColumn();
    $new = $cur ? '0' : '1';
    $conn->prepare("UPDATE site_settings SET setting_value=? WHERE setting_key='ranking_published'")->execute([$new]);
    $dash = !empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php';
    $_SESSION['flash_ranking_settings'] = $new === '1' ? 'published' : 'unpublished';
    while (ob_get_level() > 0) ob_end_clean();
    header("Location: {$dash}?page=ranking_settings");
    exit;
}

// ── Handle save ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_ranking_settings') {
    $speed_map = ['lambat' => '0.3', 'sedang' => '0.7', 'cepat' => '1.3'];
    $pause_map = ['singkat' => '1500', 'normal' => '2500', 'lama' => '4000'];

    $speed = $speed_map[$_POST['scroll_speed'] ?? 'sedang'] ?? '0.7';
    $pause = $pause_map[$_POST['pause_ms']     ?? 'normal'] ?? '2500';
    $glm   = in_array($_POST['display_gelombang'] ?? '0', ['0','1','2']) ? $_POST['display_gelombang'] : '0';

    $conn->prepare("UPDATE site_settings SET setting_value=? WHERE setting_key='ranking_scroll_speed'")->execute([$speed]);
    $conn->prepare("UPDATE site_settings SET setting_value=? WHERE setting_key='ranking_pause_ms'")->execute([$pause]);
    $conn->prepare("UPDATE site_settings SET setting_value=? WHERE setting_key='ranking_display_gelombang'")->execute([$glm]);

    $dash = !empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php';
    $_SESSION['flash_ranking_settings'] = 'success';
    while (ob_get_level() > 0) ob_end_clean();
    header("Location: {$dash}?page=ranking_settings");
    exit;
}

// ── Read current settings ─────────────────────────────────────────────────
$settings = [];
foreach ($conn->query("SELECT setting_key, setting_value FROM site_settings WHERE group_name='Ranking Display'") as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
$cur_speed     = (float)($settings['ranking_scroll_speed'] ?? 0.7);
$cur_pause     = (int)($settings['ranking_pause_ms'] ?? 2500);
$cur_glm       = $settings['ranking_display_gelombang'] ?? '0';
$cur_published = (int)($settings['ranking_published'] ?? 0);

// Map nilai ke label pilihan
$speed_label = $cur_speed <= 0.4 ? 'lambat' : ($cur_speed >= 1.0 ? 'cepat' : 'sedang');
$pause_label = $cur_pause <= 1800 ? 'singkat' : ($cur_pause >= 3500 ? 'lama' : 'normal');

$flash = '';
if (!empty($_SESSION['flash_ranking_settings'])) {
    $fv = $_SESSION['flash_ranking_settings'];
    if ($fv === 'published')   $flash = '<div class="alert alert-success d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i> Hasil penerimaan berhasil di-<strong>publish</strong>. Judul display berubah jadi resmi.</div>';
    elseif ($fv === 'unpublished') $flash = '<div class="alert alert-warning d-flex align-items-center gap-2 mb-4"><i class="bi bi-arrow-counterclockwise"></i> Hasil dikembalikan ke mode <strong>Sementara</strong>.</div>';
    else $flash = '<div class="alert alert-success d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i> Pengaturan berhasil disimpan.</div>';
    unset($_SESSION['flash_ranking_settings']);
}

$display_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/ranking_display.php';
?>

<?= $flash ?>

<div class="row g-4">
    <!-- Pengaturan tampilan -->
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-sliders text-primary"></i> Pengaturan Tampilan Display
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_ranking_settings">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Kecepatan Scroll</label>
                        <p class="text-muted small mb-2">Seberapa cepat layar bergerak naik/turun secara otomatis.</p>
                        <div class="d-flex gap-2">
                            <?php foreach (['lambat' => ['Lambat','Cocok untuk layar besar / banyak data','bi-speedometer'],
                                            'sedang' => ['Sedang','Seimbang — cocok untuk sebagian besar kondisi','bi-speedometer2'],
                                            'cepat'  => ['Cepat','Untuk data sedikit atau layar kecil','bi-lightning-fill']] as $k => [$lbl,$desc,$ic]): ?>
                            <label class="flex-fill border rounded-3 p-3 text-center cursor-pointer <?= $speed_label===$k?'border-primary bg-primary bg-opacity-10':'' ?>" style="cursor:pointer;">
                                <input type="radio" name="scroll_speed" value="<?= $k ?>" class="d-none" <?= $speed_label===$k?'checked':'' ?>>
                                <i class="bi <?= $ic ?> d-block mb-1 fs-5 <?= $speed_label===$k?'text-primary':'' ?>"></i>
                                <div class="fw-semibold small"><?= $lbl ?></div>
                                <div class="text-muted" style="font-size:.65rem;"><?= $desc ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Jeda di Atas / Bawah</label>
                        <p class="text-muted small mb-2">Berapa lama scroll berhenti di ujung sebelum berbalik arah.</p>
                        <div class="d-flex gap-2">
                            <?php foreach (['singkat' => ['Singkat','~1.5 detik'],
                                            'normal'  => ['Normal','~2.5 detik'],
                                            'lama'    => ['Lama','~4 detik']] as $k => [$lbl,$desc]): ?>
                            <label class="flex-fill border rounded-3 p-3 text-center cursor-pointer <?= $pause_label===$k?'border-primary bg-primary bg-opacity-10':'' ?>" style="cursor:pointer;">
                                <input type="radio" name="pause_ms" value="<?= $k ?>" class="d-none" <?= $pause_label===$k?'checked':'' ?>>
                                <div class="fw-semibold"><?= $lbl ?></div>
                                <div class="text-muted small"><?= $desc ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Gelombang yang Ditampilkan</label>
                        <p class="text-muted small mb-2">Pilih data gelombang mana yang muncul di layar Display Peringkat.</p>
                        <div class="d-flex gap-2">
                            <?php foreach (['0' => ['Semua','G1 + G2 ditampilkan bersama','bi-collection-fill'],
                                            '1' => ['Gelombang 1','Hanya siswa diterima G1','bi-1-circle-fill'],
                                            '2' => ['Gelombang 2','Hanya siswa diterima G2','bi-2-circle-fill']] as $k => [$lbl,$desc,$ic]): ?>
                            <label class="flex-fill border rounded-3 p-3 text-center <?= $cur_glm===$k?'border-primary bg-primary bg-opacity-10':'' ?>" style="cursor:pointer;">
                                <input type="radio" name="display_gelombang" value="<?= $k ?>" class="d-none" <?= $cur_glm===$k?'checked':'' ?>>
                                <i class="bi <?= $ic ?> d-block mb-1 fs-5 <?= $cur_glm===$k?'text-primary':'' ?>"></i>
                                <div class="fw-semibold small"><?= $lbl ?></div>
                                <div class="text-muted" style="font-size:.65rem;"><?= $desc ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i> Simpan Pengaturan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Info akses display -->
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-display text-success"></i> Akses Display Peringkat
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Buka halaman ini di layar TV atau monitor publik. Halaman akan memperbarui data secara otomatis setiap 5 detik.</p>

                <div class="input-group mb-3">
                    <input type="text" class="form-control form-control-sm font-monospace" id="displayUrl" value="<?= htmlspecialchars($display_url) ?>" readonly>
                    <button class="btn btn-outline-secondary btn-sm" onclick="copyUrl()" title="Salin URL">
                        <i class="bi bi-clipboard" id="copyIcon"></i>
                    </button>
                </div>

                <a href="<?= htmlspecialchars($display_url) ?>" target="_blank" class="btn btn-success w-100">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Display Peringkat
                </a>

                <hr class="my-3">
                <div class="d-flex align-items-start gap-2 text-muted small">
                    <i class="bi bi-info-circle-fill text-info mt-1 flex-shrink-0"></i>
                    <span>Hanya siswa dengan status <strong>Diterima</strong> yang tampil di display. Proses penerimaan tetap harus dilakukan manual melalui halaman <strong>Ranking & Hasil</strong>.</span>
                </div>
            </div>
        </div>

        <!-- Publish Hasil -->
        <div class="card shadow-sm mt-4 border-<?= $cur_published ? 'success' : 'warning' ?>">
            <div class="card-header fw-semibold d-flex align-items-center gap-2 <?= $cur_published ? 'bg-success text-white' : 'bg-warning-subtle' ?>">
                <i class="bi bi-<?= $cur_published ? 'patch-check-fill' : 'clock-history' ?>"></i>
                Status Hasil Penerimaan
                <span class="badge ms-auto <?= $cur_published ? 'bg-white text-success' : 'bg-warning text-dark' ?>">
                    <?= $cur_published ? 'RESMI / FINAL' : 'SEMENTARA' ?>
                </span>
            </div>
            <div class="card-body">
                <?php if ($cur_published): ?>
                <p class="small text-success mb-3"><i class="bi bi-check-circle-fill me-1"></i>
                    Judul di layar Display sudah berubah jadi <strong>"Hasil Penerimaan Siswa Baru"</strong> (resmi).</p>
                <?php else: ?>
                <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i>
                    Layar Display masih menampilkan judul <strong>"Peringkat Sementara"</strong>. Tekan tombol di bawah saat pendaftaran sudah ditutup dan hasil sudah final.</p>
                <?php endif; ?>
                <form method="POST" onsubmit="return confirm('<?= $cur_published ? 'Kembalikan ke mode Sementara?' : 'Publish hasil penerimaan sebagai data FINAL/RESMI?' ?>')">
                    <input type="hidden" name="action" value="toggle_publish_ranking">
                    <button type="submit" class="btn w-100 <?= $cur_published ? 'btn-outline-danger' : 'btn-success' ?>">
                        <i class="bi bi-<?= $cur_published ? 'arrow-counterclockwise' : 'patch-check-fill' ?> me-1"></i>
                        <?= $cur_published ? 'Kembalikan ke Sementara' : 'Publish Hasil Penerimaan (Final)' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold d-flex align-items-center gap-2">
                <i class="bi bi-gear text-secondary"></i> Setting Aktif
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted small">Status hasil</td><td class="fw-semibold small"><?= $cur_published ? '<span class="badge bg-success">Resmi / Final</span>' : '<span class="badge bg-warning text-dark">Sementara</span>' ?></td></tr>
                    <tr><td class="text-muted small">Gelombang ditampilkan</td><td class="fw-semibold small"><?= $cur_glm==='1'?'Gelombang 1 saja':($cur_glm==='2'?'Gelombang 2 saja':'Semua (G1+G2)') ?></td></tr>
                    <tr><td class="text-muted small">Kecepatan scroll</td><td class="fw-semibold small"><?= ucfirst($speed_label) ?> (<?= $cur_speed ?>px/frame)</td></tr>
                    <tr><td class="text-muted small">Jeda di tepi</td><td class="fw-semibold small"><?= ucfirst($pause_label) ?> (<?= $cur_pause ?>ms)</td></tr>
                    <tr><td class="text-muted small">Refresh data</td><td class="fw-semibold small">Otomatis setiap 5 detik</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Radio card highlight
document.querySelectorAll('input[type=radio]').forEach(r => {
    r.addEventListener('change', () => {
        const name = r.name;
        document.querySelectorAll(`input[name="${name}"]`).forEach(x => {
            const lbl = x.closest('label');
            lbl.classList.toggle('border-primary', x.checked);
            lbl.classList.toggle('bg-primary', x.checked);
            lbl.classList.toggle('bg-opacity-10', x.checked);
            lbl.querySelector('i')?.classList.toggle('text-primary', x.checked);
        });
    });
});

function copyUrl() {
    navigator.clipboard.writeText(document.getElementById('displayUrl').value).then(() => {
        const ic = document.getElementById('copyIcon');
        ic.className = 'bi bi-clipboard-check';
        setTimeout(() => ic.className = 'bi bi-clipboard', 2000);
    });
}
</script>
