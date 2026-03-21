<?php
/**
 * Halaman Informasi Sekolah - SMK Laboratorium Jakarta
 * Database-Driven Version
 */
require_once __DIR__ . '/conn.php';

// School Basic Info
$school_name = 'SMK Laboratorium Jakarta';
$school_description = 'Sekolah Menengah Kejuruan Laboratorium Jakarta adalah institusi pendidikan vokasi terkemuka yang berkomitmen menghasilkan lulusan berkompeten di bidang teknologi dan bisnis.';

// Get Exam Schedules from Database
try {
    $jadwal_stmt = $conn->prepare("SELECT * FROM jadwal_ujian ORDER BY tanggal ASC");
    $jadwal_stmt->execute();
    $jadwal_ujian = $jadwal_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $jadwal_ujian = [];
    error_log("Error fetching jadwal_ujian: " . $e->getMessage());
}

// Get Document Types from Database
try {
    $dokumen_stmt = $conn->prepare("SELECT * FROM jenis_dokumen ORDER BY id_jenis ASC");
    $dokumen_stmt->execute();
    $jenis_dokumen = $dokumen_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $jenis_dokumen = [];
    error_log("Error fetching jenis_dokumen: " . $e->getMessage());
}

// Get Enrollment Statistics by Major
try {
    $major_stats_stmt = $conn->prepare("SELECT jurusan, COUNT(*) as total FROM data_peserta GROUP BY jurusan");
    $major_stats_stmt->execute();
    $major_stats = $major_stats_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $major_stats = [];
    error_log("Error fetching major_stats: " . $e->getMessage());
}

// Get Enrollment Statistics by Route
try {
    $route_stats_stmt = $conn->prepare("SELECT jalur_daftar, COUNT(*) as total FROM data_peserta GROUP BY jalur_daftar");
    $route_stats_stmt->execute();
    $route_stats = $route_stats_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $route_stats = [];
    error_log("Error fetching route_stats: " . $e->getMessage());
}

// Calculate Total Students
$total_students = array_sum(array_column($major_stats, 'total'));

// Define majors based on database
$majors = [
    ['name' => 'RPL', 'short_name' => 'Rekayasa Perangkat Lunak', 'description' => 'Program keahlian dalam pengembangan software dan aplikasi', 'capacity' => 36],
    ['name' => 'TKJ', 'short_name' => 'Teknik Komputer & Jaringan', 'description' => 'Program keahlian dalam infrastruktur IT dan jaringan', 'capacity' => 36],
    ['name' => 'TKKR', 'short_name' => 'Teknik Kendaraan Ringan', 'description' => 'Program keahlian dalam otomotif dan kendaraan', 'capacity' => 36],
    ['name' => 'AP', 'short_name' => 'Administrasi Perkantoran', 'description' => 'Program keahlian dalam administrasi bisnis modern', 'capacity' => 36],
];

