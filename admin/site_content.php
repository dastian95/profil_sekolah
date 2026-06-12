<?php
if (empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger"><i class="bi bi-shield-exclamation me-2"></i>Akses ditolak. Halaman ini hanya untuk Super Admin.</div>';
    return;
}

$msg = '';
$err = '';

// ─── Seed tabel jika belum ada ──────────────────────────────────────────────
$conn->exec("CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `type`          ENUM('text','textarea','image_url','url','color') NOT NULL DEFAULT 'text',
  `label`         VARCHAR(200) NOT NULL,
  `group_name`    VARCHAR(100) NOT NULL,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$defaults = [
    ['sekolah_nama',    'SMKS Laboratorium Jakarta',                   'text',      'Nama Sekolah',             'Identitas'],
    ['sekolah_tagline', 'Selamat Datang di Portal SPMB',              'text',      'Tagline',                  'Identitas'],
    ['sekolah_alamat',  'Jl. Rawa Jaya No.37, Duren Sawit, Jakarta Timur 13460', 'textarea', 'Alamat',         'Identitas'],
    ['sekolah_telp',    '',                                            'text',      'No. Telepon',              'Identitas'],
    ['sekolah_email',   '',                                            'text',      'Email',                    'Identitas'],
    ['hero_title',      'Bergabunglah di SMKS Laboratorium Jakarta',  'text',      'Judul Hero',               'Hero'],
    ['hero_subtitle2',  'Tahun Ajaran 2026 / 2027',                   'text',      'Sub-judul Hero (Tahun Ajaran)', 'Hero'],
    ['hero_subtitle',   'Wujudkan impianmu bersama kami — sekolah kejuruan terpercaya dengan fasilitas modern dan tenaga pengajar berpengalaman.', 'textarea', 'Deskripsi Hero', 'Hero'],
    ['hero_bg_image',   'assets/img/gedung-sekolah.webp',             'image_url', 'Background Hero (path/URL)', 'Hero'],
    ['about_text',      'SMKS Laboratorium Jakarta adalah sekolah menengah kejuruan yang berdedikasi untuk menghasilkan lulusan berkompeten di bidang teknologi, kesehatan, dan kecantikan. Kami berkomitmen memberikan pendidikan berkualitas dengan fasilitas laboratorium modern dan metode pembelajaran yang relevan dengan industri.', 'textarea', 'Deskripsi Sekolah', 'Tentang'],
    ['about_image',     'assets/img/gedung-sekolah.webp',             'image_url', 'Foto Gedung (path/URL)',    'Tentang'],
    ['maps_embed_url',  'https://maps.google.com/maps?q=-6.2350331,106.9439031&z=17&output=embed', 'url', 'URL Embed Google Maps', 'Lokasi'],
    ['footer_text',     '',                                            'text',      'Teks Footer Tambahan',     'Footer'],
    // Sosial Media
    ['sosmed_instagram', '', 'url',      'Instagram URL',                 'Sosial Media'],
    ['sosmed_facebook',  '', 'url',      'Facebook URL',                  'Sosial Media'],
    ['sosmed_youtube',   '', 'url',      'YouTube URL',                   'Sosial Media'],
    ['sosmed_tiktok',    '', 'url',      'TikTok URL',                    'Sosial Media'],
    // SEO
    ['seo_description',  '', 'textarea', 'Meta Description (maks 160 karakter)', 'SEO'],
    ['seo_keywords',     '', 'text',     'Meta Keywords (pisah koma)',    'SEO'],
    // Logo
    ['logo_url',     '', 'image_url', 'Logo Sekolah (path/URL)',         'Logo'],
    ['favicon_url',  '', 'image_url', 'Favicon (path/URL .ico/.png)',    'Logo'],
];
$ins_default = $conn->prepare("INSERT IGNORE INTO site_settings (setting_key, setting_value, type, label, group_name) VALUES (?,?,?,?,?)");
foreach ($defaults as $d) $ins_default->execute($d);

// Paksa update nilai default yang sudah ada jika masih pakai nilai lama/salah
$fix_values = [
    'hero_title'     => 'Bergabunglah di SMKS Laboratorium Jakarta',
    'maps_embed_url' => 'https://maps.google.com/maps?q=-6.2350331,106.9439031&z=17&output=embed',
];
$upd_fix = $conn->prepare("UPDATE site_settings SET setting_value=? WHERE setting_key=? AND setting_value=?");
$upd_fix->execute(['Bergabunglah di SMKS Laboratorium Jakarta', 'hero_title', 'Seleksi Penerimaan Murid Baru']);
$upd_fix->execute(['https://maps.google.com/maps?q=-6.2350331,106.9439031&z=17&output=embed', 'maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7!2d106.9439031!3d-6.2350331']);

// Flash message dari PRG redirect
if (!empty($_SESSION['flash_site_content'])) {
    $msg = $_SESSION['flash_site_content'];
    unset($_SESSION['flash_site_content']);
}

// ─── POST: simpan grup ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $group = $_POST['group'] ?? '';
    $fields = $_POST['fields'] ?? [];
    if ($group && $fields) {
        $upd = $conn->prepare("UPDATE site_settings SET setting_value=? WHERE setting_key=? AND group_name=?");
        foreach ($fields as $key => $val) {
            $upd->execute([trim($val), $key, $group]);
        }
        log_admin_action($conn, 'EDIT_SITE_CONTENT', "Update konten grup: {$group}");
        $msg = '<div class="alert alert-success alert-dismissible"><i class="bi bi-check-circle me-2"></i>Konten <strong>' . htmlspecialchars($group) . '</strong> berhasil disimpan. <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }

    // PRG: redirect setelah POST agar refresh tidak mengulang aksi (tab aktif dipertahankan)
    $_SESSION['flash_site_content'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php')
        . '?page=site_content' . ($group ? '&tab=' . urlencode($group) : ''));
    exit;
}

// ─── Load semua setting ──────────────────────────────────────────────────────
$settings = [];
$groups   = [];
foreach ($conn->query("SELECT * FROM site_settings ORDER BY group_name, setting_key") as $r) {
    $settings[$r['setting_key']] = $r;
    $groups[$r['group_name']][]  = $r;
}

// Urutan & ikon grup di sidebar pills
$group_order = [
    'Identitas', 'Logo', 'Hero', 'Tentang', 'Navbar', 'Judul Section',
    'Jurusan: RPL', 'Jurusan: TKJ', 'Jurusan: AP', 'Jurusan: TKKR',
    'Cara Mendaftar', 'Lokasi', 'Footer', 'Sosial Media', 'Tema Warna', 'SEO',
];
$ordered_groups = [];
foreach ($group_order as $g) { if (isset($groups[$g])) $ordered_groups[$g] = $groups[$g]; }
foreach ($groups as $g => $items) { if (!isset($ordered_groups[$g])) $ordered_groups[$g] = $items; }
$groups = $ordered_groups;

$group_icons = [
    'Identitas'      => 'bi-building',
    'Hero'           => 'bi-image',
    'Tentang'        => 'bi-info-circle',
    'Navbar'         => 'bi-list',
    'Judul Section'  => 'bi-type-h2',
    'Jurusan: RPL'   => 'bi-code-slash',
    'Jurusan: TKJ'   => 'bi-hdd-network',
    'Jurusan: AP'    => 'bi-heart-pulse',
    'Jurusan: TKKR'  => 'bi-stars',
    'Cara Mendaftar' => 'bi-card-checklist',
    'Lokasi'         => 'bi-geo-alt',
    'Footer'         => 'bi-layout-text-window',
    'Sosial Media'   => 'bi-share-fill',
    'Tema Warna'     => 'bi-palette',
    'SEO'            => 'bi-search',
    'Logo'           => 'bi-badge-hd',
];

// Tab aktif: dari ?tab= (persist setelah simpan) atau grup pertama
$active_group = isset($_GET['tab']) && isset($groups[$_GET['tab']]) ? $_GET['tab'] : array_key_first($groups);
?>

<?= $msg ?>

<div class="d-flex align-items-center mb-4 gap-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-layout-text-window-reverse me-2 text-primary"></i>Konten & Tampilan Website</h4>
        <p class="text-muted small mb-0">Kelola teks, gambar, dan tampilan halaman publik — tanpa menyentuh kode</p>
    </div>
    <a href="index.php" target="_blank" class="btn btn-outline-secondary btn-sm ms-auto">
        <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Website
    </a>
</div>

<div class="row g-4">
    <!-- Pills navigasi grup (kiri) -->
    <div class="col-md-3">
        <div class="nav flex-column nav-pills sticky-top" style="top:80px;max-height:calc(100vh - 110px);overflow-y:auto;" id="contentTabs" role="tablist">
            <?php foreach ($groups as $group_name => $items): ?>
            <a class="nav-link text-start mb-1 <?= $group_name === $active_group ? 'active' : '' ?>"
               data-bs-toggle="pill" href="#tab-<?= md5($group_name) ?>" role="tab">
                <i class="bi <?= $group_icons[$group_name] ?? 'bi-gear' ?> me-2"></i><?= htmlspecialchars($group_name) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Konten grup (kanan) -->
    <div class="col-md-9">
        <div class="tab-content">
        <?php foreach ($groups as $group_name => $items): ?>
        <div class="tab-pane fade <?= $group_name === $active_group ? 'show active' : '' ?>" id="tab-<?= md5($group_name) ?>">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
                    <i class="bi <?= $group_icons[$group_name] ?? 'bi-gear' ?> text-primary"></i>
                    <strong><?= htmlspecialchars($group_name) ?></strong>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="group" value="<?= htmlspecialchars($group_name) ?>">
                        <?php foreach ($items as $item): $skey = $item['setting_key']; ?>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                <?= htmlspecialchars($item['label']) ?>
                                <small class="text-muted fw-normal ms-1">(<?= $item['type'] ?>)</small>
                            </label>

                            <?php if ($item['type'] === 'textarea'): ?>
                            <textarea name="fields[<?= htmlspecialchars($skey) ?>]"
                                      class="form-control" rows="4"><?= htmlspecialchars($item['setting_value'] ?? '') ?></textarea>

                            <?php elseif ($item['type'] === 'image_url'): ?>
                            <div class="input-group">
                                <input type="text" name="fields[<?= htmlspecialchars($skey) ?>]"
                                       id="inp_<?= htmlspecialchars($skey) ?>"
                                       class="form-control" value="<?= htmlspecialchars($item['setting_value'] ?? '') ?>"
                                       placeholder="Contoh: assets/img/foto.webp">
                                <button type="button" class="btn btn-outline-primary"
                                        onclick="document.getElementById('file_<?= htmlspecialchars($skey) ?>').click()">
                                    <i class="bi bi-upload me-1"></i>Upload
                                </button>
                                <input type="file" id="file_<?= htmlspecialchars($skey) ?>" accept="image/*" class="d-none"
                                       onchange="uploadImg(this, 'inp_<?= htmlspecialchars($skey) ?>', 'prev_<?= htmlspecialchars($skey) ?>')">
                            </div>
                            <div class="mt-2" id="prevwrap_<?= htmlspecialchars($skey) ?>">
                                <img id="prev_<?= htmlspecialchars($skey) ?>"
                                     src="<?= !empty($item['setting_value']) ? htmlspecialchars($item['setting_value']) : '' ?>"
                                     style="max-height:120px;max-width:320px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6;<?= empty($item['setting_value']) ? 'display:none;' : '' ?>"
                                     onerror="this.style.display='none'">
                            </div>

                            <?php elseif ($item['type'] === 'url'): ?>
                            <input type="text" name="fields[<?= htmlspecialchars($skey) ?>]"
                                   class="form-control" value="<?= htmlspecialchars($item['setting_value'] ?? '') ?>"
                                   placeholder="https://...">
                            <?php if ($skey === 'maps_embed_url' && !empty($item['setting_value'])): ?>
                            <div class="mt-2 text-muted small"><i class="bi bi-info-circle me-1"></i>Salin URL dari Google Maps → Bagikan → Sematkan peta → salin src="..." saja</div>
                            <?php endif; ?>

                            <?php elseif ($item['type'] === 'color'): ?>
                            <?php $cval = $item['setting_value'] ?: '#000000'; ?>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" class="form-control form-control-color"
                                       id="color_<?= htmlspecialchars($skey) ?>"
                                       value="<?= htmlspecialchars(preg_match('/^#[0-9a-fA-F]{6}$/', $cval) ? $cval : '#000000') ?>"
                                       oninput="document.getElementById('ctext_<?= htmlspecialchars($skey) ?>').value = this.value">
                                <input type="text" name="fields[<?= htmlspecialchars($skey) ?>]"
                                       id="ctext_<?= htmlspecialchars($skey) ?>"
                                       class="form-control font-monospace" style="max-width:140px;"
                                       value="<?= htmlspecialchars($item['setting_value'] ?? '') ?>"
                                       pattern="#[0-9a-fA-F]{6}" placeholder="#22aa55"
                                       oninput="if(/^#[0-9a-fA-F]{6}$/.test(this.value)) document.getElementById('color_<?= htmlspecialchars($skey) ?>').value = this.value">
                            </div>

                            <?php else: ?>
                            <input type="text" name="fields[<?= htmlspecialchars($skey) ?>]"
                                   class="form-control" value="<?= htmlspecialchars($item['setting_value'] ?? '') ?>">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-end border-top pt-3">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-2"></i>Simpan <?= htmlspecialchars($group_name) ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
async function uploadImg(fileInput, targetId, previewId) {
    if (!fileInput.files || !fileInput.files[0]) return;
    const btn = fileInput.previousElementSibling;
    const oldHtml = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
    try {
        const fd = new FormData();
        fd.append('image', fileInput.files[0]);
        const r = await fetch('admin/upload_image.php', { method: 'POST', body: fd });
        const j = await r.json();
        if (!j.ok) { alert(j.error || 'Upload gagal.'); return; }
        document.getElementById(targetId).value = j.path;
        const prev = document.getElementById(previewId);
        if (prev) { prev.src = j.path; prev.style.display = ''; }
    } catch (e) {
        alert('Upload gagal: ' + e.message);
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
        fileInput.value = '';
    }
}
</script>
