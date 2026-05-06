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
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `pendaftar`;
DROP TABLE IF EXISTS `gelombang`;
DROP TABLE IF EXISTS `announcements`;
DROP TABLE IF EXISTS `admin_logs`;
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
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin default: email=admin@smklab.sch.id | password=admin123
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Administrator', 'admin@smklab.sch.id', '$2y$10$TKh8H1.PyfcAqYxRSW9yte69twtPbWBCkG63/Our7S3g.MO0dGToi');

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
  `kuota_per_jurusan` int NOT NULL DEFAULT 36,
  `persen_gelombang` decimal(5,2) NOT NULL DEFAULT 70.00 COMMENT 'Glm1=70, Glm2=30',
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gelombang` (`gelombang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data default gelombang
INSERT INTO `gelombang` (`gelombang`, `tanggal_buka`, `tanggal_tutup`, `tanggal_pengumuman`, `kuota_per_jurusan`, `persen_gelombang`, `is_published`) VALUES
(1, '2026-06-15', '2026-06-29', '2026-07-01', 36, 70.00, 0),
(2, '2026-07-08', '2026-07-09', '2026-07-09', 36, 30.00, 0);

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
  `alamat` text DEFAULT NULL,
  `jurusan` enum('Rekayasa Perangkat Lunak (RPL)','Teknik Komputer dan Jaringan (TKJ)','Asisten Keperawatan (AP)','Tata Kecantikan Kulit dan Rambut (TKKR)') NOT NULL,
  `nilai_raport` decimal(5,2) NOT NULL COMMENT 'Rata-rata raport smt 1-6 (bobot 70%)',
  `nilai_tka` decimal(5,2) NOT NULL COMMENT 'Nilai TKA (bobot 30%)',
  `nilai_akhir` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT '(raport*70%) + (tka*30%), dihitung saat input',
  `lolos_usia` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=lolos, 0=gugur karena usia>21',
  `status` enum('pending','diterima','ditolak') NOT NULL DEFAULT 'pending',
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
  `admin_id` int NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