// Define admission routes
$routes = [
    ['name' => 'Reguler', 'quota' => 100, 'description' => 'Jalur penerimaan reguler melalui seleksi akademik'],
    ['name' => 'Prestasi', 'quota' => 20, 'description' => 'Jalur khusus untuk siswa berprestasi'],
    ['name' => 'Afirmasi', 'quota' => 10, 'description' => 'Jalur khusus untuk siswa yang membutuhkan prioritas'],
];
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - <?php echo $school_name; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/animate.css/animate.min.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="assets/css/main.css?v=20260117" rel="stylesheet">
    
    <style>
        .card-major {
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .card-major:hover {
            border-color: #0066cc;
            box-shadow: 0 8px 20px rgba(0,102,204,.15);
            transform: translateY(-5px);
        }
        
        .badge-major {
            font-size: 12px;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .stat-box h3 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-box p {
            font-size: 14px;
            margin: 0;
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline-item {
            margin-bottom: 30px;
            padding-left: 40px;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 20px;
            height: 20px;
            background: #0066cc;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #0066cc;
        }
        
        .achievement-badge {
            display: inline-block;
            background: #ffc107;
            color: #333;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin: 5px 5px 5px 0;
        }
    </style>
</head>
<body class="about-page">
    <!-- Header -->
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="index.php" class="logo d-flex align-items-center">
                <img src="assets/img/smk.png" style="max-height: 50px;"><h1><?php echo $school_name; ?></h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about_school.php" class="active">Tentang Kami</a></li>
                    <li><a href="index.php#daftar">Daftar</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main" class="main" style="margin-top: 100px;">
        <!-- Intro Section -->
        <section class="hero" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 0;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="display-4"><?php echo $school_name; ?></h1>
                        <p class="lead">Membangun Generasi Muda yang Berkompeten dan Profesional</p>
                        <p class="fs-6"><?php echo $school_description; ?></p>
                        <div class="mt-4">
                            <a href="#contact" class="btn btn-light btn-lg me-2">Hubungi Kami</a>
                            <a href="#majors" class="btn btn-outline-light btn-lg">Lihat Program</a>
                        </div>
                    </div>
                    <div class="col-md-6 text-center">
                        <img src="assets/img/smk.png" class="img-fluid" style="max-width: 300px;" alt="<?php echo $school_name; ?>">
                    </div>
                </div>
            </div>
        </section>

        <!-- School Info Cards -->
        <section class="py-5" style="background: #f8f9fa;">
            <div class="container">
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="stat-box" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Siswa Aktif</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3>42</h3>
                            <p>Guru Profesional</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3><?php echo count($majors); ?></h3>
                            <p>Program Studi</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-box" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <h3>1993</h3>
                            <p>Tahun Berdiri</p>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-geo-alt"></i> Lokasi</h5>
                                <p class="card-text">Jl. Raya Bogor, Manggarai, Jakarta Selatan</p>
                                <p class="card-text small text-muted">
                                    <strong>Kota:</strong> Jakarta<br>
                                    <strong>Provinsi:</strong> DKI Jakarta
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-telephone"></i> Kontak</h5>
                                <p class="card-text">
                                    <strong>Telepon:</strong> (021) 831-7227<br>
                                    <strong>Email:</strong> <a href="mailto:info@smklab.sch.id">info@smklab.sch.id</a><br>
                                    <strong>Website:</strong> <a href="http://smklab.sch.id" target="_blank">smklab.sch.id</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Program Studi Section -->
        <section id="majors" class="py-5">
            <div class="container">
                <h2 class="section-title mb-5">Program Studi Kami</h2>
                <div class="row g-4">
                    <?php foreach ($majors as $major): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-major">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $major['name']; ?></h5>
                                <p class="card-text small text-muted"><?php echo $major['short_name']; ?></p>
                                <p class="card-text fs-7"><?php echo $major['description']; ?></p>
                                
                                <div class="mt-3">
                                    <strong class="badge badge-major" style="background: #e7f3ff; color: #0066cc;">Kapasitas: <?php echo $major['capacity']; ?> siswa</strong>
                                </div>
                                
                                <?php 
                                    $major_count = 0;
                                    foreach ($major_stats as $stat) {
                                        if ($stat['jurusan'] === $major['name']) {
                                            $major_count = $stat['total'];
                                            break;
                                        }
                                    }
                                ?>
                                <div class="mt-2">
                                    <span class="badge bg-success">Terdaftar: <?php echo $major_count; ?> siswa</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Admission Routes -->
        <section class="py-5" style="background: #f8f9fa;">
            <div class="container">
                <h2 class="section-title mb-5">Jalur Penerimaan Siswa</h2>
                <div class="row g-4">
                    <?php foreach ($routes as $route): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <span class="badge bg-primary mb-2"><?php echo $route['quota']; ?> Kuota</span>
                                    <br><?php echo $route['name']; ?>
                                </h5>
                                <p class="card-text small"><?php echo $route['description']; ?></p>
                                <hr>
                                <strong class="d-block mb-2">Persyaratan:</strong>
                                <ul class="small ps-3">
                                    <li>Lulusan SMP/MTs yang sah</li>
                                    <li>Nilai akademik minimal</li>
                                    <li>Kesehatan prima</li>
                                </ul>
                                
                                <?php 
                                    $route_lower = strtolower($route['name']);
                                    $route_count = 0;
                                    foreach ($route_stats as $stat) {
                                        if (strtolower($stat['jalur_daftar']) === $route_lower) {
                                            $route_count = $stat['total'];
                                            break;
                                        }
                                    }
                                ?>
                                <div class="mt-3">
                                    <small class="text-muted">Terdaftar: <strong><?php echo $route_count; ?></strong> siswa</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Achievements -->
        <section class="py-5">
            <div class="container">
                <h2 class="section-title mb-5">Prestasi & Penghargaan Kami</h2>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="timeline">
                            <div class="timeline-item">
                                <h5>Akreditasi A</h5>
                                <p class="text-muted mb-2">
                                    <strong>Tahun:</strong> 2023 | 
                                    <strong>Tingkat:</strong> Nasional
                                </p>
                                <p>Mendapatkan akreditasi A untuk standar kualitas pendidikan tertinggi</p>
                            </div>
                            <div class="timeline-item">
                                <h5>ISO 9001:2015</h5>
                                <p class="text-muted mb-2">
                                    <strong>Tahun:</strong> 2021 | 
                                    <strong>Tingkat:</strong> Internasional
                                </p>
                                <p>Sertifikasi manajemen mutu internasional</p>
                            </div>
                            <div class="timeline-item">
                                <h5>School of Excellent</h5>
                                <p class="text-muted mb-2">
                                    <strong>Tahun:</strong> 2022 | 
                                    <strong>Tingkat:</strong> Nasional
                                </p>
                                <p>Penghargaan sebagai sekolah unggulan</p>
                            </div>
                            <div class="timeline-item">
                                <h5>Green School</h5>
                                <p class="text-muted mb-2">
                                    <strong>Tahun:</strong> 2023 | 
                                    <strong>Tingkat:</strong> Nasional
                                </p>
                                <p>Program lingkungan berkelanjutan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h5 class="card-title">🏆 Penghargaan Utama</h5>
                                <p class="card-text">SMK Laboratorium Jakarta telah meraih berbagai penghargaan di tingkat nasional dan internasional, membuktikan komitmen kami terhadap kualitas pendidikan dan prestasi siswa.</p>
                                <div class="mt-3">
                                    <span class="achievement-badge">Akreditasi A</span>
                                    <br>
                                    <span class="achievement-badge">ISO 9001:2015</span>
                                    <br>
                                    <span class="achievement-badge">School of Excellent</span>
                                    <br>
                                    <span class="achievement-badge">Green School</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Partnerships -->
        <section class="py-5" style="background: #f8f9fa;">
            <div class="container">
                <h2 class="section-title mb-5">Kemitraan Strategis</h2>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">PT. Telkom Indonesia</h5>
                                <p class="card-text"><strong>Telekomunikasi</strong></p>
                                <p class="text-muted small">Sejak tahun 2010</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">PT. Bank Mandiri</h5>
                                <p class="card-text"><strong>Perbankan</strong></p>
                                <p class="text-muted small">Sejak tahun 2012</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">Microsoft Indonesia</h5>
                                <p class="card-text"><strong>Teknologi & Software</strong></p>
                                <p class="text-muted small">Sejak tahun 2015</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="py-5">
            <div class="container">
                <h2 class="section-title mb-5">Jadwal & Informasi Ujian</h2>
                
                <?php if (!empty($jadwal_ujian)): ?>
                <div class="table-responsive mb-5">
                    <table class="table table-hover">
                        <thead class="table-primary">
                            <tr>
                                <th>Jurusan</th>
                                <th>Tanggal Ujian</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Ruangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jadwal_ujian as $jadwal): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($jadwal['jurusan']); ?></strong></td>
                                <td><?php echo date('d-m-Y', strtotime($jadwal['tanggal'])); ?></td>
                                <td><?php echo date('H:i', strtotime($jadwal['jam_mulai'])); ?></td>
                                <td><?php echo date('H:i', strtotime($jadwal['jam_selesai'])); ?></td>
                                <td><?php echo htmlspecialchars($jadwal['ruangan']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">Jadwal ujian belum tersedia</div>
                <?php endif; ?>

                <h2 class="section-title mb-5 mt-5">Dokumen yang Diperlukan</h2>
                
                <?php if (!empty($jenis_dokumen)): ?>
                <div class="row g-3 mb-5">
                    <?php foreach ($jenis_dokumen as $doc): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-file-earmark-text"></i> 
                                    <?php echo htmlspecialchars($doc['nama_jenis']); ?>
                                </h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($doc['deskripsi']); ?></p>
                                <small class="text-info"><strong>Kode:</strong> <?php echo htmlspecialchars($doc['kode_jenis']); ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="alert alert-info">Informasi dokumen tidak tersedia</div>
                <?php endif; ?>

                <h2 class="section-title mb-5">Data Pendaftar</h2>
                
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Statistik Pendaftar per Jurusan</h5>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Jurusan</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($major_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['jurusan']); ?></td>
                                            <td><strong><?php echo $stat['total']; ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-success">
                                            <td><strong>Total</strong></td>
                                            <td><strong><?php echo $total_students; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Statistik Pendaftar per Jalur</h5>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Jalur Pendaftaran</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($route_stats as $stat): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stat['jalur_daftar']); ?></td>
                                            <td><strong><?php echo $stat['total']; ?></strong></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-success">
                                            <td><strong>Total</strong></td>
                                            <td><strong><?php echo $total_students; ?></strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 class="section-title mb-5 mt-5">Hubungi Kami</h2>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-telephone"></i> Telepon</h5>
                                <p class="card-text">(021) 831-7227</p>
                            </div>
                        </div>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-envelope"></i> Email</h5>
                                <p class="card-text"><a href="mailto:info@smklab.sch.id">info@smklab.sch.id</a></p>
                            </div>
                        </div>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-clock"></i> Jam Operasional</h5>
                                <p class="card-text">Senin-Jumat: 08:00 - 16:00<br>Sabtu: 08:00 - 12:00</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-geo-alt"></i> Lokasi</h5>
                                <p class="card-text">Jl. Raya Bogor, Manggarai, Jakarta Selatan 12133</p>
                                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3966.2088!2d106.8294!3d-6.2628!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69f6a5f5f5f5f5%3A0x5f5f5f5f5f5f5f5f!2sSMK%20Laboratorium%20Jakarta!5e0!3m2!1sid!2sid!4v" style="width: 100%; height: 300px; border: 0; border-radius: 8px;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <div class="container text-center">
                <h2 class="mb-4">Siap untuk Bergabung?</h2>
                <p class="lead mb-4">Daftarkan diri Anda sekarang dan jadilah bagian dari keluarga besar SMK Laboratorium Jakarta</p>
                <a href="index.php#daftar" class="btn btn-light btn-lg">Mulai Pendaftaran</a>
            </div>
        </section>
    </main>

    <!-- Bootstrap JS -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
