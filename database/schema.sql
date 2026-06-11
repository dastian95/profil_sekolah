-- phpMyAdmin SQL Dump
-- Sistem PPDB SMK Laboratorium Jakarta
-- Versi: 2.0 (Rombak Total)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- Hapus tabel lama jika ada
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `pendaftar_raport`;
DROP TABLE IF EXISTS `pendaftar`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `gelombang`;
DROP TABLE IF EXISTS `announcements`;
-- tabel lama
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `data_peserta`;
DROP TABLE IF EXISTS `pendaftar_lama`;
DROP TABLE IF EXISTS `unggah_dokumen`;
DROP TABLE IF EXISTS `jenis_dokumen`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `user_activity_logs`;
DROP TABLE IF EXISTS `jadwal_ujian`;
DROP TABLE IF EXISTS `hasil_daftar`;
DROP TABLE IF EXISTS `daftar_ulang`;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Tabel: admins
-- ============================================================
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Akun admin default (password ditampilkan di komentar — wajib diganti setelah login pertama)
-- Login pakai USERNAME (bukan email). Akun "Super Admin" TIDAK ada di tabel ini (hardcoded di admin.php)
INSERT INTO `admins` (`name`, `username`, `email`, `password`) VALUES
('Administrator', 'admin',  'admin@smklab.sch.id', '$2y$12$TImWYleiJjm5/ZW/7jTVy.epkGrWFdzgtTTquBRo1m1tXUUu/iya2'), -- pwd: admin123
('Budi Santoso',  'budi',   'budi@smklab.sch.id',  '$2y$12$P.LZqIBum3gpl3aTd93gEu1rIwHMEsDMUOeAO3LDB1t/QPaF8r23S'), -- pwd: budi2026
('Siti Aminah',   'siti',   'siti@smklab.sch.id',  '$2y$12$5uROBC64hUB6nZ7VscrTveygq4Xcng7Ij8H/tDSBsStFdTKo37k8S'), -- pwd: siti2026
('Rahman Hakim',  'rahman', 'rahman@smklab.sch.id','$2y$12$2W92l5mQClvYhKbB1lXXXOPJTIpG8LJe6QFHWYyb5GhEHMkCQLkQW'), -- pwd: rahman2026
('Dewi Lestari',  'dewi',   'dewi@smklab.sch.id',  '$2y$12$DWNS7yOXX6w3dcwfXyEAmuq5d3Rt5v/4yzKS780gKaezskhulr/Re'), -- pwd: dewi2026
('Agus Pratama',  'agus',   'agus@smklab.sch.id',  '$2y$12$j/ICMHlknnKg2MY9yZvlyeXU9Fo82lLWgjGeHBN1Dl3r.LJ1Ytlwu'); -- pwd: agus2026

