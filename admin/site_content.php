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

    // PRG: redirect setelah POST agar refresh tidak mengulang aksi
    $_SESSION['flash_site_content'] = $msg;
    while (ob_get_level() > 0) ob_end_clean();
    header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . '?page=site_content');
    exit;
}

// ─── Load semua setting ──────────────────────────────────────────────────────
$settings = [];
$groups   = [];
foreach ($conn->query("SELECT * FROM site_settings ORDER BY group_name, setting_key") as $r) {
    $settings[$r['setting_key']] = $r;
    $groups[$r['group_name']][]  = $r;
}

$group_icons = [
    'Identitas'    => 'bi-building',
    'Hero'         => 'bi-image',
    'Tentang'      => 'bi-info-circle',
    'Lokasi'       => 'bi-geo-alt',
    'Footer'       => 'bi-layout-text-window',
    'Sosial Media' => 'bi-share-fill',
    'SEO'          => 'bi-search',
    'Logo'         => 'bi-badge-hd',
];
?>

<?= $msg ?>

<div class="d-flex align-items-center mb-4 gap-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-layout-text-window-reverse me-2 text-primary"></i>Konten & Tampilan Website</h4>
        <p class="text-muted small mb-0">Kelola teks dan konten yang ditampilkan di halaman publik</p>
    </div>
    <a href="/" target="_blank" class="btn btn-outline-secondary btn-sm ms-auto">
        <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Website
    </a>
</div>

<!-- Tab navigation -->
<ul class="nav nav-tabs mb-4" id="contentTabs">
    <?php $first = true; foreach ($groups as $group_name => $items): ?>
    <li class="nav-item">
        <a class="nav-link <?= $first ? 'active' : '' ?>" data-bs-toggle="tab" href="#tab-<?= htmlspecialchars($group_name) ?>">
            <i class="bi <?= $group_icons[$group_name] ?? 'bi-gear' ?> me-1"></i><?= htmlspecialchars($group_name) ?>
        </a>
    </li>
    <?php $first = false; endforeach; ?>
</ul>

<div class="tab-content">
<?php $first = true; foreach ($groups as $group_name => $items): ?>
<div class="tab-pane fade <?= $first ? 'show active' : '' ?>" id="tab-<?= htmlspecialchars($group_name) ?>">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex align-items-center gap-2">
            <i class="bi <?= $group_icons[$group_name] ?? 'bi-gear' ?> text-primary"></i>
            <strong><?= htmlspecialchars($group_name) ?></strong>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="group" value="<?= htmlspecialchars($group_name) ?>">
                <?php foreach ($items as $item): ?>
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        <?= htmlspecialchars($item['label']) ?>
                        <small class="text-muted fw-normal ms-1">(<?= $item['type'] ?>)</small>
                    </label>

                    <?php if ($item['type'] === 'textarea'): ?>
                    <textarea name="fields[<?= htmlspecialchars($item['setting_key']) ?>]"
                              class="form-control" rows="4"><?= htmlspecialchars($item['setting_value'] ?? '') ?></textarea>

                    <?php elseif ($item['type'] === 'image_url'): ?>
                    <input type="text" name="fields[<?= htmlspecialchars($item['setting_key']) ?>]"
                           class="form-control" value="<?= htmlspecialchars($item['setting_value'] ?? '') ?>"
                           placeholder="Contoh: assets/img/foto.webp">
                    <?php if (!empty($item['setting_value'])): ?>
                    <div class="mt-2">
                        <img src="<?= htmlspecialchars('../' . $item['setting_value']) ?>"
                             style="max-height:120px;max-width:320px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6;"
                             onerror="this.style.display='none'">
                    </div>
                    <?php endif; ?>

                    <?php elseif ($item['type'] === 'url'): ?>
                    <input type="text" name="fields[<?= htmlspecialchars($item['setting_key']) ?>]"
                           class="form-control" value="<?= htmlspecialchars($item['setting_value'] ?? '') ?>"
                           placeholder="https://...">
                    <?php if ($item['setting_key'] === 'maps_embed_url' && !empty($item['setting_value'])): ?>
                    <div class="mt-2 text-muted small"><i class="bi bi-info-circle me-1"></i>Salin URL dari Google Maps → Bagikan → Sematkan peta → salin src="..." saja</div>
                    <?php endif; ?>

                    <?php else: ?>
                    <input type="text" name="fields[<?= htmlspecialchars($item['setting_key']) ?>]"
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
<?php $first = false; endforeach; ?>
</div>
