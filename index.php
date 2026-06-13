<?php
require_once __DIR__ . '/conn.php';

// Load site_settings — graceful fallback jika tabel belum ada
$_ss = [];
try {
    foreach ($conn->query("SELECT setting_key, setting_value FROM site_settings") as $r) {
        $_ss[$r['setting_key']] = $r['setting_value'];
    }
} catch (Exception $e) { /* tabel belum dibuat, pakai default */ }
$s = fn($k, $d = '') => htmlspecialchars($_ss[$k] ?? $d);
$sr = fn($k, $d = '') => $_ss[$k] ?? $d; // raw (unescaped), untuk URL/src
// List: textarea 1 item per baris → array (untuk daftar keahlian, syarat, dll)
$slist = fn($k, $d = '') => array_values(array_filter(array_map('trim', explode("\n", $_ss[$k] ?? $d)), fn($x) => $x !== ''));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <!-- Verifikasi kepemilikan situs Google Search Console -->
  <meta name="google-site-verification" content="_usDkUv5nr1NaY5SZVWE2uwiYmUhkYlsWdsKrCW3348" />
  <title><?= $s('sekolah_nama', 'SMKS Laboratorium Jakarta') ?> — SPMB</title>
  <meta name="description" content="<?= $s('seo_description') ?>">
  <meta name="keywords" content="<?= $s('seo_keywords') ?>">

  <!-- Favicons — favicon.ico di root untuk Google, PNG untuk browser/perangkat -->
  <?php $fav = $sr('favicon_url', 'assets/img/smk.png') ?: 'assets/img/smk.png'; ?>
  <link rel="icon" href="favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($fav) ?>">
  <link rel="shortcut icon" href="favicon.ico">
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/animate.css/animate.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <!-- Cache buster: ensures browser reloads the latest CSS after edits -->
  <link href="assets/css/main.css?v=20260522" rel="stylesheet">

  <style>
    /* TEMA WARNA — dari panel Konten Website (override main.css) */
    :root {
      --accent-color: <?= htmlspecialchars($sr('theme_accent', '#2f8258') ?: '#2f8258') ?>;
      --nav-hover-color: <?= htmlspecialchars($sr('theme_accent', '#2f8258') ?: '#2f8258') ?>;
      --nav-dropdown-hover-color: <?= htmlspecialchars($sr('theme_accent', '#2f8258') ?: '#2f8258') ?>;
    }

    /* MOBILE RESPONSIVENESS */
    @media (max-width: 768px) {

      .form-control,
      .form-select {
        font-size: 16px;
        padding: 12px;
        height: auto;
      }

      .btn {
        min-height: 44px;
        padding: 12px 16px;
        font-size: 1rem;
      }

      .table {
        font-size: 0.9rem;
      }

      .carousel-container h2 {
        font-size: 1.5rem;
      }

      .carousel-container p {
        font-size: 0.9rem;
      }
    }

    /* FORM VALIDATION */
    .form-control.is-valid,
    .form-select.is-valid {
      border-color: #28a745;
      box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .form-control.is-invalid,
    .form-select.is-invalid {
      border-color: #dc3545;
      box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .valid-feedback,
    .invalid-feedback {
      display: block;
      margin-top: 5px;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .valid-feedback {
      color: #28a745;
    }

    .invalid-feedback {
      color: #dc3545;
    }

    /* ACCESSIBILITY */
    button:focus-visible,
    a:focus-visible,
    input:focus-visible,
    select:focus-visible,
    textarea:focus-visible {
      outline: 2px solid var(--accent-color);
      outline-offset: 2px;
    }

    @media (prefers-reduced-motion: reduce) {

      /* Hanya matikan animasi looping, JANGAN matikan transition agar AOS tetap jalan */
      *,
      *::before,
      *::after {
        animation-iteration-count: 1 !important;
      }
    }

    /* SMOOTH SCROLL BEHAVIOR */
    html {
      scroll-behavior: smooth;
    }

    /* NAVIGATION STYLING */
    .nav-link {
      transition: all 0.3s ease !important;
      color: #333 !important;
      font-weight: 500;
      text-decoration: none;
    }

    .nav-link:hover {
      color: <?= htmlspecialchars($sr('theme_link', '#667eea') ?: '#667eea') ?> !important;
    }

    .nav-link.active {
      color: #fff !important;
      border-bottom: 3px solid <?= htmlspecialchars($sr('theme_link', '#667eea') ?: '#667eea') ?>;
      padding-bottom: 5px;
    }

    /* CARD HOVER LIFT */
    .card {
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.13) !important;
    }

    /* SECTION ICON CIRCLES — subtle pulse on hover */
    .rounded-circle[style*="background:#198754"],
    .rounded-circle[style*="background:#667eea"] {
      transition: transform 0.2s ease;
    }

    .rounded-circle[style*="background:#198754"]:hover,
    .rounded-circle[style*="background:#667eea"]:hover {
      transform: scale(1.12);
    }

    /* TAB SWITCHING ENTRANCE ANIMATION */
    @keyframes tabSlideIn {
      from {
        opacity: 0;
        transform: translateY(22px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .tab-pane-animate {
      animation: tabSlideIn 0.45s ease-out forwards;
    }

    /* JURUSAN TAB NAV — responsive text truncation */
    .features .nav-link h4 {
      font-size: 15px;
      white-space: normal;
      line-height: 1.25;
      text-align: center;
    }

    @media (max-width: 991px) {
      .features .nav-link h4 {
        font-size: 13px;
      }
    }

    /* JURUSAN LOGO DISPLAY BOX */
    .jurusan-logo-box {
      background: #ffffff;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .jurusan-logo-box img {
      max-height: 200px;
      max-width: 90%;
      object-fit: contain;
      animation: logoFloat 3s ease-in-out infinite;
      filter: drop-shadow(0 8px 16px rgba(0,0,0,.12));
    }

    @keyframes logoFloat {
      0%, 100% { transform: translateY(0px);    filter: drop-shadow(0 8px 16px rgba(0,0,0,.12)); }
      50%       { transform: translateY(-10px);  filter: drop-shadow(0 18px 24px rgba(0,0,0,.18)); }
    }

    /* Hero — gambar sekolah sebagai background full-width */
    .hero {
      background: linear-gradient(rgba(0, 0, 0, .55), rgba(0, 0, 0, .55)),
        url('<?= htmlspecialchars($sr('hero_bg_image', 'assets/img/gedung-sekolah.webp')) ?>') center/cover no-repeat !important;
    }

    .hero .carousel-container {
      position: relative;
      z-index: 1;
    }
  </style>

  <!-- =======================================================
  * Template Name: Selecao
  * Template URL: https://bootstrapmade.com/selecao-bootstrap-template/
  * Updated: Aug 07 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.php" class="logo d-flex align-items-center">
        <img class="sitename" src="<?= htmlspecialchars($sr('logo_url', 'assets/img/smk.png') ?: 'assets/img/smk.png') ?>" style="max-height: 50px;">
        <h1><?= $s('sekolah_nama', 'SMKS Laboratorium Jakarta') ?></h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="#home" class="nav-link"><?= $s('nav_home', '🏠 Home') ?></a></li>
          <li><a href="#about" class="nav-link"><?= $s('nav_tentang', 'ℹ️ Tentang Kami') ?></a></li>
          <li><a href="#jurusan" class="nav-link"><?= $s('nav_jurusan', '📚 Jurusan') ?></a></li>
          <li><a href="#cara-mendaftar" class="nav-link"><?= $s('nav_daftar', '📝 Cara Mendaftar') ?></a></li>
          <li><a href="#pengumuman" class="nav-link"><?= $s('nav_pengumuman', '📋 Pengumuman') ?></a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
  </header>

  <main class="main">

    <!-- Hero Section -->
    <section id="home" class="hero section dark-background">

      <div class="carousel-container container">
        <h2 class="animate__animated animate__fadeInDown"><?= $s('hero_title', 'Bergabunglah di SMKS Laboratorium Jakarta') ?></h2>
        <h3 class="animate__animated animate__fadeInUp"><?= $s('hero_subtitle2', 'Tahun Ajaran 2026 / 2027') ?></h3>
        <p class="animate__animated animate__fadeInUp"><?= $s('hero_subtitle', 'Wujudkan impianmu bersama kami — sekolah kejuruan terpercaya dengan fasilitas modern dan tenaga pengajar berpengalaman.') ?></p>
        <a href="#jurusan" class="btn-get-started animate__animated animate__fadeInUp scrollto">Lihat Jurusan</a>
      </div>

    </section><!-- /Hero Section -->

    <!-- Announcements Banner (from DB) -->
    <?php
    // Hormati jadwal tayang (publish_at) & kadaluarsa (expire_at) + urutan custom
    try {
        $banners = $conn->query("SELECT * FROM announcements WHERE is_active=1
            AND (publish_at IS NULL OR publish_at <= NOW())
            AND (expire_at  IS NULL OR expire_at  >  NOW())
            ORDER BY urutan ASC, created_at DESC")->fetchAll();
    } catch (Throwable) {
        // Kolom jadwal belum ada (migrasi belum jalan) — fallback query lama
        $banners = $conn->query("SELECT * FROM announcements WHERE is_active=1 ORDER BY created_at DESC")->fetchAll();
    }
    if (!empty($banners)):
    ?>
      <div id="site-announcements">
        <?php foreach ($banners as $b):
          $cls = ['info' => 'alert-primary', 'warning' => 'alert-warning', 'danger' => 'alert-danger', 'success' => 'alert-success'][$b['type']] ?? 'alert-info';
        ?>
          <div class="alert <?= $cls ?> alert-dismissible fade show mb-0 rounded-0 text-center py-2 px-5" role="alert" style="font-size:.9rem;">
            <i class="bi bi-megaphone-fill me-2"></i>
            <strong><?= htmlspecialchars($b['title']) ?></strong> &mdash; <?= htmlspecialchars($b['message']) ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- About Section -->
    <section id="about" class="about section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2><?= $s('sec_about_title', 'About') ?></h2>
        <p><?= $s('sec_about_sub', 'Sekolah Kami') ?></p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4 align-items-start">

          <div class="col-lg-6 content" data-aos="fade-up" data-aos-delay="100">
            <p><?= $s('about_text', 'SMKS Laboratorium Jakarta adalah sekolah menengah kejuruan yang berdedikasi untuk menghasilkan lulusan berkompeten di bidang teknologi, kesehatan, dan kecantikan. Kami berkomitmen memberikan pendidikan berkualitas dengan menggunakan fasilitas laboratorium modern dan metode pembelajaran yang relevan dengan industri.') ?></p>
            <ul>
              <li data-aos="fade-right" data-aos-delay="150"><i class="bi bi-check2-circle"></i> <span>Program pendidikan yang sesuai dengan standar industri dan kurikulum nasional</span></li>
              <li data-aos="fade-right" data-aos-delay="250"><i class="bi bi-check2-circle"></i> <span>Fasilitas laboratorium dan praktik yang lengkap dan modern</span></li>
              <li data-aos="fade-right" data-aos-delay="350"><i class="bi bi-check2-circle"></i> <span>Tenaga pengajar berpengalaman dan profesional di bidangnya</span></li>
            </ul>
            <p class="mt-3">Dengan berbagai program keahlian yang kami tawarkan, kami mempersiapkan peserta didik untuk siap bekerja dan bersaing di era digital. Kesuksesan lulusan kami adalah bukti komitmen kami terhadap keunggulan pendidikan.</p>
            <div class="mt-2 small text-muted">
              <i class="bi bi-geo-alt-fill me-1" style="color:#198754;"></i>
              <?= $sr('sekolah_alamat') ? $s('sekolah_alamat') : 'Jl. Rawa Jaya No.37, Duren Sawit, RT008/RW004, Jakarta Timur 13460' ?>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <img src="<?= htmlspecialchars($sr('about_image', 'assets/img/gedung-sekolah.webp')) ?>" alt="Gedung <?= $s('sekolah_nama', 'SMKS Laboratorium Jakarta') ?>"
              class="img-fluid rounded shadow" style="width:100%;max-height:380px;object-fit:cover;">
            <div class="text-center mt-2 small text-muted">Gedung Sekolah Laboratorium Jakarta</div>
          </div>

        </div>

      </div>

    </section><!-- /About Section -->

    <!-- Features Section -->
    <section id="jurusan" class="features section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2><?= $s('sec_jurusan_title', 'Produktif') ?></h2>
        <p><?= $s('sec_jurusan_sub', 'Program Keahlian') ?></p>
      </div><!-- End Section Title -->


      <div class="container">

        <ul class="nav nav-tabs row  d-flex" data-aos="fade-up" data-aos-delay="100">
          <li class="nav-item col-6 col-lg-3">
            <a class="nav-link active show" data-bs-toggle="tab" data-bs-target="#features-tab-1">
              <i class="bi bi-code-slash"></i>
              <h4 class="d-none d-lg-block">RPL - Rekayasa Perangkat Lunak</h4>
            </a>
          </li>
          <li class="nav-item col-6 col-lg-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-2">
              <i class="bi bi-hdd-network"></i>
              <h4 class="d-none d-lg-block">TKJ - Teknik Komputer Jaringan</h4>
            </a>
          </li>
          <li class="nav-item col-6 col-lg-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-3">
              <i class="bi bi-heart-pulse"></i>
              <h4 class="d-none d-lg-block">AP - Asisten Keperawatan</h4>
            </a>
          </li>
          <li class="nav-item col-6 col-lg-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-4">
              <i class="bi bi-stars"></i>
              <h4 class="d-none d-lg-block">TKKR - Tata Kecantikan Kulit dan Rambut</h4>
            </a>
          </li>
        </ul><!-- End Tab Nav -->

        <div class="tab-content" data-aos="fade-up" data-aos-delay="200">

          <div class="tab-pane fade active show" id="features-tab-1">
            <!-- Baris 1: Deskripsi + Foto Lab -->
            <div class="row align-items-start mb-4">
              <div class="col-lg-7 order-2 order-lg-1 mt-3 mt-lg-0">
                <div class="d-flex align-items-center gap-3 mb-3">
                  <img src="<?= htmlspecialchars($sr('jur_rpl_foto', 'assets/img/logo-rpl-1.webp')) ?>" alt="Logo RPL" style="width:70px;height:70px;object-fit:contain;border-radius:50%;border:3px solid #e0e7ff;background:#fff;">
                  <h3 class="mb-0"><?= $s('jur_rpl_judul', 'Rekayasa Perangkat Lunak (RPL)') ?></h3>
                </div>
                <p class="fst-italic">
                  <?= $s('jur_rpl_intro', 'Program keahlian yang membekali siswa dengan kemampuan merancang, membangun, dan mengelola perangkat lunak sesuai kebutuhan dunia industri teknologi informasi yang terus berkembang.') ?>
                </p>
                <ul>
                  <?php foreach ($slist('jur_rpl_keahlian', "Pemrograman web: HTML, CSS, JavaScript, PHP, Python\nPengembangan aplikasi mobile Android & iOS\nBasis data, sistem informasi, dan UI/UX Design\nAlgoritma, struktur data, dan pemrograman berorientasi objek") as $ka): ?>
                  <li><i class="bi bi-check2-all"></i><span><?= htmlspecialchars($ka) ?></span></li>
                  <?php endforeach; ?>
                </ul>
                <?php if (trim($sr('jur_rpl_karir', 'Lulusan RPL siap berkarir sebagai software developer, web programmer, atau entrepreneur di bidang teknologi dengan bekal sertifikasi kompetensi nasional dan pengalaman proyek nyata.')) !== ''): ?>
                <p><?= $s('jur_rpl_karir', 'Lulusan RPL siap berkarir sebagai software developer, web programmer, atau entrepreneur di bidang teknologi dengan bekal sertifikasi kompetensi nasional dan pengalaman proyek nyata.') ?></p>
                <?php endif; ?>
              </div>
              <div class="col-lg-5 order-1 order-lg-2">
                <div class="row g-2">
                  <div class="col-12">
                    <div class="jurusan-logo-box" style="height:200px;">
                      <img src="<?= htmlspecialchars($sr('jur_rpl_foto', 'assets/img/logo-rpl-1.webp')) ?>" alt="Logo RPL">
                    </div>
                  </div>
                  <div class="col-6">
                    <img src="assets/img/rpl-lab-2.webp" alt="Kegiatan RPL" class="img-fluid rounded" style="width:100%;height:120px;object-fit:cover;object-position:center;">
                  </div>
                  <div class="col-6">
                    <img src="assets/img/rpl-lab-4.webp" alt="Kegiatan RPL" class="img-fluid rounded" style="width:100%;height:120px;object-fit:cover;object-position:center;">
                  </div>
                </div>
              </div>
            </div>

            <!-- Baris 2: Makna Logo -->
            <div class="mb-4 p-4 rounded-3" style="background:#f0f4ff;">
              <h5 class="fw-bold mb-3"><i class="bi bi-patch-question-fill me-2" style="color:#667eea;"></i>Makna Logo RPL</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="d-flex gap-3">
                    <div class="flex-shrink-0">
                      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;background:#667eea;color:#fff;font-size:.8rem;">1</div>
                    </div>
                    <div>
                      <strong class="small">Lingkaran Ganda</strong>
                      <p class="small text-muted mb-0">Melambangkan kesinambungan ilmu, kekompakan, dan proses belajar yang terus berjalan. Dunia teknologi berkembang tanpa henti, begitu pula semangat belajar siswa RPL.</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex gap-3">
                    <div class="flex-shrink-0">
                      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;background:#667eea;color:#fff;font-size:.8rem;">2</div>
                    </div>
                    <div>
                      <strong class="small">Warna Dominan</strong>
                      <p class="small text-muted mb-0"><span style="color:#1d4ed8;font-weight:600;">Biru (R)</span> — Logika &amp; kecerdasan. <span style="color:#dc2626;font-weight:600;">Merah (P)</span> — Semangat &amp; kreativitas. <span style="color:#ca8a04;font-weight:600;">Kuning (L)</span> — Keceriaan &amp; inovasi tanpa batas.</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex gap-3">
                    <div class="flex-shrink-0">
                      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;background:#667eea;color:#fff;font-size:.8rem;">3</div>
                    </div>
                    <div>
                      <strong class="small">Laptop di Tengah</strong>
                      <p class="small text-muted mb-0">Mewakili pusat kegiatan siswa RPL dalam membuat aplikasi, website, dan teknologi digital. Laptop menjadi simbol utama rekayasa perangkat lunak.</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex gap-3">
                    <div class="flex-shrink-0">
                      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;background:#667eea;color:#fff;font-size:.8rem;">4</div>
                    </div>
                    <div>
                      <strong class="small">Label Bahasa Pemrograman</strong>
                      <p class="small text-muted mb-0"><strong>HTML</strong> — Dasar website. <strong>CSS</strong> — Desain tampilan. <strong>Python</strong> — Bahasa serbaguna industri. <strong>&lt;/&gt;</strong> — Simbol logika komputer.</p>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="d-flex gap-3">
                    <div class="flex-shrink-0">
                      <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;background:#667eea;color:#fff;font-size:.8rem;">5</div>
                    </div>
                    <div>
                      <strong class="small">Simbol Awan</strong>
                      <p class="small text-muted mb-0">Menunjukkan dunia teknologi modern berbasis cloud computing, serta pemikiran kreatif yang luas dan terbuka terhadap perkembangan zaman.</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Baris 3: Visi & Misi -->
            <div class="row g-4 mb-4">
              <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                  <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-eye-fill me-2" style="color:#667eea;"></i>Visi Jurusan RPL</h5>
                    <p class="fst-italic text-muted mb-0">Mewujudkan lulusan Rekayasa Perangkat Lunak SMKS Laboratorium Jakarta yang unggul, berkarakter, inovatif, dan kompeten di bidang teknologi digital serta siap bersaing di dunia industri.</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                  <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2" style="color:#667eea;"></i>Misi Jurusan RPL</h5>
                    <ol class="mb-0 ps-3 small text-muted">
                      <li class="mb-2">Menyelenggarakan pembelajaran RPL yang berkualitas dan sesuai perkembangan teknologi.</li>
                      <li class="mb-2">Membekali siswa dengan keterampilan pemrograman dan pengembangan perangkat lunak berbasis industri.</li>
                      <li class="mb-2">Menanamkan sikap disiplin, kreatif, dan bertanggung jawab dalam berkarya digital.</li>
                      <li>Menyiapkan lulusan yang siap kerja, berwirausaha, dan melanjutkan pendidikan.</li>
                    </ol>
                  </div>
                </div>
              </div>
            </div>

            <!-- Baris 4: Foto Kegiatan Siswa -->
            <div>
              <h5 class="fw-bold mb-3"><i class="bi bi-camera-fill me-2" style="color:#667eea;"></i>Kegiatan Siswa RPL</h5>
              <div class="row g-2">
                <div class="col-6 col-sm-4 col-lg">
                  <img src="assets/img/rpl-lab-1.webp" alt="Kegiatan RPL" class="img-fluid rounded" style="height:160px;object-fit:cover;width:100%;">
                </div>
                <div class="col-6 col-sm-4 col-lg">
                  <img src="assets/img/rpl-lab-2.webp" alt="Kegiatan RPL" class="img-fluid rounded" style="height:160px;object-fit:cover;width:100%;">
                </div>
                <div class="col-6 col-sm-4 col-lg">
                  <img src="assets/img/rpl-lab-3.webp" alt="Kegiatan RPL" class="img-fluid rounded" style="height:160px;object-fit:cover;width:100%;">
                </div>
                <div class="col-6 col-sm-4 col-lg">
                  <img src="assets/img/rpl-lab-4.webp" alt="Kegiatan RPL" class="img-fluid rounded" style="height:160px;object-fit:cover;width:100%;">
                </div>
                <div class="col-6 col-sm-4 col-lg">
                  <img src="assets/img/rpl-lab-5.webp" alt="Kegiatan RPL" class="img-fluid rounded" style="height:160px;object-fit:cover;width:100%;">
                </div>
              </div>
            </div>
          </div><!-- End Tab Content Item -->

          <div class="tab-pane fade" id="features-tab-2">
            <div class="row align-items-start mb-4">
              <div class="col-lg-7 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3><?= $s('jur_tkj_judul', 'Teknik Komputer dan Jaringan (TKJ)') ?></h3>
                <p class="fst-italic">
                  <?= $s('jur_tkj_intro', 'Program keahlian yang mempersiapkan tenaga ahli instalasi, konfigurasi, dan pemeliharaan infrastruktur jaringan komputer serta sistem keamanan informasi di berbagai skala organisasi.') ?>
                </p>
                <ul>
                  <?php foreach ($slist('jur_tkj_keahlian', "Instalasi & konfigurasi jaringan LAN, WAN, dan WLAN\nKeamanan siber, firewall, VPN, dan proteksi data\nCloud computing, virtualisasi server, dan troubleshooting\nRouting & switching (Cisco), administrasi jaringan") as $ka): ?>
                  <li><i class="bi bi-check2-all"></i><span><?= htmlspecialchars($ka) ?></span></li>
                  <?php endforeach; ?>
                </ul>
                <?php if (trim($sr('jur_tkj_karir', 'Lulusan TKJ siap bekerja sebagai network engineer, IT support, sistem administrator, atau melanjutkan ke perguruan tinggi bidang informatika.')) !== ''): ?>
                <p><?= $s('jur_tkj_karir', 'Lulusan TKJ siap bekerja sebagai network engineer, IT support, sistem administrator, atau melanjutkan ke perguruan tinggi bidang informatika.') ?></p>
                <?php endif; ?>
              </div>
              <div class="col-lg-5 order-1 order-lg-2">
                <img src="<?= htmlspecialchars($sr('jur_tkj_foto', 'assets/img/tkj-lab-2.webp')) ?>" alt="Laboratorium Jaringan TKJ"
                  class="img-fluid rounded" style="width:100%;height:280px;object-fit:cover;object-position:center;">
              </div>
            </div>
          </div><!-- End Tab Content Item -->

          <div class="tab-pane fade" id="features-tab-3">
            <div class="row align-items-start mb-4">
              <div class="col-lg-7 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3><?= $s('jur_ap_judul', 'Asisten Keperawatan (AP)') ?></h3>
                <p class="fst-italic">
                  <?= $s('jur_ap_intro', 'Program keahlian yang mencetak tenaga asisten perawat profesional dan berkarakter, siap memberikan pelayanan kesehatan terbaik di rumah sakit, puskesmas, klinik, dan berbagai fasilitas kesehatan lainnya.') ?>
                </p>
                <ul>
                  <?php foreach ($slist('jur_ap_keahlian', "Perawatan dasar pasien & pemantauan tanda-tanda vital\nProsedur keperawatan klinis dan kegawatdaruratan\nFarmakologi dasar, dokumentasi medis & rekam medis\nEtika profesi keperawatan & komunikasi terapeutik") as $ka): ?>
                  <li><i class="bi bi-check2-all"></i><span><?= htmlspecialchars($ka) ?></span></li>
                  <?php endforeach; ?>
                </ul>
                <?php if (trim($sr('jur_ap_karir', 'Lulusan AP siap bekerja sebagai asisten tenaga kesehatan yang kompeten dengan peluang karir luas di seluruh fasilitas pelayanan kesehatan Indonesia.')) !== ''): ?>
                <p><?= $s('jur_ap_karir', 'Lulusan AP siap bekerja sebagai asisten tenaga kesehatan yang kompeten dengan peluang karir luas di seluruh fasilitas pelayanan kesehatan Indonesia.') ?></p>
                <?php endif; ?>
              </div>
              <div class="col-lg-5 order-1 order-lg-2">
                <img src="<?= htmlspecialchars($sr('jur_ap_foto', 'assets/img/ap-lab-1.webp')) ?>" alt="Siswa Asisten Keperawatan" class="img-fluid rounded shadow-sm" style="width:100%;height:280px;object-fit:cover;object-position:center;">
              </div>
            </div>
          </div><!-- End Tab Content Item -->

          <div class="tab-pane fade" id="features-tab-4">

            <!-- Baris 1: Header + foto -->
            <div class="row g-4 align-items-center mb-4">
              <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3 mb-3">
                  <h3 class="mb-0"><?= $s('jur_tkkr_judul', 'Tata Kecantikan Kulit dan Rambut (TKKR)') ?></h3>
                </div>
                <p class="fst-italic text-muted">
                  <?= $s('jur_tkkr_intro', 'Program keahlian yang mencetak tenaga profesional kecantikan kreatif dan terampil, siap bersaing di industri kecantikan nasional maupun internasional dengan bekal teknik terkini.') ?>
                </p>
                <ul class="mb-0">
                  <?php foreach ($slist('jur_tkkr_keahlian', "Perawatan & treatment kulit wajah, leher, dan tubuh\nTeknik styling, coloring, dan perawatan rambut\nTata rias pengantin, karakter, dan make-up artistik\nKosmetologi, manajemen salon & kewirausahaan kecantikan") as $ka): ?>
                  <li><i class="bi bi-check2-all"></i><span><?= htmlspecialchars($ka) ?></span></li>
                  <?php endforeach; ?>
                </ul>
                <?php if (trim($sr('jur_tkkr_karir', '')) !== ''): ?>
                <p class="mt-3"><?= $s('jur_tkkr_karir') ?></p>
                <?php endif; ?>
              </div>
              <div class="col-lg-5">
                <img src="<?= htmlspecialchars($sr('jur_tkkr_foto', 'assets/img/tkkr-siswa-1.webp')) ?>" alt="Siswa TKKR"
                  class="img-fluid rounded shadow"
                  style="width:100%;height:300px;object-fit:cover;object-position:center top;">
              </div>
            </div>

            <!-- Baris 2: Visi & Misi -->
            <div class="row g-3">
              <div class="col-md-5">
                <div class="card border-0 h-100" style="background:linear-gradient(135deg,#fdf4ff,#fae8ff);border-left:4px solid #c026d3 !important;border-radius:12px;">
                  <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:#7e22ce;">
                      <i class="bi bi-eye-fill me-2"></i>Visi
                    </h6>
                    <p class="mb-0 small" style="line-height:1.7;">
                      Menghasilkan lulusan yang siap kerja, siap berwirausaha, serta mampu beradaptasi dengan perkembangan industri kecantikan modern.
                    </p>
                  </div>
                </div>
              </div>
              <div class="col-md-7">
                <div class="card border-0 h-100" style="background:linear-gradient(135deg,#fdf4ff,#fae8ff);border-left:4px solid #a855f7 !important;border-radius:12px;">
                  <div class="card-body p-4">
                    <h6 class="fw-bold mb-3" style="color:#7e22ce;">
                      <i class="bi bi-list-check me-2"></i>Misi
                    </h6>
                    <ul class="mb-0 small ps-3" style="line-height:1.8;">
                      <li>Membekali peserta didik dengan keterampilan perawatan kulit, perawatan rambut, dan tata kecantikan sesuai standar industri.</li>
                      <li>Menanamkan sikap disiplin, tanggung jawab, kreativitas, serta etika profesi.</li>
                      <li>Mengikuti perkembangan teknologi dan tren kecantikan modern guna meningkatkan kompetensi peserta didik.</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

          </div><!-- End Tab Content Item -->

        </div>

      </div>

    </section><!-- /Features Section -->

    <!-- Lokasi Section -->
    <section id="lokasi" class="section dark-background">
      <div class="container" data-aos="fade-up">
        <div class="section-title">
          <h2 class="text-white"><?= $s('sec_lokasi_title', 'Lokasi') ?></h2>
          <p class="text-white"><?= $s('sec_lokasi_sub', 'Sekolah Laboratorium Jakarta') ?></p>
        </div>
        <div class="map-container" data-aos="zoom-in" data-aos-delay="200" style="width: 100%; height: 450px; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
          <iframe src="<?= htmlspecialchars($sr('maps_embed_url', 'https://maps.google.com/maps?q=-6.2350331,106.9439031&z=17&output=embed')) ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <div class="text-center mt-4" data-aos="fade-up" data-aos-delay="300">
          <a href="https://maps.app.goo.gl/ZdjZz5CfwK7nzNrG8" target="_blank" class="btn btn-outline-light"><i class="bi bi-geo-alt-fill me-2"></i> Buka di Google Maps</a>
        </div>
      </div>
    </section><!-- /Lokasi Section -->

    <!-- Cara Mendaftar Section -->
    <section id="cara-mendaftar" class="section" style="background: #f8f9fa;">
      <div class="container section-title" data-aos="fade-up">
        <h2><?= $s('sec_daftar_title', 'Cara Mendaftar') ?></h2>
        <p><?= $s('sec_daftar_sub', 'Informasi Lengkap SPMB SMKS Laboratorium Jakarta') ?></p>
        <p class="section-subtitle"><?= $s('sec_daftar_sub2', 'Datang Langsung Ke SMKS Laboratorium Jakarta') ?></p>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <?php
        $cara_gel = $conn->query("SELECT * FROM gelombang ORDER BY gelombang")->fetchAll();
        ?>

        <!-- Jadwal Gelombang -->
        <div class="row g-4 mb-4">
          <?php foreach ($cara_gel as $idx => $g): ?>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="<?= $idx * 150 + 100 ?>">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body p-4">
                  <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                      style="width:48px; height:48px; background:#198754; color:#fff; font-weight:700; font-size:1.3rem;">
                      <?= $g['gelombang'] ?>
                    </div>
                    <h4 class="mb-0 fw-bold">Gelombang <?= $g['gelombang'] ?></h4>
                  </div>
                  <div class="mb-3">
                    <div class="d-flex align-items-center mb-1"><i class="bi bi-calendar-event text-success me-2"></i><strong>Pendaftaran</strong></div>
                    <div class="ms-4 small" style="white-space: pre-line;"><?= htmlspecialchars($g['jadwal_pendaftaran_text'] ?? '') ?></div>
                  </div>
                  <div class="mb-3">
                    <div class="d-flex align-items-center mb-1"><i class="bi bi-megaphone text-success me-2"></i><strong>Pengumuman</strong></div>
                    <div class="ms-4 small" style="white-space: pre-line;"><?= htmlspecialchars($g['jadwal_pengumuman_text'] ?? '') ?></div>
                  </div>
                  <div>
                    <div class="d-flex align-items-center mb-1"><i class="bi bi-clipboard-check text-success me-2"></i><strong>Daftar Ulang</strong></div>
                    <div class="ms-4 small" style="white-space: pre-line;"><?= htmlspecialchars($g['jadwal_daftar_ulang_text'] ?? '') ?></div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Alur Pendaftaran dari DB -->
        <?php
        $tahapan_pub = $conn->query("SELECT id, nama, icon, deskripsi FROM tahapan WHERE is_active=1 ORDER BY urutan")->fetchAll();
        if (!empty($tahapan_pub)):
        ?>
          <div class="mb-4" data-aos="fade-up" data-aos-delay="150">
            <h5 class="fw-bold mb-3 text-center"><i class="bi bi-list-ol text-success me-2"></i>Alur Pendaftaran</h5>
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
              <?php foreach ($tahapan_pub as $i => $t): ?>
                <?php if ($i > 0): ?>
                  <div class="text-success d-none d-sm-block" style="font-size:1.4rem;"><i class="bi bi-chevron-right"></i></div>
                <?php endif; ?>
                <div class="card border-0 shadow-sm text-center" data-aos="zoom-in" data-aos-delay="<?= $i * 80 + 100 ?>" style="width:150px;min-height:110px;">
                  <div class="card-body py-3 px-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2"
                      style="width:42px;height:42px;background:#198754;color:#fff;font-size:1.1rem;">
                      <i class="bi <?= htmlspecialchars($t['icon']) ?>"></i>
                    </div>
                    <div class="fw-semibold" style="font-size:.82rem;line-height:1.3;"><?= htmlspecialchars($t['nama']) ?></div>
                    <?php if ($t['deskripsi']): ?>
                      <div class="text-muted mt-1" style="font-size:.72rem;"><?= htmlspecialchars(mb_substr($t['deskripsi'], 0, 55)) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Berkas yang Dibawa -->
        <div class="row justify-content-center">
          <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
            <div class="card shadow-sm border-0">
              <div class="card-body p-4">
                <h4 class="fw-bold mb-3"><i class="bi bi-folder2-open text-success me-2"></i>Berkas Photocopy yang Dibawa</h4>
                <p class="text-muted small"><?= $s('syarat_intro', 'Calon siswa datang langsung ke sekolah dengan membawa fotocopy:') ?></p>
                <ul class="list-group list-group-flush">
                  <?php foreach ($slist('syarat_list', "Kartu Keluarga (KK) DKI Jakarta (cut off 15 Juni 2025)\nNilai Raport semester 1 - 5\nNISN (Nomor Induk Siswa Nasional)\nAkte Kelahiran\nKTP Orang Tua\nWajib Membawa Surat Keterangan Tidak Buta warna(Puskesmas/Klinik)\nMap Kertas TKJ (Hijau), RPL (Merah), Asisten Keperawatan (Biru), Tata Kecantikan (Kuning)") as $syr): ?>
                  <li class="list-group-item border-0 ps-0"><i class="bi bi-check-circle-fill text-success me-2"></i><?= htmlspecialchars($syr) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section><!-- /Cara Mendaftar Section -->

    <!-- Pengumuman Penerimaan Section -->
    <section id="pengumuman" class="contact section">

      <div class="container section-title" data-aos="fade-up">
        <h2><?= $s('sec_pengumuman_title', 'Pengumuman Penerimaan') ?></h2>
        <p><?= $s('sec_pengumuman_sub', 'Hasil Seleksi SPMB SMKS Laboratorium Jakarta') ?></p>
      </div>

      <div class="container" data-aos="fade" data-aos-delay="100">
        <?php
        $gel_rows = $conn->query("SELECT * FROM gelombang WHERE is_published=1 ORDER BY gelombang")->fetchAll();
        $short_j  = [
          'Rekayasa Perangkat Lunak (RPL)'          => 'RPL',
          'Teknik Komputer dan Jaringan (TKJ)'       => 'TKJ',
          'Asisten Keperawatan (AP)'                 => 'AP',
          'Tata Kecantikan Kulit dan Rambut (TKKR)'  => 'TKKR',
        ];

        if (empty($gel_rows)):
        ?>
          <div class="row justify-content-center">
            <div class="col-lg-7 text-center py-5">
              <i class="bi bi-clock-history" style="font-size:3rem;color:#6c757d"></i>
              <h5 class="mt-3 text-muted">Pengumuman belum tersedia</h5>
              <p class="text-muted">Hasil seleksi akan diumumkan sesuai jadwal yang telah ditetapkan.</p>
            </div>
          </div>

          <?php else: foreach ($gel_rows as $g):
            $hasil_live = !empty($g['is_hasil_published']);
            $list = [];
            if ($hasil_live) {
              $diterima = $conn->prepare("SELECT no_pendaftaran, nama, nisn, jurusan FROM pendaftar
                    WHERE gelombang=? AND status='terima' ORDER BY jurusan, nilai_akhir DESC");
              $diterima->execute([$g['gelombang']]);
              $list = $diterima->fetchAll();
            }
          ?>
            <div class="mb-5" data-aos="fade-up">
              <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                <h4 class="mb-0 fw-bold">Gelombang <?= $g['gelombang'] ?></h4>
                <?php if ($hasil_live): ?>
                  <span class="badge bg-success px-3 py-2">
                    <i class="bi bi-trophy me-1"></i>Hasil Resmi
                  </span>
                <?php else: ?>
                  <span class="badge bg-info text-dark px-3 py-2">
                    <i class="bi bi-broadcast me-1"></i>Sedang Berjalan
                  </span>
                <?php endif; ?>
                <span class="text-muted small">
                  Tanggal pengumuman: <?= date('d F Y', strtotime($g['tanggal_pengumuman'])) ?>
                </span>
              </div>

              <?php if (!$hasil_live): ?>
                <!-- Tahap 1: Banner — pengumuman dibuka, hasil belum ada -->
                <div class="alert alert-info">
                  <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-megaphone-fill fs-4 flex-shrink-0 mt-1"></i>
                    <div>
                      <strong>Pendaftaran Gelombang <?= $g['gelombang'] ?> Sedang Berlangsung</strong>
                      <p class="mb-1 mt-1">
                        Periode pendaftaran: <strong><?= date('d M Y', strtotime($g['tanggal_buka'])) ?></strong>
                        s/d <strong><?= date('d M Y', strtotime($g['tanggal_tutup'])) ?></strong>
                      </p>
                      <?php if ($g['jadwal_pengumuman_text']): ?>
                        <p class="mb-0 small">
                          <i class="bi bi-calendar-check me-1"></i>
                          Pengumuman hasil: <strong><?= htmlspecialchars($g['jadwal_pengumuman_text']) ?></strong>
                        </p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php if ($g['tanggal_daftar_ulang_mulai']): ?>
                  <p class="text-muted small">
                    <i class="bi bi-arrow-right-circle me-1"></i>
                    Daftar ulang bagi yang diterima: <strong><?= date('d M Y', strtotime($g['tanggal_daftar_ulang_mulai'])) ?></strong>
                    <?php if ($g['tanggal_daftar_ulang_selesai'] && $g['tanggal_daftar_ulang_selesai'] !== $g['tanggal_daftar_ulang_mulai']): ?>
                      s/d <strong><?= date('d M Y', strtotime($g['tanggal_daftar_ulang_selesai'])) ?></strong>
                    <?php endif; ?>
                  </p>
                <?php endif; ?>

              <?php else: ?>
                <!-- Tahap 2: Hasil penerimaan live -->
                <div class="alert alert-success py-2 small mb-3">
                  <i class="bi bi-info-circle me-1"></i>
                  Periode pendaftaran: <strong><?= date('d M Y', strtotime($g['tanggal_buka'])) ?></strong>
                  s/d <strong><?= date('d M Y', strtotime($g['tanggal_tutup'])) ?></strong> |
                  Total diterima: <strong><?= count($list) ?></strong> pendaftar
                </div>

                <?php if (empty($list)): ?>
                  <p class="text-muted">Belum ada data penerimaan untuk gelombang ini.</p>
                <?php else: ?>
                  <!-- Search + Filter jurusan -->
                  <?php
                  $glm_id = 'glm' . $g['gelombang'];
                  $per_jurusan = [];
                  foreach ($list as $r) {
                    $kd = $short_j[$r['jurusan']] ?? $r['jurusan'];
                    $per_jurusan[$kd] = ($per_jurusan[$kd] ?? 0) + 1;
                  }
                  ?>
                  <input type="text" id="cari-<?= $glm_id ?>" placeholder="Cari nama atau NISN..." class="form-control form-control-sm mb-2" style="max-width:280px;">
                  <div class="d-flex flex-wrap gap-2 mb-3" id="filter-<?= $glm_id ?>">
                    <button class="btn btn-sm btn-dark filter-btn active" data-filter="all">Semua</button>
                    <?php foreach ($per_jurusan as $kd => $cnt): ?>
                      <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="<?= $kd ?>"><?= $kd ?></button>
                    <?php endforeach; ?>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tbl-<?= $glm_id ?>">
                      <thead class="table-dark">
                        <tr>
                          <th>Nama</th>
                          <th>NISN</th>
                          <th>Jurusan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($list as $r):
                          $kd = $short_j[$r['jurusan']] ?? $r['jurusan'];
                        ?>
                          <tr data-jurusan="<?= $kd ?>" data-nama="<?= strtolower(htmlspecialchars($r['nama'])) ?>" data-nisn="<?= htmlspecialchars($r['nisn']) ?>">
                            <td data-label="Nama" class="fw-semibold"><?= htmlspecialchars($r['nama']) ?></td>
                            <td data-label="NISN"><?= htmlspecialchars($r['nisn']) ?></td>
                            <td data-label="Jurusan">
                              <span class="badge bg-primary"><?= $kd ?></span>
                              <span class="ms-1 small text-muted d-none d-sm-inline"><?= htmlspecialchars($r['jurusan']) ?></span>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>

                  <!-- Arahan Selanjutnya -->
                  <div class="card border-success mt-4 mb-2">
                    <div class="card-header bg-success text-white fw-bold"><i class="bi bi-check2-circle me-2"></i>Arahan Selanjutnya</div>
                    <div class="card-body">
                      <p class="mb-2">Jika nama Anda tercantum dalam daftar di atas, segera lakukan <strong>daftar ulang</strong> ke sekolah.</p>
                      <?php if (!empty($g['tanggal_daftar_ulang_mulai'])): ?>
                        <div class="alert alert-info py-2 mb-2">
                          <i class="bi bi-calendar-event me-1"></i>
                          Periode daftar ulang: <strong><?= date('d M Y', strtotime($g['tanggal_daftar_ulang_mulai'])) ?></strong>
                          <?php if (!empty($g['tanggal_daftar_ulang_selesai']) && $g['tanggal_daftar_ulang_selesai'] !== $g['tanggal_daftar_ulang_mulai']): ?>
                            s/d <strong><?= date('d M Y', strtotime($g['tanggal_daftar_ulang_selesai'])) ?></strong>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                      <p class="mb-1 fw-semibold">Dokumen yang harus dibawa:</p>
                      <ul class="mb-2">
                        <li>Ijazah / Surat Keterangan Lulus (SKL) asli dan fotokopi</li>
                        <li>SKHUN (Surat Keterangan Hasil Ujian Nasional) asli dan fotokopi</li>
                        <li>Pas foto terbaru ukuran 3×4 (4 lembar)</li>
                        <li>Kartu Keluarga (KK) asli dan fotokopi</li>
                        <li>Akta kelahiran fotokopi</li>
                      </ul>
                      <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i>Pendaftaran yang tidak dilengkapi dokumen pada batas waktu dianggap mengundurkan diri.</p>
                    </div>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
        <?php endforeach;
        endif; ?>
      </div>

    </section><!-- /Pengumuman Section -->

  </main>

  <footer id="footer" class="footer dark-background">
    <div class="container">
      <?php
      // Kontak & sosmed — hanya tampil jika minimal satu diisi (default kosong = footer seperti semula)
      $ft_telp  = trim($sr('sekolah_telp'));
      $ft_email = trim($sr('sekolah_email'));
      $ft_sosmed = array_filter([
          'instagram' => trim($sr('sosmed_instagram')),
          'facebook'  => trim($sr('sosmed_facebook')),
          'youtube'   => trim($sr('sosmed_youtube')),
          'tiktok'    => trim($sr('sosmed_tiktok')),
      ]);
      if ($ft_telp || $ft_email || $ft_sosmed): ?>
      <div class="d-flex flex-wrap justify-content-center align-items-center gap-3 mb-3" style="font-size:.9rem;">
        <?php if ($ft_telp): ?>
        <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $ft_telp)) ?>" class="text-decoration-none" style="color:inherit;">
          <i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($ft_telp) ?>
        </a>
        <?php endif; ?>
        <?php if ($ft_email): ?>
        <a href="mailto:<?= htmlspecialchars($ft_email) ?>" class="text-decoration-none" style="color:inherit;">
          <i class="bi bi-envelope-fill me-1"></i><?= htmlspecialchars($ft_email) ?>
        </a>
        <?php endif; ?>
        <?php if ($ft_sosmed): ?>
        <span class="d-inline-flex gap-3 fs-5">
          <?php foreach ($ft_sosmed as $sm_name => $sm_url): ?>
          <a href="<?= htmlspecialchars($sm_url) ?>" target="_blank" rel="noopener" style="color:inherit;" title="<?= ucfirst($sm_name) ?>">
            <i class="bi bi-<?= $sm_name ?>"></i>
          </a>
          <?php endforeach; ?>
        </span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <div class="copyright">
        <span>&copy; <?= date('Y') ?></span> <strong class="px-1 sitename"><?= $s('sekolah_nama', 'SMKS Laboratorium Jakarta') ?></strong> <span>All Rights Reserved</span>
      </div>
      <?php if ($sr('footer_text')): ?>
      <div class="credits"><?= $s('footer_text') ?></div>
      <?php endif; ?>
    </div>
  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <!-- Cache buster: ensures browser reloads the latest JS after edits -->
  <script src="assets/js/main.js?v=20260522"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const observerOptions = {
        threshold: 0.3,
        rootMargin: '-100px 0px -66%'
      };
      const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const id = entry.target.getAttribute('id');
            document.querySelectorAll('nav.navmenu .nav-link').forEach(l => l.classList.remove('active'));
            const active = document.querySelector(`nav.navmenu a[href="#${id}"]`);
            if (active) active.classList.add('active');
          }
        });
      }, observerOptions);
      document.querySelectorAll('#home, #about, #jurusan, #cara-mendaftar, #pengumuman').forEach(s => observer.observe(s));

      // Animasi entrance + AOS refresh saat pindah tab jurusan
      document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', (e) => {
          const pane = document.querySelector(e.target.dataset.bsTarget);
          if (pane) {
            pane.classList.remove('tab-pane-animate');
            void pane.offsetWidth; // force reflow agar animasi restart
            pane.classList.add('tab-pane-animate');
          }
          if (typeof AOS !== 'undefined') AOS.refresh();
        });
      });
    });

    // Filter jurusan + search di tabel pengumuman hasil
    function applyPengumumanFilter(glmId) {
      const group = document.getElementById('filter-' + glmId);
      const tbl = document.getElementById('tbl-' + glmId);
      const search = document.getElementById('cari-' + glmId);
      if (!group || !tbl) return;
      const activeBtn = group.querySelector('.filter-btn.active');
      const filter = activeBtn ? activeBtn.dataset.filter : 'all';
      const q = search ? search.value.toLowerCase().trim() : '';
      tbl.querySelectorAll('tbody tr').forEach(row => {
        const matchJ = filter === 'all' || row.dataset.jurusan === filter;
        const matchQ = !q || row.dataset.nama.includes(q) || row.dataset.nisn.includes(q);
        row.style.display = (matchJ && matchQ) ? '' : 'none';
      });
    }
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const group = this.closest('[id^="filter-"]');
        group.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active', 'btn-dark', 'btn-primary'));
        this.classList.add('active');
        if (this.dataset.filter === 'all') this.classList.add('btn-dark');
        else this.classList.add('btn-primary');
        const glmId = group.id.replace('filter-', '');
        applyPengumumanFilter(glmId);
      });
    });
    document.querySelectorAll('[id^="cari-"]').forEach(input => {
      input.addEventListener('input', function() {
        const glmId = this.id.replace('cari-', '');
        applyPengumumanFilter(glmId);
      });
    });

    // Pastikan AOS aktif dengan duration lebih panjang agar animasi scroll terlihat jelas
    window.addEventListener('load', function() {
      if (typeof AOS !== 'undefined') {
        AOS.init({
          duration: 900,
          easing: 'ease-in-out',
          once: true,
          mirror: false,
          offset: 80
        });
      }
    });

    // Tidak perlu JS tambahan — frosted glass dihandle lewat .scrolled .header di CSS
  </script>

</body>

</html>