-- ============================================================
-- Tabel: gelombang
-- Menyimpan konfigurasi gelombang pendaftaran
-- ============================================================
CREATE TABLE `gelombang` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gelombang` tinyint NOT NULL COMMENT '1 atau 2',
  `tanggal_buka` date NOT NULL,
  `tanggal_tutup` date NOT NULL,
  `tanggal_pengumuman` date NOT NULL,
  `tanggal_daftar_ulang_mulai` date DEFAULT NULL,
  `tanggal_daftar_ulang_selesai` date DEFAULT NULL,
  `kuota_per_jurusan` int NOT NULL DEFAULT 36,
  `persen_gelombang` decimal(5,2) NOT NULL DEFAULT 70.00 COMMENT 'legacy field, kuota_glm prioritas',
  `kuota_glm` int DEFAULT NULL COMMENT 'Kuota absolut per jurusan untuk gelombang ini (G1=26, G2=10)',
  `jadwal_pendaftaran_text` text DEFAULT NULL COMMENT 'Display text utk halaman publik (multi-line)',
  `jadwal_pengumuman_text` text DEFAULT NULL,
  `jadwal_daftar_ulang_text` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gelombang` (`gelombang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data default gelombang
INSERT INTO `gelombang` (`gelombang`, `tanggal_buka`, `tanggal_tutup`, `tanggal_pengumuman`,
  `tanggal_daftar_ulang_mulai`, `tanggal_daftar_ulang_selesai`,
  `kuota_per_jurusan`, `persen_gelombang`, `kuota_glm`,
  `jadwal_pendaftaran_text`, `jadwal_pengumuman_text`, `jadwal_daftar_ulang_text`, `is_published`) VALUES
(1, '2026-06-15', '2026-06-29', '2026-07-01', '2026-07-03', '2026-07-04', 36, 72.22, 26,
  '15 - 29 Juni 2026 | 08.00 - 16.00\n30 Juni 2026 | 08.00 - 12.00',
  '1 Juli 2026 | 08.00 - 16.00',
  '3 - 4 Juli 2026 | 08.00 - 16.00', 0),
(2, '2026-07-08', '2026-07-09', '2026-07-10', '2026-07-10', '2026-07-10', 36, 27.78, 10,
  '8 Juli 2026 | 08.00 - 16.00\n9 Juli 2026 | 08.00 - 12.00',
  '10 Juli 2026 | 08.00 - 16.00',
  '10 Juli 2026 | 08.00 - 16.00', 0);

-- ============================================================
-- Tabel: pendaftar
-- Semua data pendaftar, diinput oleh admin
-- ============================================================
CREATE TABLE `pendaftar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `no_pendaftaran` varchar(20) NOT NULL,
  `gelombang` tinyint NOT NULL COMMENT '1 atau 2',
  `nama` varchar(255) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `usia` int NOT NULL COMMENT 'Usia dalam tahun saat mendaftar',
  `jenis_kelamin` enum('L','P') NOT NULL,
  `asal_sekolah` varchar(255) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `tgl_kk` date DEFAULT NULL COMMENT 'Tanggal terbit Kartu Keluarga (cek cut-off)',
  `alamat` text DEFAULT NULL,
  `sistem_pendidikan` enum('reguler','pkbm','khusus') NOT NULL DEFAULT 'reguler' COMMENT 'reguler = R70%+T30%; pkbm & khusus = 85% raport tanpa TKA',
  `jurusan` enum('Rekayasa Perangkat Lunak (RPL)','Teknik Komputer dan Jaringan (TKJ)','Asisten Keperawatan (AP)','Tata Kecantikan Kulit dan Rambut (TKKR)') NOT NULL,
  `nilai_raport` decimal(5,2) NOT NULL COMMENT 'Rata-rata raport smt 1-6 (bobot 70%)',
  `nilai_tka` decimal(5,2) NOT NULL COMMENT 'Nilai TKA (bobot 30%)',
  `nilai_akhir` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT '(raport*70%) + (tka*30%), dihitung saat input',
  `lolos_usia` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=lolos, 0=gugur karena usia>21',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=dijamin diterima oleh admin (tidak tampil di publik)',
  `status` enum('diproses','lengkap','gugur','terima') NOT NULL DEFAULT 'diproses',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_pendaftaran` (`no_pendaftaran`),
  KEY `jurusan` (`jurusan`),
  KEY `gelombang` (`gelombang`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabel: pendaftar_raport
-- Detail nilai raport per mata pelajaran × semester (FK ke pendaftar)
-- ============================================================
CREATE TABLE `pendaftar_raport` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pendaftar_id` int NOT NULL,
  `mata_pelajaran` varchar(100) NOT NULL,
  `semester` tinyint NOT NULL,
  `nilai` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pendaftar_mapel_smt` (`pendaftar_id`, `mata_pelajaran`, `semester`),
  CONSTRAINT `fk_raport_pendaftar` FOREIGN KEY (`pendaftar_id`) REFERENCES `pendaftar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabel: announcements
-- Pengumuman umum ditampilkan di halaman publik
-- ============================================================
CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','danger','success') DEFAULT 'info',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabel: admin_logs
-- Audit trail semua aksi admin
-- ============================================================
CREATE TABLE `admin_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL, -- NULL = superadmin (hardcoded, tidak ada di tabel admins)
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabel: tahapan
-- Tahapan/stage alur pendaftaran (dikonfigurasi oleh superadmin)
-- ============================================================
CREATE TABLE `tahapan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `kode` varchar(50) NOT NULL,
  `urutan` tinyint NOT NULL DEFAULT 1,
  `icon` varchar(50) NOT NULL DEFAULT 'bi-circle',
  `deskripsi` text DEFAULT NULL,
  `halaman_key` varchar(50) NOT NULL DEFAULT 'pendaftar' COMMENT 'key halaman admin: pendaftar, ranking, announcements, backup',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tahapan` (`nama`, `kode`, `urutan`, `icon`, `deskripsi`, `halaman_key`) VALUES
('Pengimput Data',    'input_data',    1, 'bi-keyboard',       'Bertugas mengisi data pendaftar ke sistem', 'pendaftar'),
('Proses Berkas',     'proses_berkas', 2, 'bi-folder-check',   'Memverifikasi dan memproses kelengkapan berkas', 'pendaftar'),
('Ranking & Seleksi', 'ranking',       3, 'bi-trophy',         'Mengelola ranking dan proses penerimaan', 'ranking'),
('Pengumuman',        'pengumuman',    4, 'bi-megaphone',      'Mengelola pengumuman publik PPDB', 'announcements');

-- ============================================================
-- Tabel: admin_tahapan
-- Mapping many-to-many admin ↔ tahapan
-- ============================================================
CREATE TABLE `admin_tahapan` (
  `admin_id` int NOT NULL,
  `tahap_id` int NOT NULL,
  PRIMARY KEY (`admin_id`, `tahap_id`),
  CONSTRAINT `fk_at_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_at_tahap` FOREIGN KEY (`tahap_id`) REFERENCES `tahapan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabel: meja
-- Meja fisik antrian (dikonfigurasi superadmin)
-- ============================================================
CREATE TABLE `meja` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nomor_meja` int NOT NULL,
  `nama` varchar(100) DEFAULT NULL COMMENT 'Label opsional, contoh: Loket A',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_meja` (`nomor_meja`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `meja` (`nomor_meja`, `nama`) VALUES
(1, 'Loket 1'), (2, 'Loket 2'), (3, 'Loket 3'), (4, 'Loket 4');

-- ============================================================
-- Tabel: antrian
-- Nomor antrian harian dua fase (1: cek berkas, 2: input data)
-- Nomor yang sama dipakai ulang di fase 2 → unique key per fase
-- ============================================================
CREATE TABLE `antrian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `nomor` int NOT NULL,
  `pendaftar_id` int DEFAULT NULL,
  `fase` tinyint NOT NULL DEFAULT 1,
  `hasil` enum('lulus','gagal') DEFAULT NULL,
  `meja_id` int DEFAULT NULL,
  `status` enum('menunggu','dipanggil','selesai','skip') NOT NULL DEFAULT 'menunggu',
  `dipanggil_at` timestamp NULL DEFAULT NULL,
  `selesai_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tanggal_nomor_fase` (`tanggal`, `nomor`, `fase`),
  KEY `meja_id` (`meja_id`),
  CONSTRAINT `fk_antrian_meja` FOREIGN KEY (`meja_id`) REFERENCES `meja` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_antrian_pendaftar` FOREIGN KEY (`pendaftar_id`) REFERENCES `pendaftar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Site Settings ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key`   VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `type`          ENUM('text','textarea','image_url','url','color') NOT NULL DEFAULT 'text',
  `label`         VARCHAR(200) NOT NULL,
  `group_name`    VARCHAR(100) NOT NULL,
  `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`, `type`, `label`, `group_name`) VALUES
('sekolah_nama',    'SMKS Laboratorium Jakarta',                   'text',      'Nama Sekolah',          'Identitas'),
('sekolah_tagline', 'Selamat Datang di Portal SPMB',              'text',      'Tagline',               'Identitas'),
('sekolah_alamat',  'Jl. Rawa Papan No.15, Jakarta Selatan',      'textarea',  'Alamat',                'Identitas'),
('sekolah_telp',    '',                                            'text',      'No. Telepon',           'Identitas'),
('sekolah_email',   '',                                            'text',      'Email',                 'Identitas'),
('hero_title',      'Seleksi Penerimaan Murid Baru',              'text',      'Judul Hero',            'Hero'),
('hero_subtitle',   'SMKS Laboratorium Jakarta membuka pendaftaran peserta didik baru. Daftarkan diri Anda sekarang dan raih masa depan cerah bersama kami.', 'textarea', 'Subjudul Hero', 'Hero'),
('hero_bg_image',   'assets/img/gedung-sekolah.webp',             'image_url', 'Background Hero',       'Hero'),
('about_text',      'SMKS Laboratorium Jakarta adalah sekolah menengah kejuruan yang berdedikasi menghasilkan lulusan berkualitas dan siap kerja di bidang teknologi dan kesehatan.', 'textarea', 'Deskripsi Sekolah', 'Tentang'),
('about_image',     'assets/img/gedung-sekolah.webp',             'image_url', 'Foto Tentang Sekolah',  'Tentang'),
('maps_embed_url',  'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.7!2d106.9439031!3d-6.2350331', 'url', 'URL Embed Google Maps', 'Lokasi'),
('footer_text',     '',                                            'text',      'Teks Footer Tambahan',  'Footer');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
