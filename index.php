<?php
require_once __DIR__ . '/../src/conn.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Pendaftaran - SMK Lab Jakarta</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/smk.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

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
  <link href="assets/css/main.css?v=20260117" rel="stylesheet">

  <style>
    /* ============================================================
       ENHANCEMENTS INLINE STYLES: Dark Mode, Mobile, Form Validation
       ============================================================ */

    /* DARK MODE SYSTEM */
    :root {
      --enable-dark-mode: 0;
    }

    [data-theme="dark"] {
      --background-color: #1a1a1a;
      --default-color: #e0e0e0;
      --heading-color: #ffffff;
      --accent-color: #66bb6a;
      --surface-color: #2d2d2d;
      --contrast-color: #ffffff;
      --nav-color: #e0e0e0;
      --nav-hover-color: #66bb6a;
      --nav-hover-bg: rgba(102, 187, 106, 0.1);
      --nav-mobile-background-color: #1a1a1a;
      --nav-dropdown-background-color: #2d2d2d;
      --nav-dropdown-color: #e0e0e0;
      --nav-dropdown-hover-color: #66bb6a;
    }

    body[data-theme="dark"] {
      background-color: var(--background-color);
      color: var(--default-color);
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    body[data-theme="dark"] .navbar,
    body[data-theme="dark"] nav,
    body[data-theme="dark"] header {
      background-color: var(--surface-color) !important;
      border-color: #3a3a3a;
    }

    body[data-theme="dark"] .card {
      background-color: var(--surface-color);
      border-color: #3a3a3a;
      color: var(--default-color);
    }

    body[data-theme="dark"] .form-control,
    body[data-theme="dark"] .form-select {
      background-color: #2d2d2d;
      border-color: #3a3a3a;
      color: var(--default-color);
    }

    body[data-theme="dark"] .form-control:focus,
    body[data-theme="dark"] .form-select:focus {
      background-color: #2d2d2d;
      border-color: var(--accent-color);
      color: var(--default-color);
      box-shadow: 0 0 0 0.2rem rgba(102, 187, 106, 0.25);
    }

    body[data-theme="dark"] .table {
      color: var(--default-color);
      border-color: #3a3a3a;
    }

    body[data-theme="dark"] .modal-content {
      background-color: var(--surface-color);
      border-color: #3a3a3a;
    }

    .dark-mode-toggle {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 1.5rem;
      color: var(--nav-color);
      transition: color 0.3s ease;
      padding: 5px 10px;
      border-radius: 5px;
    }

    .dark-mode-toggle:hover {
      color: var(--accent-color);
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
      *,
      *::before,
      *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
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
      color: #667eea !important;
    }

    .nav-link.active {
      color: #667eea !important;
      border-bottom: 3px solid #667eea;
      padding-bottom: 5px;
    }

    /* Dark Mode Toggle Button */
    .dark-mode-toggle {
      background: none;
      border: none;
      color: #667eea;
      font-size: 20px;
      padding: 5px 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
    }

    .dark-mode-toggle:hover {
      color: #667eea;
      transform: scale(1.1);
    }

    [data-theme="dark"] .dark-mode-toggle {
      color: #ffc107;
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
        <img class="sitename" src="assets/img/smk.png" style="max-height: 50px;">
        <h1>SMK Laboratorium Jakarta</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="#home" class="nav-link">🏠 Home</a></li>
          <li><a href="#about" class="nav-link">ℹ️ Tentang Kami</a></li>
          <li><a href="#jurusan" class="nav-link">📚 Jurusan</a></li>
          <li><a href="#daftar" class="nav-link">📝 Buat Akun</a></li>
          <li><a href="login.php">🔑 Login</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

    </div>
  </header>

  <main class="main">

    <!-- Hero Section -->
    <section id="home" class="hero section dark-background">

      <div id="hero-carousel" data-bs-interval="5000" class="container carousel carousel-fade" data-bs-ride="carousel">

        <!-- Slide 1 -->
        <div class="carousel-item active">
          <div class="carousel-container">
            <h2 class="animate__animated animate__fadeInDown">Selamat Datang di <span>Pendaftaran SMK</span></h2>
            <h3 class="animate__animated animate__fadeInUp">Tahun Ajaran 2026 / 2027</h3>
            <p class="animate__animated animate__fadeInUp">Segera daftarkan dirimu untuk bergabung dengan SMK Laboratorium Jakarta dan raih masa depan cerah bersama kami!</p>
            <a href="#about" class="btn-get-started animate__animated animate__fadeInUp scrollto">Read More</a>
          </div>
        </div>
      </div>

    </section><!-- /Hero Section -->

    <!-- About Section -->
    <section id="about" class="about section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>About</h2>
        <p>Sekolah Kami</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-6 content" data-aos="fade-up" data-aos-delay="100">
            <p>
              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore
              magna aliqua.
            </p>
            <ul>
              <li><i class="bi bi-check2-circle"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat.</span></li>
              <li><i class="bi bi-check2-circle"></i> <span>Duis aute irure dolor in reprehenderit in voluptate velit.</span></li>
              <li><i class="bi bi-check2-circle"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo</span></li>
            </ul>
          </div>

          <div class="col-lg-6" data-aos="fade-up" data-aos-delay="200">
            <p>Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. </p>
          </div>

        </div>

      </div>

    </section><!-- /About Section -->

    <!-- Features Section -->
    <section id="jurusan" class="features section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Productif</h2>
        <p>Jurusan Kami</p>
      </div><!-- End Section Title -->


      <div class="container">

        <ul class="nav nav-tabs row  d-flex" data-aos="fade-up" data-aos-delay="100">
          <li class="nav-item col-3">
            <a class="nav-link active show" data-bs-toggle="tab" data-bs-target="#features-tab-1">
              <i class="bi bi-binoculars"></i>
              <h4 class="d-none d-lg-block">RPL - Rekayasa Perangkat Lunak</h4>
            </a>
          </li>
          <li class="nav-item col-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-2">
              <i class="bi bi-box-seam"></i>
              <h4 class="d-none d-lg-block">TKJ - Teknik Komputer Jaringan</h4>
            </a>
          </li>
          <li class="nav-item col-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-3">
              <i class="bi bi-brightness-high"></i>
              <h4 class="d-none d-lg-block">AP - Asisten Keperawatan</h4>
            </a>
          </li>
          <li class="nav-item col-3">
            <a class="nav-link" data-bs-toggle="tab" data-bs-target="#features-tab-4">
              <i class="bi bi-command"></i>
              <h4 class="d-none d-lg-block">TKKR - Tata Kecantikan Kulit dan Rambut</h4>
            </a>
          </li>
        </ul><!-- End Tab Nav -->

        <div class="tab-content" data-aos="fade-up" data-aos-delay="200">

          <div class="tab-pane fade active show" id="features-tab-1">
            <div class="row">
              <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3>Voluptatem dignissimos provident quasi corporis voluptates sit assumenda.</h3>
                <p class="fst-italic">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore
                  magna aliqua.
                </p>
                <ul>
                  <li><i class="bi bi-check2-all"></i>
                    <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat.</span>
                  </li>
                  <li><i class="bi bi-check2-all"></i> <span>Duis aute irure dolor in reprehenderit in voluptate velit</span>.</li>
                  <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate trideta storacalaperda mastiro dolore eu fugiat nulla pariatur.</span></li>
                </ul>
                <p>
                  Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate
                  velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                  culpa qui officia deserunt mollit anim id est laborum
                </p>
              </div>
              <div class="col-lg-6 order-1 order-lg-2 text-center">
                <img src="assets/img/working-1.jpg" alt="" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content Item -->

          <div class="tab-pane fade" id="features-tab-2">
            <div class="row">
              <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3>Neque exercitationem debitis soluta quos debitis quo mollitia officia est</h3>
                <p>
                  Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate
                  velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                  culpa qui officia deserunt mollit anim id est laborum
                </p>
                <p class="fst-italic">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore
                  magna aliqua.
                </p>
                <ul>
                  <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat.</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Duis aute irure dolor in reprehenderit in voluptate velit.</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Provident mollitia neque rerum asperiores dolores quos qui a. Ipsum neque dolor voluptate nisi sed.</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate trideta storacalaperda mastiro dolore eu fugiat nulla pariatur.</span></li>
                </ul>
              </div>
              <div class="col-lg-6 order-1 order-lg-2 text-center">
                <img src="assets/img/working-2.jpg" alt="" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content Item -->

          <div class="tab-pane fade" id="features-tab-3">
            <div class="row">
              <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3>Voluptatibus commodi ut accusamus ea repudiandae ut autem dolor ut assumenda</h3>
                <p>
                  Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate
                  velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                  culpa qui officia deserunt mollit anim id est laborum
                </p>
                <ul>
                  <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat.</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Duis aute irure dolor in reprehenderit in voluptate velit.</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Provident mollitia neque rerum asperiores dolores quos qui a. Ipsum neque dolor voluptate nisi sed.</span></li>
                </ul>
                <p class="fst-italic">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore
                  magna aliqua.
                </p>
              </div>
              <div class="col-lg-6 order-1 order-lg-2 text-center">
                <img src="assets/img/working-3.jpg" alt="" class="img-fluid">
              </div>
            </div>
          </div><!-- End Tab Content Item -->

          <div class="tab-pane fade" id="features-tab-4">
            <div class="row">
              <div class="col-lg-6 order-2 order-lg-1 mt-3 mt-lg-0">
                <h3>Omnis fugiat ea explicabo sunt dolorum asperiores sequi inventore rerum</h3>
                <p>
                  Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate
                  velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in
                  culpa qui officia deserunt mollit anim id est laborum
                </p>
                <p class="fst-italic">
                  Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore
                  magna aliqua.
                </p>
                <ul>
                  <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat.</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Duis aute irure dolor in reprehenderit in voluptate velit.</span></li>
                  <li><i class="bi bi-check2-all"></i> <span>Ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate trideta storacalaperda mastiro dolore eu fugiat nulla pariatur.</span></li>
                </ul>
              </div>
              <div class="col-lg-6 order-1 order-lg-2 text-center">
                <img src="assets/img/working-4.jpg" alt="" class="img-fluid">
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
          <h2 class="text-white">Lokasi</h2>
          <p class="text-white">Jl. Rawa Jaya No.37, Pd. Kopi, Kec. Duren Sawit, Kota Jakarta Timur</p>
        </div>
        <div class="map-container" data-aos="zoom-in" data-aos-delay="200" style="width: 100%; height: 450px; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.6);">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.217658585353!2d106.94115297576462!3d-6.235014561059455!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e698cecdd9893b7%3A0xb42576feb885ccd3!2sTK%20SD%20SMP%20SMK%20Laboratorium%20Islamic%20Technology%20Jakarta!5e0!3m2!1sid!2sid!4v1769870240589!5m2!1sid!2sid" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <div class="text-center mt-4" data-aos="fade-up" data-aos-delay="300">
          <a href="https://maps.app.goo.gl/5W5phhTxe5JfTB7p6" target="_blank" class="btn btn-outline-light"><i class="bi bi-geo-alt-fill me-2"></i> Buka di Google Maps</a>
        </div>
      </div>
    </section><!-- /Lokasi Section -->

    <!-- Contact Section -->
    <section id="daftar" class="contact section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Daftar</h2>
        <p>Pendaftaran Sekolah SMK</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade" data-aos-delay="100">

        <div class="row gy-4 justify-content-center">

          <div class="col-lg-8">
            <form action="register.php" method="post" class="php-email-form" data-aos="fade-up" data-aos-delay="200">
              <div class="row gy-4">

                <div class="col-md-12">
                  <input type="text" name="name" id="reg_nama" class="form-control" placeholder="Nama Lengkap" required="">
                  <div id="name_feedback" class="small mt-1" style="min-height: 20px;"></div>
                </div>

                <div class="col-md-6">
                  <div class="input-group">
                    <input type="password" name="password" id="reg_password" class="form-control" placeholder="Password" required="" minlength="8">
                    <button class="btn btn-outline-secondary" type="button" id="toggleRegPassword">
                      <i class="bi bi-eye-slash"></i>
                    </button>
                  </div>
                  <div class="progress mt-1" style="height: 5px;">
                    <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <ul class="list-unstyled small mt-1 mb-0" id="password-requirements" style="font-size: 0.75rem;">
                    <li id="req-length" class="text-danger"><i class="bi bi-x"></i> Minimal 8 karakter</li>
                    <li id="req-upper" class="text-danger"><i class="bi bi-x"></i> Huruf Besar (A-Z)</li>
                    <li id="req-number" class="text-danger"><i class="bi bi-x"></i> Angka (0-9)</li>
                  </ul>
                </div>

                <div class="col-md-6">
                  <div class="input-group">
                    <input type="password" name="password_verify" id="reg_password_verify" class="form-control" placeholder="Verify Password" required="" minlength="8">
                    <button class="btn btn-outline-secondary" type="button" id="toggleRegPasswordVerify">
                      <i class="bi bi-eye-slash"></i>
                    </button>
                  </div>
                  <div id="password_match_feedback" class="small mt-1" style="min-height: 20px;"></div>
                </div>

                <div class="col-md-12">
                  <div class="input-group">
                    <input type="email" class="form-control" name="email" id="email_daftar" placeholder="Email Aktif" required="">
                    <button class="btn btn-outline-secondary" type="button" id="btnCheckEmail"><i class="bi bi-check-circle"></i> Cek</button>
                  </div>
                  <div id="email_feedback" class="small mt-1" style="min-height: 20px;"></div>
                </div>
                <div class="col-md-6">
                  <img src="captcha.php" alt="Captcha" class="img-fluid" style="border-radius: 4px; width: 100%; height: 45px; object-fit: cover; border: 1px solid #dee2e6;">
                </div>

                <div class="col-md-6">
                  <input type="text" name="captcha" class="form-control" placeholder="Kode Captcha" required="">
                </div>

                <div class="col-md-12 text-center">
                  <div class="loading">Loading</div>
                  <div class="error-message"></div>
                  <div class="sent-message">Pendaftaran berhasil. Silakan cek email Anda untuk verifikasi akun sebelum login.</div>
                  <button type="submit" disabled>Register</button>
                </div>

              </div>
            </form>
          </div><!-- End Contact Form -->

        </div>

      </div>

    </section><!-- /Contact Section -->

  </main>

  <footer id="footer" class="footer dark-background">
    <div class="container">
      <div class="copyright">
        <span>Copyright</span> <strong class="px-1 sitename">Selecao</strong> <span>All Rights Reserved</span>
      </div>
      <div class="credits">
        <!-- All the links in the footer should remain intact. -->
        <!-- You can delete the links only if you've purchased the pro version. -->
        <!-- Licensing information: https://bootstrapmade.com/license/ -->
        <!-- Purchase the pro version with working PHP/AJAX contact form: [buy-url] -->
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a> Distributed By <a href="https://themewagon.com">ThemeWagon</a>
      </div>
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
  <script src="assets/js/main.js?v=20260117"></script>

  <script>
    // ============================================================
    // ENHANCEMENTS INLINE SCRIPTS
    // ============================================================

    // DARK MODE MANAGER CLASS
    class DarkModeManager {
      constructor() {
        this.themeKey = 'theme-preference';
        this.mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        this.init();
      }

      init() {
        const savedTheme = localStorage.getItem(this.themeKey);
        
        if (savedTheme) {
          this.setTheme(savedTheme);
        } else {
          const systemTheme = this.mediaQuery.matches ? 'dark' : 'light';
          this.setTheme(systemTheme);
        }

        this.mediaQuery.addEventListener('change', (e) => {
          const newTheme = e.matches ? 'dark' : 'light';
          this.setTheme(newTheme);
        });

        this.createToggleButton();
      }

      setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(this.themeKey, theme);
        this.updateToggleButtonIcon(theme);
      }

      getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
      }

      toggleTheme() {
        const currentTheme = this.getTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
      }

      createToggleButton() {
        const nav = document.querySelector('nav.navmenu');
        if (!nav) return;

        const toggleButton = document.createElement('button');
        toggleButton.className = 'dark-mode-toggle';
        toggleButton.type = 'button';
        toggleButton.setAttribute('aria-label', 'Toggle dark mode');
        toggleButton.title = 'Toggle Theme';
        
        this.updateToggleButtonIcon(this.getTheme());
        toggleButton.addEventListener('click', (e) => {
          e.preventDefault();
          this.toggleTheme();
        });

        const navbarEnd = nav.querySelector('ul');
        if (navbarEnd) {
          const li = document.createElement('li');
          li.appendChild(toggleButton);
          navbarEnd.appendChild(li);
        }
      }

      updateToggleButtonIcon(theme) {
        const button = document.querySelector('.dark-mode-toggle');
        if (!button) return;

        if (theme === 'dark') {
          button.innerHTML = '<i class="bi bi-sun-fill"></i>';
        } else {
          button.innerHTML = '<i class="bi bi-moon-stars"></i>';
        }
      }
    }

    // Initialize dark mode on page load
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        new DarkModeManager();
      });
    } else {
      new DarkModeManager();
    }

    // FORM VALIDATOR CLASS
    class FormValidator {
      constructor(formSelector) {
        this.form = document.querySelector(formSelector);
        if (!this.form) return;
        
        this.validatedFields = new Set();
        this.init();
      }

      init() {
        this.setupInputValidation();
        this.setupPasswordStrength();
        this.setupPasswordMatching();
      }

      setupInputValidation() {
        const inputs = this.form.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
        
        inputs.forEach(input => {
          if (input.name === 'password_verify') return;
          
          input.addEventListener('blur', () => {
            this.validatedFields.add(input.name);
            this.validateField(input);
          });
          
          input.addEventListener('input', () => {
            if (this.validatedFields.has(input.name)) {
              if (input.validateTimeout) clearTimeout(input.validateTimeout);
              input.validateTimeout = setTimeout(() => {
                if (input.type !== 'password') {
                  this.validateField(input);
                }
              }, 300);
            }
          });
        });
      }

      validateField(input) {
        const value = input.value.trim();
        const type = input.type;
        const name = input.name;
        let isValid = true, errorMsg = '';

        input.classList.remove('is-valid', 'is-invalid');

        if (input.hasAttribute('required') && !value) {
          isValid = false;
          errorMsg = 'Field ini harus diisi';
        } else if (value) {
          if (type === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
              isValid = false;
              errorMsg = 'Format email tidak valid';
            }
          } else if (type === 'text' && name === 'name') {
            if (!/^[a-zA-Z\s]+$/.test(value)) {
              isValid = false;
              errorMsg = 'Nama hanya boleh huruf dan spasi';
            } else if (value.length < 3) {
              isValid = false;
              errorMsg = 'Nama minimal 3 karakter';
            }
          } else if (type === 'password' && name === 'password') {
            const hasLength = value.length >= 8;
            const hasUpper = /[A-Z]/.test(value);
            const hasNumber = /[0-9]/.test(value);
            isValid = hasLength && hasUpper && hasNumber;
          }
        }

        if (isValid && value) {
          input.classList.add('is-valid');
        } else if (!isValid) {
          input.classList.add('is-invalid');
        }

        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('small')) {
          if (errorMsg) {
            feedback.className = `small mt-1 ${isValid ? 'text-success' : 'text-danger'}`;
            feedback.textContent = errorMsg;
          }
        }

        return isValid;
      }

      setupPasswordStrength() {
        const passwordInput = document.getElementById('reg_password');
        if (!passwordInput) return;

        passwordInput.addEventListener('input', () => {
          const val = passwordInput.value;
          const strengthBar = document.getElementById('password-strength-bar');
          
          if (!strengthBar) return;

          let strength = 0;
          if (val.length >= 8) strength += 25;
          if (/[a-z]+/.test(val)) strength += 25;
          if (/[A-Z]+/.test(val)) strength += 25;
          if (/[0-9]+/.test(val)) strength += 25;

          strengthBar.style.width = strength + '%';
          strengthBar.className = 'progress-bar';
          
          if (strength <= 40) {
            strengthBar.classList.add('bg-danger');
          } else if (strength <= 80) {
            strengthBar.classList.add('bg-warning');
          } else {
            strengthBar.classList.add('bg-success');
          }

          const reqLength = document.getElementById('req-length');
          const reqUpper = document.getElementById('req-upper');
          const reqNumber = document.getElementById('req-number');

          if (reqLength) reqLength.className = val.length >= 8 ? 'text-success' : 'text-danger';
          if (reqUpper) reqUpper.className = /[A-Z]/.test(val) ? 'text-success' : 'text-danger';
          if (reqNumber) reqNumber.className = /[0-9]/.test(val) ? 'text-success' : 'text-danger';
        });
      }

      setupPasswordMatching() {
        const passInput = document.getElementById('reg_password');
        const verifyInput = document.getElementById('reg_password_verify');
        const matchFeedback = document.getElementById('password_match_feedback');

        if (!passInput || !verifyInput || !matchFeedback) return;

        const checkMatch = () => {
          if (!verifyInput.value) {
            verifyInput.classList.remove('is-valid', 'is-invalid');
            matchFeedback.textContent = '';
            return;
          }

          const isMatch = passInput.value === verifyInput.value;
          verifyInput.classList.toggle('is-valid', isMatch);
          verifyInput.classList.toggle('is-invalid', !isMatch);
          
          matchFeedback.className = `small mt-1 ${isMatch ? 'text-success' : 'text-danger'}`;
          matchFeedback.textContent = isMatch ? '✓ Password cocok' : '✗ Password tidak cocok';
        };

        passInput.addEventListener('input', checkMatch);
        verifyInput.addEventListener('input', checkMatch);
      }
    }

    // Initialize enhancements on page load
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.querySelector('form.php-email-form');
      if (form) {
        new FormValidator('form.php-email-form');
      }

      // CAPTCHA refresh button
      const captchaImg = document.querySelector('img[src*="captcha"]');
      if (captchaImg && !captchaImg.nextElementSibling?.classList.contains('btn-outline-secondary')) {
        const refreshBtn = document.createElement('button');
        refreshBtn.type = 'button';
        refreshBtn.className = 'btn btn-sm btn-outline-secondary ms-2';
        refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Refresh';
        refreshBtn.addEventListener('click', (e) => {
          e.preventDefault();
          captchaImg.src = 'captcha.php?' + new Date().getTime();
        });
        captchaImg.parentNode.insertBefore(refreshBtn, captchaImg.nextSibling);
      }
    });

    // Show toast notification
    window.showToast = function(message, type = 'info') {
      const toastContainer = document.getElementById('toastContainer') || (() => {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
        document.body.appendChild(container);
        return container;
      })();

      const toast = document.createElement('div');
      toast.className = `alert alert-${type} alert-dismissible fade show`;
      toast.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
      
      toastContainer.appendChild(toast);
      setTimeout(() => toast.remove(), 5000);
    };
  </script>

  <script>
    // ============================================================
    // UNIFIED FORM VALIDATION & TAB NAVIGATION SYSTEM
    // ============================================================
    
    // Utility: Debounce function to prevent excessive API calls
    function debounce(func, delayMs = 500) {
      let timeoutId;
      return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delayMs);
      };
    }

    // PASSWORD TOGGLE HANDLER
    function setupPasswordToggle(inputId, toggleId) {
      const passwordInput = document.getElementById(inputId);
      const toggleButton = document.getElementById(toggleId);
      if (!passwordInput || !toggleButton) return;

      toggleButton.addEventListener('click', function(e) {
        e.preventDefault();
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        const icon = this.querySelector('i');
        if (icon) {
          icon.classList.toggle('bi-eye');
          icon.classList.toggle('bi-eye-slash');
        }
      });
    }

    // FORM VALIDATION
    function checkFormValidity() {
      const submitBtn = document.querySelector('form.php-email-form button[type="submit"]');
      const emailInput = document.getElementById('email_daftar');
      const passInput = document.getElementById('reg_password');
      const verifyInput = document.getElementById('reg_password_verify');
      const nameInput = document.getElementById('reg_nama');

      if (!submitBtn || !emailInput || !passInput || !verifyInput || !nameInput) return;

      const isEmailValid = emailInput.classList.contains('is-valid');
      const passVal = passInput.value;
      const isPassValid = passVal.length >= 8 && /[A-Z]/.test(passVal) && /[0-9]/.test(passVal);
      const isMatch = passVal === verifyInput.value && passVal.length > 0;
      const isNameValid = /^[a-zA-Z\s]+$/.test(nameInput.value);

      submitBtn.disabled = !(isEmailValid && isPassValid && isMatch && isNameValid);
    }

    // EMAIL AVAILABILITY CHECK - WITH DEBOUNCING
    function setupEmailValidation() {
      const emailInput = document.getElementById('email_daftar');
      const btnCheckEmail = document.getElementById('btnCheckEmail');
      const feedback = document.getElementById('email_feedback');
      
      if (!emailInput) return;

      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      let lastCheckedEmail = '';
      let isChecking = false;

      function performEmailCheck() {
        const email = emailInput.value.trim();

        if (!email) {
          emailInput.classList.remove('is-invalid', 'is-valid');
          feedback.textContent = '';
          checkFormValidity();
          return;
        }

        if (!emailPattern.test(email)) {
          emailInput.classList.add('is-invalid');
          emailInput.classList.remove('is-valid');
          feedback.className = 'small mt-1 text-danger';
          feedback.textContent = 'Format email tidak valid.';
          checkFormValidity();
          return;
        }

        if (email === lastCheckedEmail || isChecking) return;

        isChecking = true;
        lastCheckedEmail = email;

        if (btnCheckEmail) {
          btnCheckEmail.disabled = true;
          btnCheckEmail.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
        }

        fetch('check_email_availability.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'taken') {
            emailInput.classList.add('is-invalid');
            emailInput.classList.remove('is-valid');
            feedback.className = 'small mt-1 text-danger';
            feedback.textContent = data.message || 'Email sudah terdaftar.';
          } else if (data.status === 'available') {
            emailInput.classList.add('is-valid');
            emailInput.classList.remove('is-invalid');
            feedback.className = 'small mt-1 text-success';
            feedback.textContent = data.message || 'Email tersedia.';
          }
          checkFormValidity();
        })
        .catch(error => {
          console.error('Email check error:', error);
          feedback.className = 'small mt-1 text-warning';
          feedback.textContent = 'Tidak dapat memverifikasi email.';
        })
        .finally(() => {
          isChecking = false;
          if (btnCheckEmail) {
            btnCheckEmail.disabled = false;
            btnCheckEmail.innerHTML = '<i class="bi bi-search"></i> Cek Email';
          }
        });
      }

      const debouncedCheck = debounce(performEmailCheck, 600);
      emailInput.addEventListener('input', debouncedCheck);
      
      if (btnCheckEmail) {
        btnCheckEmail.addEventListener('click', performEmailCheck);
      }
    }

    // PASSWORD STRENGTH METER
    function setupPasswordStrength() {
      const passwordInput = document.getElementById('reg_password');
      const strengthBar = document.getElementById('password-strength-bar');
      
      if (!passwordInput || !strengthBar) return;

      passwordInput.addEventListener('input', function() {
        const val = this.value;
        let strength = 0;

        if (val.length === 0) {
          strengthBar.style.width = '0%';
          strengthBar.className = 'progress-bar';
        } else {
          if (val.length >= 8) strength += 25;
          if (/[a-z]+/.test(val)) strength += 25;
          if (/[A-Z]+/.test(val)) strength += 25;
          if (/[0-9]+/.test(val)) strength += 25;

          strengthBar.style.width = strength + '%';
          strengthBar.className = strength <= 40 
            ? 'progress-bar bg-danger' 
            : strength <= 80 
            ? 'progress-bar bg-warning' 
            : 'progress-bar bg-success';

          // Update requirements
          const reqLength = document.getElementById('req-length');
          const reqUpper = document.getElementById('req-upper');
          const reqNumber = document.getElementById('req-number');

          const setReqStatus = (el, isValid, text) => {
            if (el) {
              el.className = isValid ? 'text-success' : 'text-danger';
              el.innerHTML = (isValid ? '<i class="bi bi-check"></i> ' : '<i class="bi bi-x"></i> ') + text;
            }
          };

          setReqStatus(reqLength, val.length >= 8, 'Minimal 8 karakter');
          setReqStatus(reqUpper, /[A-Z]/.test(val), 'Huruf Besar (A-Z)');
          setReqStatus(reqNumber, /[0-9]/.test(val), 'Angka (0-9)');
        }

        checkFormValidity();
      });
    }

    // PASSWORD CONFIRMATION
    function setupPasswordConfirmation() {
      const passInput = document.getElementById('reg_password');
      const verifyInput = document.getElementById('reg_password_verify');
      const feedback = document.getElementById('password_match_feedback');

      if (!passInput || !verifyInput || !feedback) return;

      function validateMatch() {
        const pass = passInput.value;
        const verify = verifyInput.value;

        if (!verify) {
          verifyInput.classList.remove('is-invalid', 'is-valid');
          feedback.textContent = '';
          return;
        }

        if (pass === verify) {
          verifyInput.classList.remove('is-invalid');
          verifyInput.classList.add('is-valid');
          feedback.className = 'small mt-1 text-success';
          feedback.textContent = 'Password cocok.';
        } else {
          verifyInput.classList.remove('is-valid');
          verifyInput.classList.add('is-invalid');
          feedback.className = 'small mt-1 text-danger';
          feedback.textContent = 'Password tidak cocok.';
        }
        checkFormValidity();
      }

      passInput.addEventListener('input', validateMatch);
      verifyInput.addEventListener('input', validateMatch);
    }

    // NAME VALIDATION
    function setupNameValidation() {
      const nameInput = document.getElementById('reg_nama');
      if (!nameInput) return;

      nameInput.addEventListener('input', function() {
        const feedback = document.getElementById('name_feedback');
        if (!feedback) return;

        if (/^[a-zA-Z\s]*$/.test(this.value)) {
          this.classList.remove('is-invalid');
          this.classList.add('is-valid');
          feedback.textContent = '';
        } else {
          this.classList.remove('is-valid');
          this.classList.add('is-invalid');
          feedback.className = 'small mt-1 text-danger';
          feedback.textContent = 'Hanya huruf dan spasi yang diperbolehkan.';
        }
        checkFormValidity();
      });
    }

    // INITIALIZE ALL ON PAGE LOAD
    document.addEventListener('DOMContentLoaded', function() {
      // Password toggles
      setupPasswordToggle('reg_password', 'toggleRegPassword');
      setupPasswordToggle('reg_password_verify', 'toggleRegPasswordVerify');

      // Validation handlers
      setupEmailValidation();
      setupPasswordStrength();
      setupPasswordConfirmation();
      setupNameValidation();

      // Initial form check
      checkFormValidity();

      // Update active nav link on scroll
      const observerOptions = {
        threshold: 0.3,
        rootMargin: '-100px 0px -66%'
      };

      const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const id = entry.target.getAttribute('id');
            const navLinks = document.querySelectorAll('nav.navmenu .nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            
            const activeLink = document.querySelector(`nav.navmenu a[href="#${id}"]`);
            if (activeLink) {
              activeLink.classList.add('active');
            }
          }
        });
      }, observerOptions);

      // Observe all main sections
      document.querySelectorAll('#home, #about, #jurusan, #daftar').forEach(section => {
        observer.observe(section);
      });
    });
  </script>

</body>

</html>