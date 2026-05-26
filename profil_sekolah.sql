-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Bulan Mei 2026 pada 10.28
-- Versi server: 11.4.10-MariaDB
-- Versi PHP: 8.5.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `profil_sekolah`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admins`
--

INSERT INTO `admins` (`id`, `name`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'Administrator', 'admin', 'admin@smklab.sch.id', '$2y$12$ZcREDTAw0mb4l1fBOmtimOxrvMIzlN8ygeIRaavnQwfD3bR6aGEhy', '2026-05-06 03:22:32'),
(2, 'Budi Santoso', 'budi', 'budi@smklab.sch.id', '$2y$12$ZcREDTAw0mb4l1fBOmtimOxrvMIzlN8ygeIRaavnQwfD3bR6aGEhy', '2026-05-08 02:48:34'),
(3, 'Siti Aminah', 'siti', 'siti@smklab.sch.id', '$2y$12$ZcREDTAw0mb4l1fBOmtimOxrvMIzlN8ygeIRaavnQwfD3bR6aGEhy', '2026-05-08 02:48:34'),
(4, 'Rahman Hakim', 'rahman', 'rahman@smklab.sch.id', '$2y$12$ZcREDTAw0mb4l1fBOmtimOxrvMIzlN8ygeIRaavnQwfD3bR6aGEhy', '2026-05-08 02:48:34'),
(5, 'Dewi Lestari', 'dewi', 'dewi@smklab.sch.id', '$2y$12$ZcREDTAw0mb4l1fBOmtimOxrvMIzlN8ygeIRaavnQwfD3bR6aGEhy', '2026-05-08 02:48:34'),
(6, 'Agus Pratama', 'agus', 'agus@smklab.sch.id', '$2y$12$ZcREDTAw0mb4l1fBOmtimOxrvMIzlN8ygeIRaavnQwfD3bR6aGEhy', '2026-05-08 02:48:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-08 02:28:43'),
(2, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-08 03:27:02'),
(3, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-10 10:44:57'),
(4, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-10 10:45:00'),
(5, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-10 10:46:39'),
(6, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-10 10:48:48'),
(7, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-13 14:36:27'),
(8, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-15 08:40:35'),
(9, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-18 01:59:12'),
(10, 1, 'TAMBAH_PENDAFTAR', 'Tambah pendaftar: Mujammad Sharil Al Farizi (PPDB-2026-G1-0001)', '::1', '2026-05-18 02:35:50'),
(11, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-20 11:17:49'),
(12, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-20 12:47:12'),
(13, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-20 12:47:21'),
(14, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-21 00:24:18'),
(15, NULL, 'TAHAPAN_EDIT', 'Edit tahapan ID:1 → input_data', '::1', '2026-05-21 01:13:16'),
(16, NULL, 'ANTRIAN_BUKA', 'Buka antrian 2026-05-21: 100 nomor, 4 meja', '::1', '2026-05-21 01:56:48'),
(17, NULL, 'MEJA_EDIT', 'Edit Meja ID:3 → nomor 3, fase 2', '::1', '2026-05-21 02:02:28'),
(18, NULL, 'MEJA_EDIT', 'Edit Meja ID:3 → nomor 3, fase 2', '::1', '2026-05-21 02:02:28'),
(19, NULL, 'MEJA_EDIT', 'Edit Meja ID:4 → nomor 4, fase 2', '::1', '2026-05-21 02:02:36'),
(20, 1, 'LOGIN', 'Admin login berhasil', '127.0.0.1', '2026-05-21 02:04:14'),
(21, NULL, 'ANTRIAN_RESET', 'Reset antrian tanggal 2026-05-21', '::1', '2026-05-21 02:24:43'),
(22, NULL, 'ANTRIAN_RESET', 'Reset antrian tanggal 2026-05-21', '::1', '2026-05-21 02:25:44'),
(23, NULL, 'ANTRIAN_RESET', 'Reset antrian tanggal 2026-05-21', '::1', '2026-05-21 02:26:38'),
(24, NULL, 'ANTRIAN_RESET', 'Reset antrian tanggal 2026-05-21', '::1', '2026-05-21 02:27:36'),
(25, NULL, 'ANTRIAN_RESET', 'Hapus total antrian tanggal 2026-05-21', '::1', '2026-05-21 02:38:27'),
(26, NULL, 'ANTRIAN_BUKA', 'Buka antrian 2026-05-21: 200 nomor, 4 meja', '::1', '2026-05-21 02:38:38'),
(27, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-21 23:48:53'),
(28, NULL, 'LOGIN_FAILED', 'Login gagal untuk username: superadmin', '::1', '2026-05-21 23:49:24'),
(29, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-21 23:49:56'),
(30, NULL, 'PUBLISH_PENGUMUMAN', 'Publish pengumuman gelombang ID:1', '::1', '2026-05-21 23:58:16'),
(31, NULL, 'UNPUBLISH_PENGUMUMAN', 'Unpublish pengumuman gelombang ID:1', '::1', '2026-05-21 23:58:47'),
(32, NULL, 'ANTRIAN_BUKA', 'Buka antrian 2026-05-22: 200 nomor, 4 meja', '::1', '2026-05-22 00:27:43'),
(33, NULL, 'MEJA_ADD', 'Tambah Meja 5 (Fase 2)', '::1', '2026-05-22 00:47:35'),
(34, NULL, 'MEJA_EDIT', 'Edit Meja ID:18 → nomor 5, fase 2', '::1', '2026-05-22 00:54:54'),
(35, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-22 00:55:38'),
(36, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-22 00:59:26'),
(37, NULL, 'LOGIN_FAILED', 'Login gagal untuk username: superadmin', '::1', '2026-05-23 03:41:49'),
(38, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-23 03:42:02'),
(39, NULL, 'PUBLISH_PENGUMUMAN', 'Publish pengumuman gelombang ID:1', '::1', '2026-05-23 03:42:38'),
(40, NULL, 'PUBLISH_PENGUMUMAN', 'Publish pengumuman gelombang ID:2', '::1', '2026-05-23 04:09:43'),
(41, NULL, 'UNPUBLISH_PENGUMUMAN', 'Unpublish semua pengumuman gelombang ID:2', '::1', '2026-05-23 04:10:29'),
(42, NULL, 'ADMIN_EDIT', 'Superadmin edit admin ID:1 → admin, 4 tahapan', '::1', '2026-05-23 04:11:21'),
(43, NULL, 'PROSES_RANKING', 'Proses penerimaan Gelombang 1: 104 diterima', '::1', '2026-05-23 04:23:47'),
(44, NULL, 'ANTRIAN_BUKA', 'Buka antrian 2026-05-23: 50 nomor, 5 meja', '::1', '2026-05-23 04:46:13'),
(45, NULL, 'LOGOUT_SUPER', 'Superadmin logout', '::1', '2026-05-23 04:48:53'),
(46, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-23 04:49:01'),
(47, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-23 04:49:51'),
(48, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-23 04:50:08'),
(49, NULL, 'ADMIN_EDIT', 'Superadmin edit admin ID:1 → admin, 1 tahapan', '::1', '2026-05-23 04:50:37'),
(50, NULL, 'LOGOUT_SUPER', 'Superadmin logout', '::1', '2026-05-23 04:50:47'),
(51, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-23 04:50:54'),
(52, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-23 04:51:24'),
(53, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-23 04:51:37'),
(54, NULL, 'LOGOUT_SUPER', 'Superadmin logout', '::1', '2026-05-23 05:14:35'),
(55, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-23 05:14:43'),
(56, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-23 05:14:55'),
(57, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-23 05:15:14'),
(58, NULL, 'ADMIN_EDIT', 'Superadmin edit admin ID:1 → admin, 4 tahapan', '::1', '2026-05-23 05:15:29'),
(59, NULL, 'LOGOUT_SUPER', 'Superadmin logout', '::1', '2026-05-23 05:15:52'),
(60, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-23 05:16:02'),
(61, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-23 05:16:29'),
(62, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-23 05:16:51'),
(63, NULL, 'ADMIN_EDIT', 'Superadmin edit admin ID:1 → admin, 0 tahapan', '::1', '2026-05-23 05:16:58'),
(64, NULL, 'LOGOUT_SUPER', 'Superadmin logout', '::1', '2026-05-23 05:17:02'),
(65, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-23 05:17:12'),
(66, 1, 'LOGOUT', 'Admin logout', '::1', '2026-05-23 05:17:32'),
(67, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-23 05:17:54'),
(68, 1, 'LOGIN', 'Admin login berhasil', '127.0.0.1', '2026-05-23 05:27:24'),
(69, NULL, 'ADMIN_EDIT', 'Superadmin edit admin ID:1 → admin, 1 tahapan', '::1', '2026-05-23 05:31:58'),
(70, NULL, 'LOGIN_FAILED', 'Login gagal untuk username: superadmin', '::1', '2026-05-23 22:32:49'),
(71, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-23 22:33:10'),
(72, NULL, 'LOGIN_FAILED', 'Login gagal untuk username: admin', '::1', '2026-05-23 22:40:20'),
(73, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-23 22:40:26'),
(74, NULL, 'ANTRIAN_BUKA', 'Buka antrian 2026-05-24: 50 nomor, 5 meja', '::1', '2026-05-23 22:40:35'),
(75, NULL, 'PUBLISH_HASIL', 'Publish hasil penerimaan gelombang ID:1', '::1', '2026-05-24 00:32:06'),
(76, NULL, 'PUBLISH_HASIL', 'Publish hasil penerimaan gelombang ID:1', '::1', '2026-05-24 01:20:45'),
(77, NULL, 'UNPUBLISH_PENGUMUMAN', 'Unpublish semua pengumuman gelombang ID:1', '::1', '2026-05-24 07:12:48'),
(78, NULL, 'UNPUBLISH_PENGUMUMAN', 'Unpublish semua pengumuman gelombang ID:1', '::1', '2026-05-24 07:12:54'),
(79, NULL, 'UNPUBLISH_PENGUMUMAN', 'Unpublish semua pengumuman gelombang ID:1', '::1', '2026-05-24 07:12:57'),
(80, 1, 'LOGIN', 'Admin login berhasil', '127.0.0.1', '2026-05-24 08:27:31'),
(81, 1, 'LOGIN', 'Admin login berhasil', '127.0.0.1', '2026-05-24 11:22:48'),
(82, 1, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-25 01:20:56'),
(83, NULL, 'LOGIN_SUPER', 'Superadmin login berhasil', '::1', '2026-05-25 01:21:42'),
(84, NULL, 'UPDATE_GELOMBANG', 'Update setting gelombang ID:1', '::1', '2026-05-25 01:25:47'),
(85, NULL, 'UPDATE_GELOMBANG', 'Update setting gelombang ID:1', '::1', '2026-05-25 01:25:55'),
(86, NULL, 'UPDATE_GELOMBANG', 'Update setting gelombang ID:1', '::1', '2026-05-25 01:25:57'),
(87, NULL, 'UPDATE_GELOMBANG', 'Update setting gelombang ID:1', '::1', '2026-05-25 01:26:01'),
(88, NULL, 'PUBLISH_PENGUMUMAN', 'Publish pengumuman gelombang ID:1', '::1', '2026-05-25 01:26:08'),
(89, NULL, 'LOGIN_FAILED', 'Login gagal untuk username: budi', '::1', '2026-05-25 01:43:53'),
(90, 2, 'LOGIN', 'Admin login berhasil', '::1', '2026-05-25 02:24:00'),
(91, NULL, 'MEJA_ADD', 'Tambah Meja 6 (Fase 1)', '::1', '2026-05-25 02:25:04'),
(92, NULL, 'MEJA_ADD', 'Tambah Meja 7 (Fase 1)', '::1', '2026-05-25 02:25:14'),
(93, NULL, 'ANTRIAN_BUKA', 'Buka antrian 2026-05-25: 200 nomor, 7 meja', '::1', '2026-05-25 02:26:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_tahapan`
--

CREATE TABLE `admin_tahapan` (
  `admin_id` int(11) NOT NULL,
  `tahap_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin_tahapan`
--

INSERT INTO `admin_tahapan` (`admin_id`, `tahap_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','danger','success') DEFAULT 'info',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `type`, `is_active`, `created_at`) VALUES
(1, 'orang saya', 'kumpul do bawahan', 'info', 0, '2026-05-10 10:48:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `antrian`
--

CREATE TABLE `antrian` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `nomor` int(11) NOT NULL,
  `fase` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=Cek Berkas, 2=Input Data',
  `meja_id` int(11) DEFAULT NULL,
  `status` enum('menunggu','dipanggil','selesai','skip') NOT NULL DEFAULT 'menunggu',
  `hasil` enum('lulus','gagal') DEFAULT NULL COMMENT 'Hasil cek berkas (fase 1 only)',
  `dipanggil_at` timestamp NULL DEFAULT NULL,
  `selesai_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `antrian`
--

INSERT INTO `antrian` (`id`, `tanggal`, `nomor`, `fase`, `meja_id`, `status`, `hasil`, `dipanggil_at`, `selesai_at`, `created_at`) VALUES
(103, '2026-05-21', 1, 1, 1, 'selesai', 'lulus', '2026-05-21 02:38:53', '2026-05-21 02:39:12', '2026-05-21 02:38:35'),
(104, '2026-05-21', 2, 1, 1, 'selesai', 'lulus', '2026-05-21 02:39:12', '2026-05-21 03:00:03', '2026-05-21 02:38:35'),
(105, '2026-05-21', 3, 1, 1, 'dipanggil', NULL, '2026-05-21 03:00:03', NULL, '2026-05-21 02:38:35'),
(106, '2026-05-21', 4, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(107, '2026-05-21', 5, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(108, '2026-05-21', 6, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(109, '2026-05-21', 7, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(110, '2026-05-21', 8, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(111, '2026-05-21', 9, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(112, '2026-05-21', 10, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(113, '2026-05-21', 11, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(114, '2026-05-21', 12, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(115, '2026-05-21', 13, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(116, '2026-05-21', 14, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(117, '2026-05-21', 15, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(118, '2026-05-21', 16, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(119, '2026-05-21', 17, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(120, '2026-05-21', 18, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(121, '2026-05-21', 19, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(122, '2026-05-21', 20, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(123, '2026-05-21', 21, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(124, '2026-05-21', 22, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(125, '2026-05-21', 23, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(126, '2026-05-21', 24, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(127, '2026-05-21', 25, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(128, '2026-05-21', 26, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(129, '2026-05-21', 27, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(130, '2026-05-21', 28, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(131, '2026-05-21', 29, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(132, '2026-05-21', 30, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(133, '2026-05-21', 31, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(134, '2026-05-21', 32, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(135, '2026-05-21', 33, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(136, '2026-05-21', 34, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(137, '2026-05-21', 35, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(138, '2026-05-21', 36, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(139, '2026-05-21', 37, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(140, '2026-05-21', 38, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(141, '2026-05-21', 39, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(142, '2026-05-21', 40, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(143, '2026-05-21', 41, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(144, '2026-05-21', 42, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(145, '2026-05-21', 43, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(146, '2026-05-21', 44, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(147, '2026-05-21', 45, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(148, '2026-05-21', 46, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(149, '2026-05-21', 47, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(150, '2026-05-21', 48, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(151, '2026-05-21', 49, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(152, '2026-05-21', 50, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(153, '2026-05-21', 51, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(154, '2026-05-21', 52, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(155, '2026-05-21', 53, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(156, '2026-05-21', 54, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(157, '2026-05-21', 55, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(158, '2026-05-21', 56, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(159, '2026-05-21', 57, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(160, '2026-05-21', 58, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(161, '2026-05-21', 59, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(162, '2026-05-21', 60, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(163, '2026-05-21', 61, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(164, '2026-05-21', 62, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(165, '2026-05-21', 63, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(166, '2026-05-21', 64, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(167, '2026-05-21', 65, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(168, '2026-05-21', 66, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(169, '2026-05-21', 67, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(170, '2026-05-21', 68, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(171, '2026-05-21', 69, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(172, '2026-05-21', 70, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(173, '2026-05-21', 71, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(174, '2026-05-21', 72, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(175, '2026-05-21', 73, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(176, '2026-05-21', 74, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(177, '2026-05-21', 75, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(178, '2026-05-21', 76, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(179, '2026-05-21', 77, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(180, '2026-05-21', 78, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(181, '2026-05-21', 79, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(182, '2026-05-21', 80, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(183, '2026-05-21', 81, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(184, '2026-05-21', 82, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(185, '2026-05-21', 83, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(186, '2026-05-21', 84, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(187, '2026-05-21', 85, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(188, '2026-05-21', 86, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(189, '2026-05-21', 87, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(190, '2026-05-21', 88, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(191, '2026-05-21', 89, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(192, '2026-05-21', 90, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(193, '2026-05-21', 91, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(194, '2026-05-21', 92, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(195, '2026-05-21', 93, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(196, '2026-05-21', 94, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(197, '2026-05-21', 95, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(198, '2026-05-21', 96, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(199, '2026-05-21', 97, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(200, '2026-05-21', 98, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(201, '2026-05-21', 99, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(202, '2026-05-21', 100, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(203, '2026-05-21', 101, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(204, '2026-05-21', 102, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(205, '2026-05-21', 103, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(206, '2026-05-21', 104, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(207, '2026-05-21', 105, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(208, '2026-05-21', 106, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(209, '2026-05-21', 107, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(210, '2026-05-21', 108, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(211, '2026-05-21', 109, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(212, '2026-05-21', 110, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(213, '2026-05-21', 111, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(214, '2026-05-21', 112, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(215, '2026-05-21', 113, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(216, '2026-05-21', 114, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(217, '2026-05-21', 115, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(218, '2026-05-21', 116, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(219, '2026-05-21', 117, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(220, '2026-05-21', 118, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(221, '2026-05-21', 119, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(222, '2026-05-21', 120, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(223, '2026-05-21', 121, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(224, '2026-05-21', 122, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(225, '2026-05-21', 123, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(226, '2026-05-21', 124, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(227, '2026-05-21', 125, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:35'),
(228, '2026-05-21', 126, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(229, '2026-05-21', 127, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(230, '2026-05-21', 128, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(231, '2026-05-21', 129, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(232, '2026-05-21', 130, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(233, '2026-05-21', 131, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(234, '2026-05-21', 132, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(235, '2026-05-21', 133, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(236, '2026-05-21', 134, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(237, '2026-05-21', 135, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(238, '2026-05-21', 136, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(239, '2026-05-21', 137, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(240, '2026-05-21', 138, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(241, '2026-05-21', 139, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(242, '2026-05-21', 140, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(243, '2026-05-21', 141, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(244, '2026-05-21', 142, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(245, '2026-05-21', 143, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(246, '2026-05-21', 144, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(247, '2026-05-21', 145, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(248, '2026-05-21', 146, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(249, '2026-05-21', 147, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(250, '2026-05-21', 148, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(251, '2026-05-21', 149, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(252, '2026-05-21', 150, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(253, '2026-05-21', 151, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(254, '2026-05-21', 152, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(255, '2026-05-21', 153, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(256, '2026-05-21', 154, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(257, '2026-05-21', 155, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(258, '2026-05-21', 156, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(259, '2026-05-21', 157, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(260, '2026-05-21', 158, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(261, '2026-05-21', 159, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(262, '2026-05-21', 160, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(263, '2026-05-21', 161, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(264, '2026-05-21', 162, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(265, '2026-05-21', 163, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(266, '2026-05-21', 164, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(267, '2026-05-21', 165, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(268, '2026-05-21', 166, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(269, '2026-05-21', 167, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(270, '2026-05-21', 168, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:36'),
(271, '2026-05-21', 169, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:37'),
(272, '2026-05-21', 170, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(273, '2026-05-21', 171, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(274, '2026-05-21', 172, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(275, '2026-05-21', 173, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(276, '2026-05-21', 174, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(277, '2026-05-21', 175, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(278, '2026-05-21', 176, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(279, '2026-05-21', 177, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(280, '2026-05-21', 178, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(281, '2026-05-21', 179, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(282, '2026-05-21', 180, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(283, '2026-05-21', 181, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(284, '2026-05-21', 182, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(285, '2026-05-21', 183, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(286, '2026-05-21', 184, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(287, '2026-05-21', 185, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(288, '2026-05-21', 186, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(289, '2026-05-21', 187, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(290, '2026-05-21', 188, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(291, '2026-05-21', 189, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(292, '2026-05-21', 190, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(293, '2026-05-21', 191, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(294, '2026-05-21', 192, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(295, '2026-05-21', 193, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(296, '2026-05-21', 194, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(297, '2026-05-21', 195, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(298, '2026-05-21', 196, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(299, '2026-05-21', 197, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(300, '2026-05-21', 198, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(301, '2026-05-21', 199, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(302, '2026-05-21', 200, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:38:38'),
(303, '2026-05-21', 1, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-21 02:39:12'),
(304, '2026-05-21', 2, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-21 03:00:03'),
(305, '2026-05-22', 1, 1, 1, 'selesai', 'lulus', '2026-05-22 00:54:00', '2026-05-22 00:54:12', '2026-05-22 00:27:42'),
(306, '2026-05-22', 2, 1, 1, 'selesai', 'gagal', '2026-05-22 00:54:12', '2026-05-22 00:54:19', '2026-05-22 00:27:42'),
(307, '2026-05-22', 3, 1, 1, 'dipanggil', NULL, '2026-05-22 00:54:19', NULL, '2026-05-22 00:27:42'),
(308, '2026-05-22', 4, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(309, '2026-05-22', 5, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(310, '2026-05-22', 6, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(311, '2026-05-22', 7, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(312, '2026-05-22', 8, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(313, '2026-05-22', 9, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(314, '2026-05-22', 10, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(315, '2026-05-22', 11, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(316, '2026-05-22', 12, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(317, '2026-05-22', 13, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(318, '2026-05-22', 14, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(319, '2026-05-22', 15, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(320, '2026-05-22', 16, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(321, '2026-05-22', 17, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(322, '2026-05-22', 18, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(323, '2026-05-22', 19, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(324, '2026-05-22', 20, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(325, '2026-05-22', 21, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(326, '2026-05-22', 22, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(327, '2026-05-22', 23, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(328, '2026-05-22', 24, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(329, '2026-05-22', 25, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(330, '2026-05-22', 26, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(331, '2026-05-22', 27, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(332, '2026-05-22', 28, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(333, '2026-05-22', 29, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(334, '2026-05-22', 30, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(335, '2026-05-22', 31, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(336, '2026-05-22', 32, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(337, '2026-05-22', 33, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(338, '2026-05-22', 34, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(339, '2026-05-22', 35, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(340, '2026-05-22', 36, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(341, '2026-05-22', 37, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(342, '2026-05-22', 38, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(343, '2026-05-22', 39, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(344, '2026-05-22', 40, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(345, '2026-05-22', 41, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(346, '2026-05-22', 42, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(347, '2026-05-22', 43, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(348, '2026-05-22', 44, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(349, '2026-05-22', 45, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(350, '2026-05-22', 46, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(351, '2026-05-22', 47, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(352, '2026-05-22', 48, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(353, '2026-05-22', 49, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(354, '2026-05-22', 50, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(355, '2026-05-22', 51, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(356, '2026-05-22', 52, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(357, '2026-05-22', 53, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(358, '2026-05-22', 54, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(359, '2026-05-22', 55, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(360, '2026-05-22', 56, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(361, '2026-05-22', 57, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(362, '2026-05-22', 58, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(363, '2026-05-22', 59, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(364, '2026-05-22', 60, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(365, '2026-05-22', 61, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(366, '2026-05-22', 62, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(367, '2026-05-22', 63, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(368, '2026-05-22', 64, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(369, '2026-05-22', 65, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(370, '2026-05-22', 66, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(371, '2026-05-22', 67, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(372, '2026-05-22', 68, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(373, '2026-05-22', 69, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(374, '2026-05-22', 70, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(375, '2026-05-22', 71, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(376, '2026-05-22', 72, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(377, '2026-05-22', 73, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(378, '2026-05-22', 74, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(379, '2026-05-22', 75, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(380, '2026-05-22', 76, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(381, '2026-05-22', 77, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(382, '2026-05-22', 78, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(383, '2026-05-22', 79, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(384, '2026-05-22', 80, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(385, '2026-05-22', 81, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(386, '2026-05-22', 82, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(387, '2026-05-22', 83, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(388, '2026-05-22', 84, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(389, '2026-05-22', 85, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(390, '2026-05-22', 86, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(391, '2026-05-22', 87, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(392, '2026-05-22', 88, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(393, '2026-05-22', 89, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(394, '2026-05-22', 90, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(395, '2026-05-22', 91, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(396, '2026-05-22', 92, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(397, '2026-05-22', 93, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(398, '2026-05-22', 94, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(399, '2026-05-22', 95, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(400, '2026-05-22', 96, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(401, '2026-05-22', 97, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(402, '2026-05-22', 98, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(403, '2026-05-22', 99, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(404, '2026-05-22', 100, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(405, '2026-05-22', 101, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(406, '2026-05-22', 102, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(407, '2026-05-22', 103, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(408, '2026-05-22', 104, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(409, '2026-05-22', 105, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(410, '2026-05-22', 106, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(411, '2026-05-22', 107, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(412, '2026-05-22', 108, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(413, '2026-05-22', 109, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(414, '2026-05-22', 110, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(415, '2026-05-22', 111, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(416, '2026-05-22', 112, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(417, '2026-05-22', 113, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(418, '2026-05-22', 114, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(419, '2026-05-22', 115, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(420, '2026-05-22', 116, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(421, '2026-05-22', 117, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(422, '2026-05-22', 118, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(423, '2026-05-22', 119, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(424, '2026-05-22', 120, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(425, '2026-05-22', 121, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(426, '2026-05-22', 122, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(427, '2026-05-22', 123, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(428, '2026-05-22', 124, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(429, '2026-05-22', 125, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(430, '2026-05-22', 126, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(431, '2026-05-22', 127, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(432, '2026-05-22', 128, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(433, '2026-05-22', 129, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(434, '2026-05-22', 130, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(435, '2026-05-22', 131, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(436, '2026-05-22', 132, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(437, '2026-05-22', 133, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(438, '2026-05-22', 134, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(439, '2026-05-22', 135, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(440, '2026-05-22', 136, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(441, '2026-05-22', 137, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(442, '2026-05-22', 138, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(443, '2026-05-22', 139, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(444, '2026-05-22', 140, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(445, '2026-05-22', 141, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:42'),
(446, '2026-05-22', 142, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(447, '2026-05-22', 143, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(448, '2026-05-22', 144, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(449, '2026-05-22', 145, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(450, '2026-05-22', 146, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(451, '2026-05-22', 147, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(452, '2026-05-22', 148, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(453, '2026-05-22', 149, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(454, '2026-05-22', 150, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(455, '2026-05-22', 151, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(456, '2026-05-22', 152, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(457, '2026-05-22', 153, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(458, '2026-05-22', 154, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(459, '2026-05-22', 155, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(460, '2026-05-22', 156, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(461, '2026-05-22', 157, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(462, '2026-05-22', 158, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(463, '2026-05-22', 159, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(464, '2026-05-22', 160, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(465, '2026-05-22', 161, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(466, '2026-05-22', 162, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(467, '2026-05-22', 163, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(468, '2026-05-22', 164, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(469, '2026-05-22', 165, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(470, '2026-05-22', 166, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(471, '2026-05-22', 167, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(472, '2026-05-22', 168, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(473, '2026-05-22', 169, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(474, '2026-05-22', 170, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(475, '2026-05-22', 171, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(476, '2026-05-22', 172, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(477, '2026-05-22', 173, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(478, '2026-05-22', 174, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(479, '2026-05-22', 175, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(480, '2026-05-22', 176, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(481, '2026-05-22', 177, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(482, '2026-05-22', 178, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(483, '2026-05-22', 179, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(484, '2026-05-22', 180, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(485, '2026-05-22', 181, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(486, '2026-05-22', 182, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(487, '2026-05-22', 183, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(488, '2026-05-22', 184, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(489, '2026-05-22', 185, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(490, '2026-05-22', 186, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(491, '2026-05-22', 187, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(492, '2026-05-22', 188, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(493, '2026-05-22', 189, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(494, '2026-05-22', 190, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(495, '2026-05-22', 191, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(496, '2026-05-22', 192, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(497, '2026-05-22', 193, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(498, '2026-05-22', 194, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(499, '2026-05-22', 195, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(500, '2026-05-22', 196, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(501, '2026-05-22', 197, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(502, '2026-05-22', 198, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(503, '2026-05-22', 199, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(504, '2026-05-22', 200, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:27:43'),
(505, '2026-05-22', 1, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-22 00:54:12'),
(506, '2026-05-23', 1, 1, 1, 'selesai', 'lulus', '2026-05-23 05:27:34', '2026-05-23 05:27:37', '2026-05-23 04:46:13'),
(507, '2026-05-23', 2, 1, 1, 'dipanggil', NULL, '2026-05-23 05:27:37', NULL, '2026-05-23 04:46:13'),
(508, '2026-05-23', 3, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(509, '2026-05-23', 4, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(510, '2026-05-23', 5, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(511, '2026-05-23', 6, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(512, '2026-05-23', 7, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(513, '2026-05-23', 8, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(514, '2026-05-23', 9, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(515, '2026-05-23', 10, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(516, '2026-05-23', 11, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(517, '2026-05-23', 12, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(518, '2026-05-23', 13, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(519, '2026-05-23', 14, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(520, '2026-05-23', 15, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(521, '2026-05-23', 16, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(522, '2026-05-23', 17, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(523, '2026-05-23', 18, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(524, '2026-05-23', 19, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(525, '2026-05-23', 20, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(526, '2026-05-23', 21, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(527, '2026-05-23', 22, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(528, '2026-05-23', 23, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(529, '2026-05-23', 24, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(530, '2026-05-23', 25, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(531, '2026-05-23', 26, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(532, '2026-05-23', 27, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(533, '2026-05-23', 28, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(534, '2026-05-23', 29, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(535, '2026-05-23', 30, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(536, '2026-05-23', 31, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(537, '2026-05-23', 32, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(538, '2026-05-23', 33, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(539, '2026-05-23', 34, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(540, '2026-05-23', 35, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(541, '2026-05-23', 36, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(542, '2026-05-23', 37, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(543, '2026-05-23', 38, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(544, '2026-05-23', 39, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(545, '2026-05-23', 40, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(546, '2026-05-23', 41, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(547, '2026-05-23', 42, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(548, '2026-05-23', 43, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(549, '2026-05-23', 44, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(550, '2026-05-23', 45, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(551, '2026-05-23', 46, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(552, '2026-05-23', 47, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(553, '2026-05-23', 48, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(554, '2026-05-23', 49, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(555, '2026-05-23', 50, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 04:46:13'),
(556, '2026-05-23', 1, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-23 05:27:37'),
(557, '2026-05-24', 1, 1, 1, 'selesai', 'lulus', '2026-05-24 07:26:59', '2026-05-24 07:27:09', '2026-05-23 22:40:35'),
(558, '2026-05-24', 2, 1, 1, 'selesai', 'lulus', '2026-05-24 07:27:09', '2026-05-24 07:28:50', '2026-05-23 22:40:35'),
(559, '2026-05-24', 3, 1, 1, 'selesai', 'lulus', '2026-05-24 07:28:50', '2026-05-24 07:39:37', '2026-05-23 22:40:35'),
(560, '2026-05-24', 4, 1, 1, 'selesai', 'gagal', '2026-05-24 07:39:37', '2026-05-24 08:29:31', '2026-05-23 22:40:35'),
(561, '2026-05-24', 5, 1, 2, 'selesai', 'lulus', '2026-05-24 08:27:45', '2026-05-24 08:27:49', '2026-05-23 22:40:35'),
(562, '2026-05-24', 6, 1, 2, 'selesai', 'lulus', '2026-05-24 08:27:49', '2026-05-24 08:27:55', '2026-05-23 22:40:35'),
(563, '2026-05-24', 7, 1, 2, 'selesai', 'lulus', '2026-05-24 08:27:55', '2026-05-24 08:31:20', '2026-05-23 22:40:35'),
(564, '2026-05-24', 8, 1, 1, 'skip', NULL, '2026-05-24 08:29:31', '2026-05-24 08:29:35', '2026-05-23 22:40:35'),
(565, '2026-05-24', 9, 1, 1, 'selesai', 'lulus', '2026-05-24 08:29:35', '2026-05-24 08:29:57', '2026-05-23 22:40:35'),
(566, '2026-05-24', 10, 1, 1, 'selesai', 'lulus', '2026-05-24 08:29:57', '2026-05-24 08:30:05', '2026-05-23 22:40:35'),
(567, '2026-05-24', 11, 1, 1, 'selesai', 'lulus', '2026-05-24 08:30:05', '2026-05-24 08:30:20', '2026-05-23 22:40:35'),
(568, '2026-05-24', 12, 1, 1, 'selesai', 'lulus', '2026-05-24 08:30:20', '2026-05-24 08:32:28', '2026-05-23 22:40:35'),
(569, '2026-05-24', 13, 1, 2, 'selesai', 'lulus', '2026-05-24 08:31:20', '2026-05-24 08:32:01', '2026-05-23 22:40:35'),
(570, '2026-05-24', 14, 1, 2, 'selesai', 'lulus', '2026-05-24 08:32:01', '2026-05-24 08:33:38', '2026-05-23 22:40:35'),
(571, '2026-05-24', 15, 1, 1, 'selesai', 'lulus', '2026-05-24 08:32:28', '2026-05-24 09:05:59', '2026-05-23 22:40:35'),
(572, '2026-05-24', 16, 1, 2, 'dipanggil', NULL, '2026-05-24 08:33:38', NULL, '2026-05-23 22:40:35'),
(573, '2026-05-24', 17, 1, 1, 'selesai', 'lulus', '2026-05-24 09:05:59', '2026-05-24 09:43:36', '2026-05-23 22:40:35'),
(574, '2026-05-24', 18, 1, 1, 'selesai', 'lulus', '2026-05-24 09:43:36', '2026-05-24 09:43:51', '2026-05-23 22:40:35'),
(575, '2026-05-24', 19, 1, 1, 'dipanggil', NULL, '2026-05-24 09:43:51', NULL, '2026-05-23 22:40:35'),
(576, '2026-05-24', 20, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(577, '2026-05-24', 21, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(578, '2026-05-24', 22, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(579, '2026-05-24', 23, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(580, '2026-05-24', 24, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(581, '2026-05-24', 25, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(582, '2026-05-24', 26, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(583, '2026-05-24', 27, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(584, '2026-05-24', 28, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(585, '2026-05-24', 29, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(586, '2026-05-24', 30, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(587, '2026-05-24', 31, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(588, '2026-05-24', 32, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(589, '2026-05-24', 33, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(590, '2026-05-24', 34, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(591, '2026-05-24', 35, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(592, '2026-05-24', 36, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(593, '2026-05-24', 37, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(594, '2026-05-24', 38, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(595, '2026-05-24', 39, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(596, '2026-05-24', 40, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(597, '2026-05-24', 41, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(598, '2026-05-24', 42, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(599, '2026-05-24', 43, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(600, '2026-05-24', 44, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(601, '2026-05-24', 45, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(602, '2026-05-24', 46, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(603, '2026-05-24', 47, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(604, '2026-05-24', 48, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(605, '2026-05-24', 49, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(606, '2026-05-24', 50, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-23 22:40:35'),
(607, '2026-05-24', 1, 2, 3, 'selesai', NULL, '2026-05-24 08:28:22', '2026-05-24 08:28:32', '2026-05-24 07:27:09'),
(608, '2026-05-24', 2, 2, 3, 'selesai', NULL, '2026-05-24 08:28:32', '2026-05-24 08:34:09', '2026-05-24 07:28:50'),
(609, '2026-05-24', 3, 2, 3, 'dipanggil', NULL, '2026-05-24 08:34:09', NULL, '2026-05-24 07:39:37'),
(610, '2026-05-24', 5, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:27:49'),
(611, '2026-05-24', 6, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:27:55'),
(612, '2026-05-24', 9, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:29:57'),
(613, '2026-05-24', 10, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:30:05'),
(614, '2026-05-24', 11, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:30:20'),
(615, '2026-05-24', 7, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:31:20'),
(616, '2026-05-24', 13, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:32:01'),
(617, '2026-05-24', 12, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:32:28'),
(618, '2026-05-24', 14, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 08:33:38'),
(619, '2026-05-24', 15, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 09:05:59'),
(620, '2026-05-24', 17, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 09:43:36'),
(621, '2026-05-24', 18, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-24 09:43:51'),
(622, '2026-05-25', 1, 1, 1, 'dipanggil', NULL, '2026-05-25 02:27:01', NULL, '2026-05-25 02:26:21'),
(623, '2026-05-25', 2, 1, 2, 'selesai', 'lulus', '2026-05-25 02:39:12', '2026-05-25 02:39:37', '2026-05-25 02:26:21'),
(624, '2026-05-25', 3, 1, 2, 'dipanggil', NULL, '2026-05-25 02:39:37', NULL, '2026-05-25 02:26:21'),
(625, '2026-05-25', 4, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(626, '2026-05-25', 5, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(627, '2026-05-25', 6, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(628, '2026-05-25', 7, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(629, '2026-05-25', 8, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(630, '2026-05-25', 9, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(631, '2026-05-25', 10, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(632, '2026-05-25', 11, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(633, '2026-05-25', 12, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(634, '2026-05-25', 13, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(635, '2026-05-25', 14, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(636, '2026-05-25', 15, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(637, '2026-05-25', 16, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(638, '2026-05-25', 17, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(639, '2026-05-25', 18, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(640, '2026-05-25', 19, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(641, '2026-05-25', 20, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(642, '2026-05-25', 21, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(643, '2026-05-25', 22, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(644, '2026-05-25', 23, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(645, '2026-05-25', 24, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(646, '2026-05-25', 25, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(647, '2026-05-25', 26, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(648, '2026-05-25', 27, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(649, '2026-05-25', 28, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(650, '2026-05-25', 29, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(651, '2026-05-25', 30, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(652, '2026-05-25', 31, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(653, '2026-05-25', 32, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(654, '2026-05-25', 33, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(655, '2026-05-25', 34, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(656, '2026-05-25', 35, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(657, '2026-05-25', 36, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(658, '2026-05-25', 37, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(659, '2026-05-25', 38, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(660, '2026-05-25', 39, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(661, '2026-05-25', 40, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(662, '2026-05-25', 41, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(663, '2026-05-25', 42, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(664, '2026-05-25', 43, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(665, '2026-05-25', 44, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(666, '2026-05-25', 45, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(667, '2026-05-25', 46, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(668, '2026-05-25', 47, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(669, '2026-05-25', 48, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(670, '2026-05-25', 49, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(671, '2026-05-25', 50, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(672, '2026-05-25', 51, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(673, '2026-05-25', 52, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(674, '2026-05-25', 53, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(675, '2026-05-25', 54, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(676, '2026-05-25', 55, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(677, '2026-05-25', 56, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(678, '2026-05-25', 57, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(679, '2026-05-25', 58, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(680, '2026-05-25', 59, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(681, '2026-05-25', 60, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(682, '2026-05-25', 61, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(683, '2026-05-25', 62, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(684, '2026-05-25', 63, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(685, '2026-05-25', 64, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(686, '2026-05-25', 65, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(687, '2026-05-25', 66, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(688, '2026-05-25', 67, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(689, '2026-05-25', 68, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(690, '2026-05-25', 69, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(691, '2026-05-25', 70, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(692, '2026-05-25', 71, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(693, '2026-05-25', 72, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(694, '2026-05-25', 73, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21');
INSERT INTO `antrian` (`id`, `tanggal`, `nomor`, `fase`, `meja_id`, `status`, `hasil`, `dipanggil_at`, `selesai_at`, `created_at`) VALUES
(695, '2026-05-25', 74, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(696, '2026-05-25', 75, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(697, '2026-05-25', 76, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(698, '2026-05-25', 77, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(699, '2026-05-25', 78, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(700, '2026-05-25', 79, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(701, '2026-05-25', 80, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(702, '2026-05-25', 81, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(703, '2026-05-25', 82, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(704, '2026-05-25', 83, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(705, '2026-05-25', 84, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(706, '2026-05-25', 85, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(707, '2026-05-25', 86, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(708, '2026-05-25', 87, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(709, '2026-05-25', 88, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(710, '2026-05-25', 89, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(711, '2026-05-25', 90, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(712, '2026-05-25', 91, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(713, '2026-05-25', 92, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(714, '2026-05-25', 93, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(715, '2026-05-25', 94, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(716, '2026-05-25', 95, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(717, '2026-05-25', 96, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(718, '2026-05-25', 97, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(719, '2026-05-25', 98, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(720, '2026-05-25', 99, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(721, '2026-05-25', 100, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(722, '2026-05-25', 101, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(723, '2026-05-25', 102, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(724, '2026-05-25', 103, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(725, '2026-05-25', 104, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(726, '2026-05-25', 105, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(727, '2026-05-25', 106, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(728, '2026-05-25', 107, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(729, '2026-05-25', 108, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(730, '2026-05-25', 109, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(731, '2026-05-25', 110, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(732, '2026-05-25', 111, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(733, '2026-05-25', 112, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(734, '2026-05-25', 113, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(735, '2026-05-25', 114, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(736, '2026-05-25', 115, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(737, '2026-05-25', 116, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(738, '2026-05-25', 117, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(739, '2026-05-25', 118, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(740, '2026-05-25', 119, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(741, '2026-05-25', 120, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(742, '2026-05-25', 121, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(743, '2026-05-25', 122, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(744, '2026-05-25', 123, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(745, '2026-05-25', 124, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(746, '2026-05-25', 125, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(747, '2026-05-25', 126, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(748, '2026-05-25', 127, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(749, '2026-05-25', 128, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(750, '2026-05-25', 129, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(751, '2026-05-25', 130, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(752, '2026-05-25', 131, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(753, '2026-05-25', 132, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(754, '2026-05-25', 133, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(755, '2026-05-25', 134, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(756, '2026-05-25', 135, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(757, '2026-05-25', 136, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(758, '2026-05-25', 137, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(759, '2026-05-25', 138, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(760, '2026-05-25', 139, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(761, '2026-05-25', 140, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(762, '2026-05-25', 141, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(763, '2026-05-25', 142, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(764, '2026-05-25', 143, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(765, '2026-05-25', 144, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(766, '2026-05-25', 145, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(767, '2026-05-25', 146, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(768, '2026-05-25', 147, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(769, '2026-05-25', 148, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(770, '2026-05-25', 149, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(771, '2026-05-25', 150, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(772, '2026-05-25', 151, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(773, '2026-05-25', 152, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(774, '2026-05-25', 153, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(775, '2026-05-25', 154, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(776, '2026-05-25', 155, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:21'),
(777, '2026-05-25', 156, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(778, '2026-05-25', 157, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(779, '2026-05-25', 158, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(780, '2026-05-25', 159, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(781, '2026-05-25', 160, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(782, '2026-05-25', 161, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(783, '2026-05-25', 162, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(784, '2026-05-25', 163, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(785, '2026-05-25', 164, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(786, '2026-05-25', 165, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(787, '2026-05-25', 166, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(788, '2026-05-25', 167, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(789, '2026-05-25', 168, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(790, '2026-05-25', 169, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(791, '2026-05-25', 170, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(792, '2026-05-25', 171, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(793, '2026-05-25', 172, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(794, '2026-05-25', 173, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(795, '2026-05-25', 174, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(796, '2026-05-25', 175, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(797, '2026-05-25', 176, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(798, '2026-05-25', 177, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(799, '2026-05-25', 178, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(800, '2026-05-25', 179, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(801, '2026-05-25', 180, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(802, '2026-05-25', 181, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(803, '2026-05-25', 182, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(804, '2026-05-25', 183, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(805, '2026-05-25', 184, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(806, '2026-05-25', 185, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(807, '2026-05-25', 186, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(808, '2026-05-25', 187, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(809, '2026-05-25', 188, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(810, '2026-05-25', 189, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(811, '2026-05-25', 190, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(812, '2026-05-25', 191, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(813, '2026-05-25', 192, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(814, '2026-05-25', 193, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(815, '2026-05-25', 194, 1, 18, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(816, '2026-05-25', 195, 1, 19, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(817, '2026-05-25', 196, 1, 20, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(818, '2026-05-25', 197, 1, 1, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(819, '2026-05-25', 198, 1, 2, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(820, '2026-05-25', 199, 1, 3, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(821, '2026-05-25', 200, 1, 4, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:26:22'),
(822, '2026-05-25', 2, 2, NULL, 'menunggu', NULL, NULL, NULL, '2026-05-25 02:39:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `gelombang`
--

CREATE TABLE `gelombang` (
  `id` int(11) NOT NULL,
  `gelombang` tinyint(4) NOT NULL COMMENT '1 atau 2',
  `tanggal_buka` date NOT NULL,
  `tanggal_tutup` date NOT NULL,
  `tanggal_pengumuman` date NOT NULL,
  `tanggal_daftar_ulang_mulai` date DEFAULT NULL,
  `tanggal_daftar_ulang_selesai` date DEFAULT NULL,
  `kuota_per_jurusan` int(11) NOT NULL DEFAULT 36,
  `persen_gelombang` decimal(5,2) NOT NULL DEFAULT 70.00 COMMENT 'Glm1=70, Glm2=30',
  `kuota_glm` int(11) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `is_hasil_published` tinyint(1) NOT NULL DEFAULT 0,
  `hasil_published_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `jadwal_pendaftaran_text` text DEFAULT NULL,
  `jadwal_pengumuman_text` text DEFAULT NULL,
  `jadwal_daftar_ulang_text` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `gelombang`
--

INSERT INTO `gelombang` (`id`, `gelombang`, `tanggal_buka`, `tanggal_tutup`, `tanggal_pengumuman`, `tanggal_daftar_ulang_mulai`, `tanggal_daftar_ulang_selesai`, `kuota_per_jurusan`, `persen_gelombang`, `kuota_glm`, `is_published`, `is_hasil_published`, `hasil_published_at`, `published_at`, `created_at`, `jadwal_pendaftaran_text`, `jadwal_pengumuman_text`, `jadwal_daftar_ulang_text`) VALUES
(1, 1, '2026-06-15', '2026-06-30', '2026-07-01', '2026-07-02', '2026-07-03', 36, 70.00, 26, 1, 0, NULL, '2026-05-25 01:26:08', '2026-05-06 03:22:32', '15 - 29 Juni 2026 | 08:00 - 16:00\r\n30 Juni 2026 | 08:00 - 12:00', '1 Juli 2026 | 08:00 - 16:00', '3 - 4 Juli 2026 | 08:00 - 16:00'),
(2, 2, '2026-07-08', '2026-07-09', '2026-07-10', '2026-07-10', '2026-07-10', 36, 30.00, 10, 0, 0, NULL, NULL, '2026-05-06 03:22:32', '8 Juli 2026 | 08:00 - 16:00\n9 Juli 2026 | 08:00 - 12:00', '10 Juli 2026 | 08:00 - 16:00', '10 Juli 2026 | 08:00 - 16:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `meja`
--

CREATE TABLE `meja` (
  `id` int(11) NOT NULL,
  `nomor_meja` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `fase` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=Cek Berkas, 2=Input Data & Surat Tanda Daftar',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `meja`
--

INSERT INTO `meja` (`id`, `nomor_meja`, `nama`, `fase`, `is_active`, `created_at`) VALUES
(1, 1, 'Loket 1', 1, 1, '2026-05-21 00:39:10'),
(2, 2, 'Loket 2', 1, 1, '2026-05-21 00:39:10'),
(3, 3, 'Loket 3', 2, 1, '2026-05-21 00:39:10'),
(4, 4, 'Loket 4', 2, 1, '2026-05-21 00:39:10'),
(18, 5, 'Loket 5', 2, 1, '2026-05-22 00:47:35'),
(19, 6, 'Loket6', 1, 1, '2026-05-25 02:25:04'),
(20, 7, 'Loket 7', 1, 1, '2026-05-25 02:25:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pendaftar`
--

CREATE TABLE `pendaftar` (
  `id` int(11) NOT NULL,
  `no_pendaftaran` varchar(20) NOT NULL,
  `gelombang` tinyint(4) NOT NULL COMMENT '1 atau 2',
  `nama` varchar(255) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `usia` int(11) NOT NULL COMMENT 'Usia dalam tahun saat mendaftar',
  `jenis_kelamin` enum('L','P') NOT NULL,
  `asal_sekolah` varchar(255) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `jurusan` enum('Rekayasa Perangkat Lunak (RPL)','Teknik Komputer dan Jaringan (TKJ)','Asisten Keperawatan (AP)','Tata Kecantikan Kulit dan Rambut (TKKR)') NOT NULL,
  `sistem_pendidikan` enum('reguler','pkbm') NOT NULL DEFAULT 'reguler' COMMENT 'reguler=SMP biasa; pkbm=Paket B',
  `nilai_raport` decimal(5,2) NOT NULL COMMENT 'Rata-rata raport smt 1-6 (bobot 70%)',
  `nilai_tka` decimal(5,2) NOT NULL COMMENT 'Nilai TKA (bobot 30%)',
  `nilai_akhir` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT '(raport*70%) + (tka*30%), dihitung saat input',
  `lolos_usia` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=lolos, 0=gugur karena usia>21',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=dijamin diterima',
  `status` enum('diproses','terima','gugur') NOT NULL DEFAULT 'diproses',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pendaftar`
--

INSERT INTO `pendaftar` (`id`, `no_pendaftaran`, `gelombang`, `nama`, `nisn`, `tanggal_lahir`, `usia`, `jenis_kelamin`, `asal_sekolah`, `no_telp`, `alamat`, `jurusan`, `sistem_pendidikan`, `nilai_raport`, `nilai_tka`, `nilai_akhir`, `lolos_usia`, `is_pinned`, `status`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 'PPDB-2026-G1-0001', 1, 'Mujammad Sharil Al Farizi', '8876390', '2018-04-15', 8, 'L', 'Smpn Tanjung Pura Karawang', '0816874589', 'Jl. Kp. Bnadan Rt 008/010 Kota Baru Jkaarta Timur', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 80.80, 82.00, 81.1600, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-18 02:35:50', '2026-05-23 04:23:47'),
(2, 'PPDB-26-001', 1, 'Rizky Aditya Pratama', '0091234501', '2009-03-15', 16, 'L', 'SMP Negeri 12 Jakarta Timur', '081234560001', 'Jl. Rawa Jaya No.5, Duren Sawit', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 87.50, 82.00, 85.8500, 1, 1, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(3, 'PPDB-26-002', 1, 'Nadia Putri Rahayu', '0091234502', '2009-07-22', 16, 'P', 'SMP Negeri 5 Jakarta Timur', '081234560002', 'Jl. Cipinang Besar No.12', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 91.20, 88.50, 90.3900, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(4, 'PPDB-26-003', 1, 'Farhan Hidayatullah', '0091234503', '2010-01-08', 16, 'L', 'SMP Islam Al-Azhar 13', '081234560003', 'Jl. Penggilingan No.3, Cakung', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 78.00, 75.50, 77.2500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(5, 'PPDB-26-004', 1, 'Siti Rahmawati', '0091234504', '2009-11-30', 16, 'P', 'SMP Muhammadiyah 9', '081234560004', 'Jl. Klender No.7, Duren Sawit', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 83.75, 79.00, 82.3250, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(6, 'PPDB-26-005', 1, 'Dimas Cahya Nugraha', '0091234505', '2010-05-17', 15, 'L', 'SMP Negeri 28 Jakarta Timur', '081234560005', 'Jl. Pondok Bambu No.20, Duren Sawit', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 72.50, 68.00, 71.1500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(7, 'PPDB-26-006', 1, 'Aulia Fitri Handayani', '0091234506', '2009-09-03', 16, 'P', 'SMP Negeri 7 Jakarta Timur', '081234560006', 'Jl. Cipinang Muara No.9', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 88.00, 91.50, 89.0500, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(8, 'PPDB-26-007', 1, 'Bagas Setiawan', '0091234507', '2010-02-14', 16, 'L', 'SMP Negeri 220 Jakarta', '081234560007', 'Jl. Kramat Jati No.15', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 65.00, 60.00, 63.5000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(9, 'PPDB-26-008', 1, 'Melinda Sari', '0091234508', '2003-06-25', 22, 'P', 'PKBM Cahaya Bangsa', '081234560008', 'Jl. Matraman No.8', 'Rekayasa Perangkat Lunak (RPL)', 'pkbm', 80.00, 76.00, 78.8000, 0, 0, 'gugur', 'Gugur: usia melebihi 21 tahun', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(10, 'PPDB-26-009', 1, 'Yoga Pratomo', '0091234509', '2009-12-10', 16, 'L', 'SMP Negeri 103 Jakarta', '081234560009', 'Jl. Rawa Bebek No.2', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 76.25, 71.00, 74.6750, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(11, 'PPDB-26-010', 1, 'Putri Anggraini', '0091234510', '2010-08-19', 15, 'P', 'SMP Terbuka 103', '081234560010', 'Jl. Bali Mester No.4, Jatinegara', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 93.00, 90.00, 92.1000, 1, 1, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(12, 'PPDB-26-011', 1, 'Andika Kusuma', '0091234511', '2009-04-11', 17, 'L', 'SMP Negeri 6 Jakarta Timur', '081234560011', 'Jl. Utan Kayu No.33', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 85.50, 80.00, 83.8500, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(13, 'PPDB-26-012', 1, 'Rini Oktaviani', '0091234512', '2009-10-05', 16, 'P', 'SMP Negeri 17 Jakarta', '081234560012', 'Jl. Pisangan No.11, Cakung', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 79.00, 83.50, 80.3500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(14, 'PPDB-26-013', 1, 'Hendra Wijaya', '0091234513', '2010-03-28', 16, 'L', 'SMP Islam Andalusia', '081234560013', 'Jl. Penggilingan Raya No.7', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 90.00, 87.00, 89.1000, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(15, 'PPDB-26-014', 1, 'Laila Nurhasanah', '0091234514', '2009-06-14', 16, 'P', 'SMP Negeri 45 Jakarta', '081234560014', 'Jl. Palmeriam No.5', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 68.75, 65.00, 67.6250, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(16, 'PPDB-26-015', 1, 'Wahyu Santoso', '0091234515', '2010-11-02', 15, 'L', 'SMP Negeri 8 Jakarta', '081234560015', 'Jl. Cipinang Cempedak No.9', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 82.00, 78.50, 80.9500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(17, 'PPDB-26-016', 1, 'Dewi Permatasari', '0091234516', '2009-08-23', 16, 'P', 'PKBM Pelita Harapan', '081234560016', 'Jl. Klender Baru No.6', 'Teknik Komputer dan Jaringan (TKJ)', 'pkbm', 74.50, 70.00, 73.1500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(18, 'PPDB-26-017', 1, 'Ridwan Maulana', '0091234517', '2002-09-30', 23, 'L', 'PKBM Maju Bersama', '081234560017', 'Jl. Cipinang No.14', 'Teknik Komputer dan Jaringan (TKJ)', 'pkbm', 71.00, 68.00, 70.1000, 0, 0, 'gugur', 'Gugur: usia melebihi 21 tahun', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(19, 'PPDB-26-018', 1, 'Isna Fitriani', '0091234518', '2010-07-16', 15, 'P', 'SMP Negeri 266 Jakarta', '081234560018', 'Jl. Rawamangun Muka No.2', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 86.25, 84.00, 85.5750, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(20, 'PPDB-26-019', 1, 'Fajar Ramadhan', '0091234519', '2009-02-07', 17, 'L', 'SMP Negeri 49 Jakarta', '081234560019', 'Jl. Buaran No.18, Duren Sawit', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 77.50, 73.50, 76.3000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(21, 'PPDB-26-020', 1, 'Sinta Maharani', '0091234520', '2010-04-09', 16, 'P', 'SMP Negeri 115 Jakarta', '081234560020', 'Jl. Cipinang Muara No.21', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 94.00, 92.00, 93.4000, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(22, 'PPDB-26-021', 1, 'Nur Halimah', '0091234521', '2009-05-20', 17, 'P', 'SMP Negeri 14 Jakarta', '081234560021', 'Jl. Cipinang Indah No.3', 'Asisten Keperawatan (AP)', 'reguler', 88.00, 85.00, 87.1000, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(23, 'PPDB-26-022', 1, 'Muhammad Iqbal', '0091234522', '2009-09-17', 16, 'L', 'SMP Negeri 38 Jakarta', '081234560022', 'Jl. Pondok Kelapa No.5', 'Asisten Keperawatan (AP)', 'reguler', 73.00, 70.00, 72.1000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(24, 'PPDB-26-023', 1, 'Ani Sulistyowati', '0091234523', '2010-01-25', 16, 'P', 'SMP Negeri 55 Jakarta', '081234560023', 'Jl. Rawa Jaya No.12', 'Asisten Keperawatan (AP)', 'reguler', 91.50, 89.00, 90.7500, 1, 1, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(25, 'PPDB-26-024', 1, 'Budi Santoso', '0091234524', '2010-06-08', 15, 'L', 'PKBM Cahaya Ilmu', '081234560024', 'Jl. Klender No.19', 'Asisten Keperawatan (AP)', 'pkbm', 69.00, 66.00, 68.1000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(26, 'PPDB-26-025', 1, 'Yuni Astuti', '0091234525', '2009-11-12', 16, 'P', 'SMP Negeri 187 Jakarta', '081234560025', 'Jl. Kramat Jati No.8', 'Asisten Keperawatan (AP)', 'reguler', 82.75, 80.00, 81.9250, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(27, 'PPDB-26-026', 1, 'Eko Prasetyo', '0091234526', '2010-10-14', 15, 'L', 'SMP Negeri 74 Jakarta', '081234560026', 'Jl. Cipinang Muara No.4', 'Asisten Keperawatan (AP)', 'reguler', 78.50, 74.00, 77.1500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(28, 'PPDB-26-027', 1, 'Fitria Rahmadani', '0091234527', '2009-03-29', 17, 'P', 'SMP Negeri 21 Jakarta', '081234560027', 'Jl. Buaran Raya No.7', 'Asisten Keperawatan (AP)', 'reguler', 86.00, 82.50, 84.9500, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(29, 'PPDB-26-028', 1, 'Agus Hermawan', '0091234528', '2010-08-31', 15, 'L', 'SMP Negeri 193 Jakarta', '081234560028', 'Jl. Duren Sawit No.22', 'Asisten Keperawatan (AP)', 'reguler', 63.50, 60.00, 62.4500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(30, 'PPDB-26-029', 1, 'Wulandari Kusuma', '0091234529', '2009-07-04', 16, 'P', 'SMP Negeri 3 Jakarta Timur', '081234560029', 'Jl. Matraman Raya No.45', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 89.00, 86.00, 88.1000, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(31, 'PPDB-26-030', 1, 'Rina Septiani', '0091234530', '2010-02-18', 16, 'P', 'SMP Negeri 30 Jakarta', '081234560030', 'Jl. Cipinang Besar No.3', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 76.00, 72.00, 74.8000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(32, 'PPDB-26-031', 1, 'Linda Permata', '0091234531', '2009-12-22', 16, 'P', 'SMP Muhammadiyah 17', '081234560031', 'Jl. Pondok Bambu No.11', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 92.50, 90.00, 91.7500, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(33, 'PPDB-26-032', 1, 'Rena Anggraita', '0091234532', '2010-05-05', 15, 'P', 'SMP Negeri 111 Jakarta', '081234560032', 'Jl. Kramat Jati No.33', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 70.50, 67.00, 69.4500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(34, 'PPDB-26-033', 1, 'Sari Dewi', '0091234533', '2009-08-15', 16, 'P', 'PKBM Insan Mandiri', '081234560033', 'Jl. Rawa Jaya No.17', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'pkbm', 81.00, 77.50, 79.9500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(35, 'PPDB-26-034', 1, 'Maya Rahayu', '0091234534', '2010-09-27', 15, 'P', 'SMP Negeri 256 Jakarta', '081234560034', 'Jl. Cipinang Cempedak No.6', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 84.00, 81.00, 83.1000, 1, 0, 'terima', NULL, '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(36, 'PPDB-26-035', 1, 'Novita Sari', '0091234535', '2002-04-03', 24, 'P', 'PKBM Bina Insani', '081234560035', 'Jl. Cakung No.9', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'pkbm', 67.00, 64.00, 66.1000, 0, 0, 'gugur', 'Gugur: usia melebihi 21 tahun', '2026-05-21 01:42:33', '2026-05-23 04:23:47'),
(37, 'PPDB-26-036', 2, 'Arif Budiman', '0091234536', '2009-06-30', 16, 'L', 'SMP Negeri 22 Jakarta', '081234560036', 'Jl. Duren Sawit No.8', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 85.00, 82.00, 84.1000, 1, 0, 'diproses', NULL, '2026-05-21 01:42:33', '2026-05-21 01:42:33'),
(38, 'PPDB-26-037', 2, 'Citra Kusumawati', '0091234537', '2010-03-11', 16, 'P', 'SMP Negeri 42 Jakarta', '081234560037', 'Jl. Cipinang Indah No.8', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 79.50, 76.00, 78.4500, 1, 0, 'diproses', NULL, '2026-05-21 01:42:33', '2026-05-21 01:42:33'),
(39, 'PPDB-26-038', 2, 'Dani Kurniawan', '0091234538', '2009-10-19', 16, 'L', 'SMP Negeri 99 Jakarta', '081234560038', 'Jl. Rawamangun No.14', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 88.50, 85.50, 87.6000, 1, 0, 'diproses', NULL, '2026-05-21 01:42:33', '2026-05-21 01:42:33'),
(40, 'PPDB-26-039', 2, 'Elsa Fitriani', '0091234539', '2010-01-14', 16, 'P', 'SMP Negeri 61 Jakarta', '081234560039', 'Jl. Buaran No.22', 'Asisten Keperawatan (AP)', 'reguler', 83.00, 80.00, 82.1000, 1, 0, 'diproses', NULL, '2026-05-21 01:42:33', '2026-05-21 01:42:33'),
(41, 'PPDB-26-040', 2, 'Fira Kusuma', '0091234540', '2009-11-28', 16, 'P', 'SMP Negeri 78 Jakarta', '081234560040', 'Jl. Kali Malang No.5', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 90.00, 88.00, 89.4000, 1, 0, 'diproses', NULL, '2026-05-21 01:42:33', '2026-05-21 01:42:33'),
(42, 'G1-2026-001', 1, 'Andi Firmansyah', '3171234560001', '2009-03-12', 17, 'L', 'SMPN 1 Jakarta Pusat', '081234560001', 'Jl. Cempaka No.1, Jakarta Pusat', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 89.50, 91.00, 90.0500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(43, 'G1-2026-002', 1, 'Budi Prasetyo', '3171234560002', '2009-07-22', 16, 'L', 'SMPN 2 Jakarta Pusat', '081234560002', 'Jl. Mawar No.2, Jakarta Pusat', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 88.00, 90.50, 88.9500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(44, 'G1-2026-003', 1, 'Cahya Permata Sari', '3171234560003', '2009-01-05', 17, 'P', 'SMPN 3 Jakarta Barat', '081234560003', 'Jl. Anggrek No.3, Jakarta Barat', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 91.00, 88.00, 90.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(45, 'G1-2026-004', 1, 'Deni Kurniawan', '3171234560004', '2009-11-30', 16, 'L', 'SMPN 5 Depok', '081234560004', 'Jl. Flamboyan No.4, Depok', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 87.50, 89.00, 88.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(46, 'G1-2026-005', 1, 'Eka Putri Lestari', '3171234560005', '2008-06-18', 17, 'P', 'SMPN 10 Bekasi', '081234560005', 'Jl. Teratai No.5, Bekasi', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 86.00, 92.00, 88.8000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(47, 'G1-2026-006', 1, 'Fajar Nugroho', '3171234560006', '2009-04-14', 17, 'L', 'MTs Negeri 1 Jakarta', '081234560006', 'Jl. Kenanga No.6, Jakarta Selatan', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 90.00, 85.00, 88.5000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(48, 'G1-2026-007', 1, 'Gita Rahayu', '3171234560007', '2009-09-09', 16, 'P', 'SMPN 7 Tangerang', '081234560007', 'Jl. Melati No.7, Tangerang', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 85.00, 90.00, 87.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(49, 'G1-2026-008', 1, 'Hendra Wijaya', '3171234560008', '2009-02-28', 17, 'L', 'SMPN 8 Jakarta Timur', '081234560008', 'Jl. Dahlia No.8, Jakarta Timur', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 88.50, 86.00, 87.7500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(50, 'G1-2026-009', 1, 'Indah Setiawati', '3171234560009', '2008-12-12', 17, 'P', 'SMPN 4 Bogor', '081234560009', 'Jl. Sakura No.9, Bogor', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 84.00, 89.00, 85.9000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(51, 'G1-2026-010', 1, 'Joko Santoso', '3171234560010', '2009-05-20', 17, 'L', 'SMP Islam Al-Azhar', '081234560010', 'Jl. Palm No.10, Jakarta Selatan', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 83.50, 91.00, 86.7500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(52, 'G1-2026-011', 1, 'Kevin Adrianto', '3171234560011', '2009-08-03', 16, 'L', 'SMPN 11 Jakarta Barat', '081234560011', 'Jl. Pinus No.11, Jakarta Barat', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 87.00, 84.00, 86.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(53, 'G1-2026-012', 1, 'Linda Wulandari', '3171234560012', '2009-03-27', 17, 'P', 'SMPN 6 Depok', '081234560012', 'Jl. Kamboja No.12, Depok', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 86.50, 85.00, 86.0500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(54, 'G1-2026-013', 1, 'Muhammad Fauzi', '3171234560013', '2009-10-10', 16, 'L', 'MTs Negeri 2 Jakarta', '081234560013', 'Jl. Cemara No.13, Jakarta Utara', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 85.50, 87.00, 86.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(55, 'G1-2026-014', 1, 'Nadia Kusumawati', '3171234560014', '2009-01-15', 17, 'P', 'SMPN 9 Tangerang Selatan', '081234560014', 'Jl. Beringin No.14, Tangerang Selatan', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 84.50, 88.50, 86.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(56, 'G1-2026-015', 1, 'Oscar Pratama', '3171234560015', '2009-06-06', 16, 'L', 'SMPN 12 Bekasi', '081234560015', 'Jl. Akasia No.15, Bekasi', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 83.00, 88.00, 85.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(57, 'G1-2026-016', 1, 'Putri Amelia', '3171234560016', '2009-07-07', 16, 'P', 'SMP Kristen BPK', '081234560016', 'Jl. Bambu No.16, Jakarta Timur', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 82.50, 89.00, 84.9500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(58, 'G1-2026-017', 1, 'Rizky Hamdani', '3171234560017', '2009-04-22', 17, 'L', 'SMPN 14 Jakarta Selatan', '081234560017', 'Jl. Rambutan No.17, Jakarta Selatan', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 86.00, 82.00, 84.8000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(59, 'G1-2026-018', 1, 'Sari Dewi Anggraeni', '3171234560018', '2009-02-14', 17, 'P', 'SMPN 16 Jakarta Barat', '081234560018', 'Jl. Mangga No.18, Jakarta Barat', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 81.50, 90.00, 84.6000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(60, 'G1-2026-019', 1, 'Tri Wahyudi', '3171234560019', '2008-11-11', 17, 'L', 'MTs Al-Falah', '081234560019', 'Jl. Jeruk No.19, Depok', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 80.00, 91.00, 83.3000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(61, 'G1-2026-020', 1, 'Upi Ratnasari', '3171234560020', '2009-09-25', 16, 'P', 'SMPN 20 Jakarta', '081234560020', 'Jl. Nangka No.20, Jakarta Utara', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 85.00, 80.00, 83.5000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(62, 'G1-2026-021', 1, 'Vicky Andriyanto', '3171234560021', '2009-03-03', 17, 'L', 'SMPN 22 Bogor', '081234560021', 'Jl. Durian No.21, Bogor', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 82.00, 85.00, 83.1500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(63, 'G1-2026-022', 1, 'Wulan Safitri', '3171234560022', '2009-06-30', 16, 'P', 'SMPN 18 Bekasi', '081234560022', 'Jl. Pepaya No.22, Bekasi', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 83.50, 81.00, 82.7500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(64, 'G1-2026-023', 1, 'Xander Putra', '3171234560023', '2009-01-18', 17, 'L', 'SMP Cendekia', '081234560023', 'Jl. Sirsak No.23, Tangerang', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 80.50, 86.00, 82.1500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(65, 'G1-2026-024', 1, 'Yanti Kurniasih', '3171234560024', '2009-08-08', 16, 'P', 'SMPN 25 Jakarta Timur', '081234560024', 'Jl. Jambu No.24, Jakarta Timur', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 81.00, 84.00, 82.1000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(66, 'G1-2026-025', 1, 'Zaky Maulana', '3171234560025', '2009-05-15', 17, 'L', 'MTs Darul Ulum', '081234560025', 'Jl. Sawo No.25, Jakarta Selatan', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 80.00, 85.00, 81.5000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(67, 'G1-2026-026', 1, 'Aditya Pramudya', '3171234560026', '2009-02-02', 17, 'L', 'SMPN 30 Jakarta Barat', '081234560026', 'Jl. Belimbing No.26, Jakarta Barat', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 82.00, 80.00, 81.4000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(68, 'G1-2026-027', 1, 'Bagus Setiawan', '3171234560027', '2009-04-11', 17, 'L', 'SMPN 33 Depok', '081234560027', 'Jl. Apel No.27, Depok', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 78.00, 82.00, 79.2000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(69, 'G1-2026-028', 1, 'Cici Ambarwati', '3171234560028', '2009-07-19', 16, 'P', 'SMPN 35 Bekasi', '081234560028', 'Jl. Ananas No.28, Bekasi', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 75.50, 80.00, 77.1500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(70, 'G1-2026-029', 1, 'Dito Firmanto', '3171234560029', '2009-10-01', 16, 'L', 'MTs Nurul Huda', '081234560029', 'Jl. Alpukat No.29, Tangerang Selatan', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 73.00, 78.00, 74.5000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(71, 'G1-2026-030', 1, 'Elisa Permata', '3171234560030', '2009-12-20', 16, 'P', 'SMPN 40 Jakarta Utara', '081234560030', 'Jl. Leci No.30, Jakarta Utara', 'Rekayasa Perangkat Lunak (RPL)', 'reguler', 70.00, 75.00, 71.5000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(72, 'G1-2026-031', 1, 'Fahri Ramadhan', '3171234560031', '2009-03-08', 17, 'L', 'SMPN 2 Bekasi', '081234560031', 'Jl. Kelapa No.31, Bekasi', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 90.00, 92.00, 90.6000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(73, 'G1-2026-032', 1, 'Galih Setyawan', '3171234560032', '2009-06-14', 17, 'L', 'SMPN 3 Depok', '081234560032', 'Jl. Kopi No.32, Depok', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 89.00, 90.00, 89.3000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(74, 'G1-2026-033', 1, 'Hani Fitriani', '3171234560033', '2009-01-22', 17, 'P', 'SMPN 7 Jakarta Timur', '081234560033', 'Jl. Coklat No.33, Jakarta Timur', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 88.50, 89.50, 88.8000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(75, 'G1-2026-034', 1, 'Iqbal Fathoni', '3171234560034', '2009-09-17', 16, 'L', 'MTs Negeri 3 Jakarta', '081234560034', 'Jl. Vanili No.34, Jakarta Selatan', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 87.00, 91.00, 88.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(76, 'G1-2026-035', 1, 'Julia Anggraini', '3171234560035', '2009-04-29', 17, 'P', 'SMPN 11 Bogor', '081234560035', 'Jl. Cengkeh No.35, Bogor', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 86.50, 88.00, 87.1500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(77, 'G1-2026-036', 1, 'Krisna Bayu', '3171234560036', '2009-02-11', 17, 'L', 'SMPN 15 Tangerang', '081234560036', 'Jl. Pala No.36, Tangerang', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 85.00, 89.00, 86.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(78, 'G1-2026-037', 1, 'Laila Nurhayati', '3171234560037', '2009-07-05', 16, 'P', 'SMPN 19 Jakarta Barat', '081234560037', 'Jl. Kayu Manis No.37, Jakarta Barat', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 84.00, 90.00, 85.8000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(79, 'G1-2026-038', 1, 'Mirza Akhyar', '3171234560038', '2009-11-23', 16, 'L', 'SMP IT Al-Kautsar', '081234560038', 'Jl. Lada No.38, Jakarta Utara', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 87.00, 83.00, 85.8000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(80, 'G1-2026-039', 1, 'Nisa Ramadhani', '3171234560039', '2009-03-31', 17, 'P', 'SMPN 22 Bekasi', '081234560039', 'Jl. Kayu Putih No.39, Bekasi', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 83.00, 90.00, 85.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(81, 'G1-2026-040', 1, 'Omar Habibi', '3171234560040', '2009-08-16', 16, 'L', 'SMPN 24 Depok', '081234560040', 'Jl. Jati No.40, Depok', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 86.00, 83.00, 85.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(82, 'G1-2026-041', 1, 'Priya Utami', '3171234560041', '2009-05-07', 17, 'P', 'SMPN 26 Jakarta Selatan', '081234560041', 'Jl. Meranti No.41, Jakarta Selatan', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 84.50, 86.00, 85.0500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(83, 'G1-2026-042', 1, 'Qori Hidayatullah', '3171234560042', '2009-01-28', 17, 'L', 'MTs Al-Ikhlas', '081234560042', 'Jl. Ulin No.42, Tangerang Selatan', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 82.00, 90.00, 84.4000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(84, 'G1-2026-043', 1, 'Reni Oktaviani', '3171234560043', '2009-10-04', 16, 'P', 'SMPN 28 Jakarta Timur', '081234560043', 'Jl. Mahoni No.43, Jakarta Timur', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 85.00, 83.00, 84.5000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(85, 'G1-2026-044', 1, 'Surya Pratama', '3171234560044', '2009-06-21', 17, 'L', 'SMPN 30 Bogor', '081234560044', 'Jl. Sonokeling No.44, Bogor', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 83.50, 86.00, 84.2500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(86, 'G1-2026-045', 1, 'Tania Putri', '3171234560045', '2009-02-17', 17, 'P', 'SMPN 32 Jakarta Barat', '081234560045', 'Jl. Merbau No.45, Jakarta Barat', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 82.50, 87.00, 84.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(87, 'G1-2026-046', 1, 'Umar Salim', '3171234560046', '2009-09-12', 16, 'L', 'SMP Global Mandiri', '081234560046', 'Jl. Sengon No.46, Depok', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 80.00, 90.00, 83.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(88, 'G1-2026-047', 1, 'Vera Handayani', '3171234560047', '2009-04-04', 17, 'P', 'SMPN 35 Bekasi', '081234560047', 'Jl. Cendana No.47, Bekasi', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 84.00, 81.00, 83.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(89, 'G1-2026-048', 1, 'Wahyu Nurdiana', '3171234560048', '2009-07-26', 16, 'L', 'MTs Negeri 4 Tangerang', '081234560048', 'Jl. Waru No.48, Tangerang', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 81.00, 87.00, 83.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(90, 'G1-2026-049', 1, 'Xena Oktarina', '3171234560049', '2009-12-08', 16, 'P', 'SMPN 38 Jakarta Utara', '081234560049', 'Jl. Aren No.49, Jakarta Utara', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 83.00, 83.00, 83.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(91, 'G1-2026-050', 1, 'Yusuf Alfarizi', '3171234560050', '2009-05-19', 17, 'L', 'SMPN 40 Jakarta Selatan', '081234560050', 'Jl. Bambu Kuning No.50, Jakarta Selatan', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 82.00, 85.00, 83.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(92, 'G1-2026-051', 1, 'Zahra Nabila', '3171234560051', '2009-03-14', 17, 'P', 'SMPN 42 Jakarta Timur', '081234560051', 'Jl. Rotan No.51, Jakarta Timur', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 80.50, 86.00, 82.1500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(93, 'G1-2026-052', 1, 'Aldo Pratama', '3171234560052', '2009-08-30', 16, 'L', 'SMPN 44 Jakarta Barat', '081234560052', 'Jl. Besi No.52, Jakarta Barat', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 81.00, 84.00, 82.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(94, 'G1-2026-053', 1, 'Bella Saputri', '3171234560053', '2009-11-06', 16, 'P', 'MTs Darul Falah', '081234560053', 'Jl. Tembaga No.53, Bogor', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 80.00, 85.00, 81.5000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(95, 'G1-2026-054', 1, 'Candra Mustofa', '3171234560054', '2009-02-24', 17, 'L', 'SMPN 46 Depok', '081234560054', 'Jl. Nikel No.54, Depok', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 81.50, 82.00, 81.6500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(96, 'G1-2026-055', 1, 'Diah Ayu Puspita', '3171234560055', '2009-07-11', 16, 'P', 'SMPN 48 Bekasi', '081234560055', 'Jl. Perak No.55, Bekasi', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 80.00, 84.00, 81.2000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(97, 'G1-2026-056', 1, 'Eko Cahyono', '3171234560056', '2009-04-18', 17, 'L', 'SMP Budi Mulia', '081234560056', 'Jl. Timah No.56, Tangerang Selatan', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 79.00, 84.00, 80.5000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(98, 'G1-2026-057', 1, 'Fina Ardhiani', '3171234560057', '2009-09-03', 16, 'P', 'SMPN 50 Jakarta Utara', '081234560057', 'Jl. Emas No.57, Jakarta Utara', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 76.00, 80.00, 77.2000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(99, 'G1-2026-058', 1, 'Gilang Saputra', '3171234560058', '2009-01-09', 17, 'L', 'SMPN 52 Jakarta Selatan', '081234560058', 'Jl. Platina No.58, Jakarta Selatan', 'Teknik Komputer dan Jaringan (TKJ)', 'reguler', 72.00, 75.00, 73.2000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(100, 'G1-2026-059', 1, 'Hilda Ratnasari', '3171234560059', '2009-05-25', 17, 'P', 'SMPN 1 Tangerang', '081234560059', 'Jl. Seruni No.59, Tangerang', 'Asisten Keperawatan (AP)', 'reguler', 92.00, 90.00, 91.4000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(101, 'G1-2026-060', 1, 'Irma Damayanti', '3171234560060', '2009-03-02', 17, 'P', 'SMPN 3 Bogor', '081234560060', 'Jl. Marigold No.60, Bogor', 'Asisten Keperawatan (AP)', 'reguler', 91.50, 89.00, 90.7500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(102, 'G1-2026-061', 1, 'Johan Suryadi', '3171234560061', '2009-07-28', 16, 'L', 'SMPN 5 Jakarta Barat', '081234560061', 'Jl. Lavender No.61, Jakarta Barat', 'Asisten Keperawatan (AP)', 'reguler', 90.00, 91.00, 90.3000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(103, 'G1-2026-062', 1, 'Kartika Sari', '3171234560062', '2009-02-06', 17, 'P', 'SMPN 7 Jakarta Pusat', '081234560062', 'Jl. Lily No.62, Jakarta Pusat', 'Asisten Keperawatan (AP)', 'reguler', 89.00, 92.00, 90.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(104, 'G1-2026-063', 1, 'Lukman Hakim', '3171234560063', '2009-10-14', 16, 'L', 'MTs Negeri 5 Bekasi', '081234560063', 'Jl. Lotus No.63, Bekasi', 'Asisten Keperawatan (AP)', 'reguler', 88.50, 90.00, 89.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(105, 'G1-2026-064', 1, 'Maya Fitriani', '3171234560064', '2009-04-20', 17, 'P', 'SMPN 9 Depok', '081234560064', 'Jl. Dahlia No.64, Depok', 'Asisten Keperawatan (AP)', 'reguler', 87.00, 91.00, 88.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(106, 'G1-2026-065', 1, 'Nurul Aini', '3171234560065', '2009-08-07', 16, 'P', 'SMPN 11 Tangerang Selatan', '081234560065', 'Jl. Aster No.65, Tangerang Selatan', 'Asisten Keperawatan (AP)', 'reguler', 86.00, 90.50, 87.5500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(107, 'G1-2026-066', 1, 'Okta Wijayanti', '3171234560066', '2009-01-31', 17, 'P', 'SMP Muhammadiyah 1', '081234560066', 'Jl. Gladiol No.66, Jakarta Timur', 'Asisten Keperawatan (AP)', 'reguler', 88.00, 86.00, 87.4000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(108, 'G1-2026-067', 1, 'Pandu Kusuma', '3171234560067', '2009-06-08', 17, 'L', 'SMPN 13 Jakarta Selatan', '081234560067', 'Jl. Iris No.67, Jakarta Selatan', 'Asisten Keperawatan (AP)', 'reguler', 85.50, 90.00, 87.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(109, 'G1-2026-068', 1, 'Qisti Amalia', '3171234560068', '2009-11-15', 16, 'P', 'SMPN 15 Bogor', '081234560068', 'Jl. Magnolia No.68, Bogor', 'Asisten Keperawatan (AP)', 'reguler', 86.50, 87.00, 86.6500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(110, 'G1-2026-069', 1, 'Rangga Saputra', '3171234560069', '2009-03-23', 17, 'L', 'SMPN 17 Jakarta Utara', '081234560069', 'Jl. Melur No.69, Jakarta Utara', 'Asisten Keperawatan (AP)', 'reguler', 84.00, 91.00, 86.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(111, 'G1-2026-070', 1, 'Silvia Candra', '3171234560070', '2009-09-01', 16, 'P', 'MTs Al-Hikmah', '081234560070', 'Jl. Peony No.70, Depok', 'Asisten Keperawatan (AP)', 'reguler', 85.00, 88.00, 86.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(112, 'G1-2026-071', 1, 'Toni Aryadi', '3171234560071', '2009-05-27', 17, 'L', 'SMPN 19 Bekasi', '081234560071', 'Jl. Snapdragon No.71, Bekasi', 'Asisten Keperawatan (AP)', 'reguler', 83.50, 90.00, 85.6500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(113, 'G1-2026-072', 1, 'Uswah Hasanah', '3171234560072', '2009-02-19', 17, 'P', 'SMPN 21 Tangerang', '081234560072', 'Jl. Carnation No.72, Tangerang', 'Asisten Keperawatan (AP)', 'reguler', 87.00, 82.00, 85.5000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(114, 'G1-2026-073', 1, 'Vina Oktaviana', '3171234560073', '2009-07-13', 16, 'P', 'SMPN 23 Jakarta Barat', '081234560073', 'Jl. Zinnia No.73, Jakarta Barat', 'Asisten Keperawatan (AP)', 'reguler', 84.00, 88.00, 85.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(115, 'G1-2026-074', 1, 'Wahid Nurhidayat', '3171234560074', '2009-12-21', 16, 'L', 'MTs Negeri 6 Jakarta', '081234560074', 'Jl. Freesia No.74, Jakarta Pusat', 'Asisten Keperawatan (AP)', 'reguler', 82.00, 91.00, 84.7000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(116, 'G1-2026-075', 1, 'Xima Arifiyah', '3171234560075', '2009-04-15', 17, 'P', 'SMPN 25 Jakarta Selatan', '081234560075', 'Jl. Begonia No.75, Jakarta Selatan', 'Asisten Keperawatan (AP)', 'reguler', 83.00, 88.00, 84.5000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(117, 'G1-2026-076', 1, 'Yoga Prasetyo', '3171234560076', '2009-10-29', 16, 'L', 'SMPN 27 Jakarta Timur', '081234560076', 'Jl. Azalea No.76, Jakarta Timur', 'Asisten Keperawatan (AP)', 'reguler', 82.50, 87.00, 84.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(118, 'G1-2026-077', 1, 'Yuni Astuti', '3171234560077', '2009-06-17', 16, 'P', 'SMPN 29 Depok', '081234560077', 'Jl. Wisteria No.77, Depok', 'Asisten Keperawatan (AP)', 'reguler', 81.50, 88.00, 83.6500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(119, 'G1-2026-078', 1, 'Zara Aulia', '3171234560078', '2009-01-11', 17, 'P', 'SMPN 31 Bogor', '081234560078', 'Jl. Hibiscus No.78, Bogor', 'Asisten Keperawatan (AP)', 'reguler', 80.00, 89.00, 83.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(120, 'G1-2026-079', 1, 'Abdul Ghani', '3171234560079', '2009-08-22', 16, 'L', 'SMPN 33 Bekasi', '081234560079', 'Jl. Heliconia No.79, Bekasi', 'Asisten Keperawatan (AP)', 'reguler', 82.00, 85.00, 83.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(121, 'G1-2026-080', 1, 'Baiq Surya', '3171234560080', '2009-03-18', 17, 'P', 'SMPN 35 Tangerang Selatan', '081234560080', 'Jl. Calimyrna No.80, Tangerang Selatan', 'Asisten Keperawatan (AP)', 'reguler', 80.00, 87.00, 82.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(122, 'G1-2026-081', 1, 'Chairul Anwar', '3171234560081', '2009-11-02', 16, 'L', 'MTs Raudhatul Ulum', '081234560081', 'Jl. Cyclamen No.81, Jakarta Utara', 'Asisten Keperawatan (AP)', 'reguler', 79.00, 87.00, 81.7000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(123, 'G1-2026-082', 1, 'Devi Anggriani', '3171234560082', '2009-06-26', 16, 'P', 'SMPN 37 Jakarta Barat', '081234560082', 'Jl. Larkspur No.82, Jakarta Barat', 'Asisten Keperawatan (AP)', 'reguler', 81.50, 83.00, 82.0500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(124, 'G1-2026-083', 1, 'Edi Santoso', '3171234560083', '2009-09-09', 16, 'L', 'SMPN 39 Jakarta Selatan', '081234560083', 'Jl. Lobelia No.83, Jakarta Selatan', 'Asisten Keperawatan (AP)', 'reguler', 80.50, 83.00, 81.2500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(125, 'G1-2026-084', 1, 'Fitri Handayani', '3171234560084', '2009-01-04', 17, 'P', 'SMP Insan Cendekia', '081234560084', 'Jl. Marigold No.84, Depok', 'Asisten Keperawatan (AP)', 'reguler', 79.00, 85.00, 81.2000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(126, 'G1-2026-085', 1, 'Gilang Nugraha', '3171234560085', '2009-07-20', 16, 'L', 'SMPN 41 Bogor', '081234560085', 'Jl. Pansy No.85, Bogor', 'Asisten Keperawatan (AP)', 'reguler', 75.00, 78.00, 76.0000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(127, 'G1-2026-086', 1, 'Hamidah Nurjannah', '3171234560086', '2009-04-09', 17, 'P', 'SMPN 1 Depok', '081234560086', 'Jl. Orchid No.86, Depok', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 93.00, 91.00, 92.4000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(128, 'G1-2026-087', 1, 'Ika Wahyuningsih', '3171234560087', '2009-08-24', 16, 'P', 'SMPN 3 Tangerang', '081234560087', 'Jl. Petunia No.87, Tangerang', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 91.00, 92.50, 91.4500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(129, 'G1-2026-088', 1, 'Jasmine Putri', '3171234560088', '2009-02-10', 17, 'P', 'SMPN 5 Bekasi', '081234560088', 'Jl. Poppy No.88, Bekasi', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 90.50, 91.00, 90.6500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(130, 'G1-2026-089', 1, 'Karina Dewi', '3171234560089', '2009-06-03', 17, 'P', 'SMPN 7 Bogor', '081234560089', 'Jl. Primrose No.89, Bogor', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 89.00, 93.00, 90.5000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(131, 'G1-2026-090', 1, 'Latifah Hanum', '3171234560090', '2009-10-17', 16, 'P', 'MTs Negeri 7 Jakarta', '081234560090', 'Jl. Sunflower No.90, Jakarta Pusat', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 88.00, 92.00, 89.6000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(132, 'G1-2026-091', 1, 'Mira Kusumawati', '3171234560091', '2009-03-25', 17, 'P', 'SMPN 9 Jakarta Barat', '081234560091', 'Jl. Tulip No.91, Jakarta Barat', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 90.00, 88.00, 89.4000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(133, 'G1-2026-092', 1, 'Nining Susilawati', '3171234560092', '2009-07-31', 16, 'P', 'SMPN 11 Jakarta Timur', '081234560092', 'Jl. Violet No.92, Jakarta Timur', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 87.50, 91.00, 88.5500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(134, 'G1-2026-093', 1, 'Olivia Rahmawati', '3171234560093', '2009-01-13', 17, 'P', 'SMPN 13 Jakarta Selatan', '081234560093', 'Jl. Yarrow No.93, Jakarta Selatan', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 86.00, 92.00, 88.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(135, 'G1-2026-094', 1, 'Pita Susilowati', '3171234560094', '2009-05-21', 17, 'P', 'SMP Al-Hikmah', '081234560094', 'Jl. Zinnia No.94, Jakarta Utara', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 87.50, 89.00, 88.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(136, 'G1-2026-095', 1, 'Qorina Sabrina', '3171234560095', '2009-09-06', 16, 'P', 'SMPN 15 Depok', '081234560095', 'Jl. Acacia No.95, Depok', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 86.00, 90.50, 87.5500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(137, 'G1-2026-096', 1, 'Rini Agustina', '3171234560096', '2009-04-01', 17, 'P', 'SMPN 17 Bekasi', '081234560096', 'Jl. Birch No.96, Bekasi', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 88.00, 86.00, 87.4000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(138, 'G1-2026-097', 1, 'Sinta Dewi', '3171234560097', '2009-12-28', 16, 'P', 'SMPN 19 Bogor', '081234560097', 'Jl. Cedar No.97, Bogor', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 85.00, 90.00, 87.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(139, 'G1-2026-098', 1, 'Tiara Anggraini', '3171234560098', '2009-08-11', 16, 'P', 'MTs Raudlatul Jannah', '081234560098', 'Jl. Cypress No.98, Tangerang', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 86.50, 87.00, 86.6500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(140, 'G1-2026-099', 1, 'Uning Hartati', '3171234560099', '2009-02-26', 17, 'P', 'SMPN 21 Tangerang Selatan', '081234560099', 'Jl. Elm No.99, Tangerang Selatan', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 84.50, 89.00, 86.0500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(141, 'G1-2026-100', 1, 'Vina Marlina', '3171234560100', '2009-06-10', 17, 'P', 'SMPN 23 Jakarta Barat', '081234560100', 'Jl. Fir No.100, Jakarta Barat', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 83.00, 90.00, 85.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(142, 'G1-2026-101', 1, 'Westi Anggraini', '3171234560101', '2009-03-06', 17, 'P', 'SMPN 25 Jakarta Utara', '081234560101', 'Jl. Ginkgo No.101, Jakarta Utara', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 84.00, 87.00, 85.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(143, 'G1-2026-102', 1, 'Xena Savitri', '3171234560102', '2009-10-22', 16, 'P', 'SMPN 27 Jakarta Selatan', '081234560102', 'Jl. Hawthorn No.102, Jakarta Selatan', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 82.50, 89.00, 84.4500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(144, 'G1-2026-103', 1, 'Yola Fitriani', '3171234560103', '2009-07-04', 16, 'P', 'SMPN 29 Jakarta Timur', '081234560103', 'Jl. Ivy No.103, Jakarta Timur', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 83.00, 87.00, 84.2000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(145, 'G1-2026-104', 1, 'Zahrah Maulida', '3171234560104', '2009-01-26', 17, 'P', 'SMP Bina Bangsa', '081234560104', 'Jl. Juniper No.104, Depok', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 82.00, 88.00, 83.8000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(146, 'G1-2026-105', 1, 'Ayu Pratiwi', '3171234560105', '2009-05-12', 17, 'P', 'SMPN 31 Bekasi', '081234560105', 'Jl. Larch No.105, Bekasi', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 81.00, 88.00, 83.1000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(147, 'G1-2026-106', 1, 'Bunga Sekarwangi', '3171234560106', '2009-09-18', 16, 'P', 'SMPN 33 Bogor', '081234560106', 'Jl. Maple No.106, Bogor', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 80.00, 88.00, 82.4000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(148, 'G1-2026-107', 1, 'Cinta Amalia', '3171234560107', '2009-12-05', 16, 'P', 'MTs Babus Salam', '081234560107', 'Jl. Oak No.107, Tangerang', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 81.50, 84.00, 82.2500, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(149, 'G1-2026-108', 1, 'Dina Ambarwati', '3171234560108', '2009-04-08', 17, 'P', 'SMPN 35 Tangerang Selatan', '081234560108', 'Jl. Pine No.108, Tangerang Selatan', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 80.00, 86.00, 82.0000, 1, 0, 'terima', NULL, '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(150, 'G1-2026-109', 1, 'Erin Wahyuni', '3171234560109', '2009-08-19', 16, 'P', 'SMPN 37 Jakarta Barat', '081234560109', 'Jl. Spruce No.109, Jakarta Barat', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 79.50, 85.00, 81.1500, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47'),
(151, 'G1-2026-110', 1, 'Fitria Nurhayati', '3171234560110', '2009-11-07', 16, 'P', 'SMPN 39 Jakarta Utara', '081234560110', 'Jl. Willow No.110, Jakarta Utara', 'Tata Kecantikan Kulit dan Rambut (TKKR)', 'reguler', 74.00, 77.00, 75.1000, 1, 0, 'gugur', 'Nilai tidak mencapai kuota', '2026-05-23 04:18:46', '2026-05-23 04:23:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pendaftar_raport`
--

CREATE TABLE `pendaftar_raport` (
  `id` int(11) NOT NULL,
  `pendaftar_id` int(11) NOT NULL,
  `mata_pelajaran` varchar(100) NOT NULL,
  `semester` tinyint(4) NOT NULL,
  `nilai` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pendaftar_raport`
--

INSERT INTO `pendaftar_raport` (`id`, `pendaftar_id`, `mata_pelajaran`, `semester`, `nilai`) VALUES
(1, 1, 'Pendidikan Agama dan Budi Pekerti', 1, 78.00),
(2, 1, 'Pendidikan Agama dan Budi Pekerti', 2, 80.00),
(3, 1, 'Pendidikan Agama dan Budi Pekerti', 3, 85.00),
(4, 1, 'Pendidikan Agama dan Budi Pekerti', 4, 79.00),
(5, 1, 'Pendidikan Agama dan Budi Pekerti', 5, 82.00),
(258, 1, 'Pendidikan Agama dan Budi Pekerti', 6, 89.00),
(259, 1, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 1, 84.00),
(260, 1, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 2, 85.00),
(261, 1, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 3, 86.00),
(262, 1, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 4, 87.00),
(263, 1, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 5, 88.00),
(264, 1, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 6, 89.00),
(265, 1, 'Bahasa Indonesia', 1, 87.00),
(266, 1, 'Bahasa Indonesia', 2, 88.00),
(267, 1, 'Bahasa Indonesia', 3, 88.00),
(268, 1, 'Bahasa Indonesia', 4, 89.00),
(269, 1, 'Bahasa Indonesia', 5, 90.00),
(270, 1, 'Bahasa Indonesia', 6, 91.00),
(271, 1, 'Matematika', 1, 83.00),
(272, 1, 'Matematika', 2, 84.00),
(273, 1, 'Matematika', 3, 85.00),
(274, 1, 'Matematika', 4, 86.00),
(275, 1, 'Matematika', 5, 87.00),
(276, 1, 'Matematika', 6, 88.00),
(277, 1, 'Ilmu Pengetahuan Alam (IPA)', 1, 86.00),
(278, 1, 'Ilmu Pengetahuan Alam (IPA)', 2, 87.00),
(279, 1, 'Ilmu Pengetahuan Alam (IPA)', 3, 88.00),
(280, 1, 'Ilmu Pengetahuan Alam (IPA)', 4, 89.00),
(281, 1, 'Ilmu Pengetahuan Alam (IPA)', 5, 90.00),
(282, 1, 'Ilmu Pengetahuan Alam (IPA)', 6, 90.00),
(283, 1, 'Ilmu Pengetahuan Sosial (IPS)', 1, 85.00),
(284, 1, 'Ilmu Pengetahuan Sosial (IPS)', 2, 86.00),
(285, 1, 'Ilmu Pengetahuan Sosial (IPS)', 3, 87.00),
(286, 1, 'Ilmu Pengetahuan Sosial (IPS)', 4, 88.00),
(287, 1, 'Ilmu Pengetahuan Sosial (IPS)', 5, 88.00),
(288, 1, 'Ilmu Pengetahuan Sosial (IPS)', 6, 89.00),
(289, 1, 'Bahasa Inggris', 1, 88.00),
(290, 1, 'Bahasa Inggris', 2, 89.00),
(291, 1, 'Bahasa Inggris', 3, 89.00),
(292, 1, 'Bahasa Inggris', 4, 90.00),
(293, 1, 'Bahasa Inggris', 5, 91.00),
(294, 1, 'Bahasa Inggris', 6, 92.00),
(295, 2, 'Pendidikan Agama dan Budi Pekerti', 1, 90.00),
(296, 2, 'Pendidikan Agama dan Budi Pekerti', 2, 91.00),
(297, 2, 'Pendidikan Agama dan Budi Pekerti', 3, 91.00),
(298, 2, 'Pendidikan Agama dan Budi Pekerti', 4, 92.00),
(299, 2, 'Pendidikan Agama dan Budi Pekerti', 5, 92.00),
(300, 2, 'Pendidikan Agama dan Budi Pekerti', 6, 93.00),
(301, 2, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 1, 89.00),
(302, 2, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 2, 90.00),
(303, 2, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 3, 91.00),
(304, 2, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 4, 91.00),
(305, 2, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 5, 92.00),
(306, 2, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 6, 93.00),
(307, 2, 'Bahasa Indonesia', 1, 91.00),
(308, 2, 'Bahasa Indonesia', 2, 92.00),
(309, 2, 'Bahasa Indonesia', 3, 92.00),
(310, 2, 'Bahasa Indonesia', 4, 93.00),
(311, 2, 'Bahasa Indonesia', 5, 93.00),
(312, 2, 'Bahasa Indonesia', 6, 94.00),
(313, 2, 'Matematika', 1, 88.00),
(314, 2, 'Matematika', 2, 89.00),
(315, 2, 'Matematika', 3, 90.00),
(316, 2, 'Matematika', 4, 91.00),
(317, 2, 'Matematika', 5, 92.00),
(318, 2, 'Matematika', 6, 93.00),
(319, 2, 'Ilmu Pengetahuan Alam (IPA)', 1, 90.00),
(320, 2, 'Ilmu Pengetahuan Alam (IPA)', 2, 91.00),
(321, 2, 'Ilmu Pengetahuan Alam (IPA)', 3, 92.00),
(322, 2, 'Ilmu Pengetahuan Alam (IPA)', 4, 92.00),
(323, 2, 'Ilmu Pengetahuan Alam (IPA)', 5, 93.00),
(324, 2, 'Ilmu Pengetahuan Alam (IPA)', 6, 94.00),
(325, 2, 'Ilmu Pengetahuan Sosial (IPS)', 1, 89.00),
(326, 2, 'Ilmu Pengetahuan Sosial (IPS)', 2, 90.00),
(327, 2, 'Ilmu Pengetahuan Sosial (IPS)', 3, 91.00),
(328, 2, 'Ilmu Pengetahuan Sosial (IPS)', 4, 92.00),
(329, 2, 'Ilmu Pengetahuan Sosial (IPS)', 5, 92.00),
(330, 2, 'Ilmu Pengetahuan Sosial (IPS)', 6, 93.00),
(331, 2, 'Bahasa Inggris', 1, 92.00),
(332, 2, 'Bahasa Inggris', 2, 93.00),
(333, 2, 'Bahasa Inggris', 3, 93.00),
(334, 2, 'Bahasa Inggris', 4, 94.00),
(335, 2, 'Bahasa Inggris', 5, 94.00),
(336, 2, 'Bahasa Inggris', 6, 95.00),
(337, 3, 'Pendidikan Agama dan Budi Pekerti', 1, 78.00),
(338, 3, 'Pendidikan Agama dan Budi Pekerti', 2, 78.00),
(339, 3, 'Pendidikan Agama dan Budi Pekerti', 3, 79.00),
(340, 3, 'Pendidikan Agama dan Budi Pekerti', 4, 79.00),
(341, 3, 'Pendidikan Agama dan Budi Pekerti', 5, 80.00),
(342, 3, 'Pendidikan Agama dan Budi Pekerti', 6, 80.00),
(343, 3, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 1, 76.00),
(344, 3, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 2, 77.00),
(345, 3, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 3, 77.00),
(346, 3, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 4, 78.00),
(347, 3, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 5, 78.00),
(348, 3, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 6, 79.00),
(349, 3, 'Bahasa Indonesia', 1, 79.00),
(350, 3, 'Bahasa Indonesia', 2, 79.00),
(351, 3, 'Bahasa Indonesia', 3, 80.00),
(352, 3, 'Bahasa Indonesia', 4, 80.00),
(353, 3, 'Bahasa Indonesia', 5, 81.00),
(354, 3, 'Bahasa Indonesia', 6, 81.00),
(355, 3, 'Matematika', 1, 75.00),
(356, 3, 'Matematika', 2, 76.00),
(357, 3, 'Matematika', 3, 77.00),
(358, 3, 'Matematika', 4, 77.00),
(359, 3, 'Matematika', 5, 78.00),
(360, 3, 'Matematika', 6, 78.00),
(361, 3, 'Ilmu Pengetahuan Alam (IPA)', 1, 78.00),
(362, 3, 'Ilmu Pengetahuan Alam (IPA)', 2, 79.00),
(363, 3, 'Ilmu Pengetahuan Alam (IPA)', 3, 79.00),
(364, 3, 'Ilmu Pengetahuan Alam (IPA)', 4, 80.00),
(365, 3, 'Ilmu Pengetahuan Alam (IPA)', 5, 80.00),
(366, 3, 'Ilmu Pengetahuan Alam (IPA)', 6, 81.00),
(367, 3, 'Ilmu Pengetahuan Sosial (IPS)', 1, 77.00),
(368, 3, 'Ilmu Pengetahuan Sosial (IPS)', 2, 78.00),
(369, 3, 'Ilmu Pengetahuan Sosial (IPS)', 3, 78.00),
(370, 3, 'Ilmu Pengetahuan Sosial (IPS)', 4, 79.00),
(371, 3, 'Ilmu Pengetahuan Sosial (IPS)', 5, 79.00),
(372, 3, 'Ilmu Pengetahuan Sosial (IPS)', 6, 80.00),
(373, 3, 'Bahasa Inggris', 1, 76.00),
(374, 3, 'Bahasa Inggris', 2, 77.00),
(375, 3, 'Bahasa Inggris', 3, 78.00),
(376, 3, 'Bahasa Inggris', 4, 78.00),
(377, 3, 'Bahasa Inggris', 5, 79.00),
(378, 3, 'Bahasa Inggris', 6, 80.00),
(379, 10, 'Pendidikan Agama dan Budi Pekerti', 1, 92.00),
(380, 10, 'Pendidikan Agama dan Budi Pekerti', 2, 93.00),
(381, 10, 'Pendidikan Agama dan Budi Pekerti', 3, 93.00),
(382, 10, 'Pendidikan Agama dan Budi Pekerti', 4, 94.00),
(383, 10, 'Pendidikan Agama dan Budi Pekerti', 5, 94.00),
(384, 10, 'Pendidikan Agama dan Budi Pekerti', 6, 95.00),
(385, 10, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 1, 91.00),
(386, 10, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 2, 92.00),
(387, 10, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 3, 93.00),
(388, 10, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 4, 93.00),
(389, 10, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 5, 94.00),
(390, 10, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 6, 95.00),
(391, 10, 'Bahasa Indonesia', 1, 93.00),
(392, 10, 'Bahasa Indonesia', 2, 94.00),
(393, 10, 'Bahasa Indonesia', 3, 94.00),
(394, 10, 'Bahasa Indonesia', 4, 95.00),
(395, 10, 'Bahasa Indonesia', 5, 95.00),
(396, 10, 'Bahasa Indonesia', 6, 96.00),
(397, 10, 'Matematika', 1, 91.00),
(398, 10, 'Matematika', 2, 92.00),
(399, 10, 'Matematika', 3, 93.00),
(400, 10, 'Matematika', 4, 93.00),
(401, 10, 'Matematika', 5, 94.00),
(402, 10, 'Matematika', 6, 95.00),
(403, 10, 'Ilmu Pengetahuan Alam (IPA)', 1, 93.00),
(404, 10, 'Ilmu Pengetahuan Alam (IPA)', 2, 94.00),
(405, 10, 'Ilmu Pengetahuan Alam (IPA)', 3, 94.00),
(406, 10, 'Ilmu Pengetahuan Alam (IPA)', 4, 95.00),
(407, 10, 'Ilmu Pengetahuan Alam (IPA)', 5, 95.00),
(408, 10, 'Ilmu Pengetahuan Alam (IPA)', 6, 96.00),
(409, 10, 'Ilmu Pengetahuan Sosial (IPS)', 1, 91.00),
(410, 10, 'Ilmu Pengetahuan Sosial (IPS)', 2, 92.00),
(411, 10, 'Ilmu Pengetahuan Sosial (IPS)', 3, 93.00),
(412, 10, 'Ilmu Pengetahuan Sosial (IPS)', 4, 93.00),
(413, 10, 'Ilmu Pengetahuan Sosial (IPS)', 5, 94.00),
(414, 10, 'Ilmu Pengetahuan Sosial (IPS)', 6, 94.00),
(415, 10, 'Bahasa Inggris', 1, 93.00),
(416, 10, 'Bahasa Inggris', 2, 94.00),
(417, 10, 'Bahasa Inggris', 3, 94.00),
(418, 10, 'Bahasa Inggris', 4, 95.00),
(419, 10, 'Bahasa Inggris', 5, 96.00),
(420, 10, 'Bahasa Inggris', 6, 96.00),
(421, 20, 'Pendidikan Agama dan Budi Pekerti', 1, 93.00),
(422, 20, 'Pendidikan Agama dan Budi Pekerti', 2, 94.00),
(423, 20, 'Pendidikan Agama dan Budi Pekerti', 3, 94.00),
(424, 20, 'Pendidikan Agama dan Budi Pekerti', 4, 95.00),
(425, 20, 'Pendidikan Agama dan Budi Pekerti', 5, 95.00),
(426, 20, 'Pendidikan Agama dan Budi Pekerti', 6, 96.00),
(427, 20, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 1, 92.00),
(428, 20, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 2, 93.00),
(429, 20, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 3, 93.00),
(430, 20, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 4, 94.00),
(431, 20, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 5, 94.00),
(432, 20, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 6, 95.00),
(433, 20, 'Bahasa Indonesia', 1, 94.00),
(434, 20, 'Bahasa Indonesia', 2, 94.00),
(435, 20, 'Bahasa Indonesia', 3, 95.00),
(436, 20, 'Bahasa Indonesia', 4, 95.00),
(437, 20, 'Bahasa Indonesia', 5, 96.00),
(438, 20, 'Bahasa Indonesia', 6, 97.00),
(439, 20, 'Matematika', 1, 93.00),
(440, 20, 'Matematika', 2, 93.00),
(441, 20, 'Matematika', 3, 94.00),
(442, 20, 'Matematika', 4, 95.00),
(443, 20, 'Matematika', 5, 95.00),
(444, 20, 'Matematika', 6, 96.00),
(445, 20, 'Ilmu Pengetahuan Alam (IPA)', 1, 94.00),
(446, 20, 'Ilmu Pengetahuan Alam (IPA)', 2, 94.00),
(447, 20, 'Ilmu Pengetahuan Alam (IPA)', 3, 95.00),
(448, 20, 'Ilmu Pengetahuan Alam (IPA)', 4, 95.00),
(449, 20, 'Ilmu Pengetahuan Alam (IPA)', 5, 96.00),
(450, 20, 'Ilmu Pengetahuan Alam (IPA)', 6, 96.00),
(451, 20, 'Ilmu Pengetahuan Sosial (IPS)', 1, 92.00),
(452, 20, 'Ilmu Pengetahuan Sosial (IPS)', 2, 93.00),
(453, 20, 'Ilmu Pengetahuan Sosial (IPS)', 3, 93.00),
(454, 20, 'Ilmu Pengetahuan Sosial (IPS)', 4, 94.00),
(455, 20, 'Ilmu Pengetahuan Sosial (IPS)', 5, 94.00),
(456, 20, 'Ilmu Pengetahuan Sosial (IPS)', 6, 95.00),
(457, 20, 'Bahasa Inggris', 1, 94.00),
(458, 20, 'Bahasa Inggris', 2, 95.00),
(459, 20, 'Bahasa Inggris', 3, 95.00),
(460, 20, 'Bahasa Inggris', 4, 96.00),
(461, 20, 'Bahasa Inggris', 5, 96.00),
(462, 20, 'Bahasa Inggris', 6, 97.00),
(463, 23, 'Pendidikan Agama dan Budi Pekerti', 1, 91.00),
(464, 23, 'Pendidikan Agama dan Budi Pekerti', 2, 92.00),
(465, 23, 'Pendidikan Agama dan Budi Pekerti', 3, 92.00),
(466, 23, 'Pendidikan Agama dan Budi Pekerti', 4, 93.00),
(467, 23, 'Pendidikan Agama dan Budi Pekerti', 5, 93.00),
(468, 23, 'Pendidikan Agama dan Budi Pekerti', 6, 94.00),
(469, 23, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 1, 90.00),
(470, 23, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 2, 91.00),
(471, 23, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 3, 91.00),
(472, 23, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 4, 92.00),
(473, 23, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 5, 92.00),
(474, 23, 'Pendidikan Pancasila dan Kewarganegaraan (PPKn)', 6, 93.00),
(475, 23, 'Bahasa Indonesia', 1, 91.00),
(476, 23, 'Bahasa Indonesia', 2, 92.00),
(477, 23, 'Bahasa Indonesia', 3, 93.00),
(478, 23, 'Bahasa Indonesia', 4, 93.00),
(479, 23, 'Bahasa Indonesia', 5, 94.00),
(480, 23, 'Bahasa Indonesia', 6, 94.00),
(481, 23, 'Matematika', 1, 90.00),
(482, 23, 'Matematika', 2, 91.00),
(483, 23, 'Matematika', 3, 91.00),
(484, 23, 'Matematika', 4, 92.00),
(485, 23, 'Matematika', 5, 92.00),
(486, 23, 'Matematika', 6, 93.00),
(487, 23, 'Ilmu Pengetahuan Alam (IPA)', 1, 91.00),
(488, 23, 'Ilmu Pengetahuan Alam (IPA)', 2, 92.00),
(489, 23, 'Ilmu Pengetahuan Alam (IPA)', 3, 92.00),
(490, 23, 'Ilmu Pengetahuan Alam (IPA)', 4, 93.00),
(491, 23, 'Ilmu Pengetahuan Alam (IPA)', 5, 93.00),
(492, 23, 'Ilmu Pengetahuan Alam (IPA)', 6, 94.00),
(493, 23, 'Ilmu Pengetahuan Sosial (IPS)', 1, 90.00),
(494, 23, 'Ilmu Pengetahuan Sosial (IPS)', 2, 91.00),
(495, 23, 'Ilmu Pengetahuan Sosial (IPS)', 3, 92.00),
(496, 23, 'Ilmu Pengetahuan Sosial (IPS)', 4, 92.00),
(497, 23, 'Ilmu Pengetahuan Sosial (IPS)', 5, 93.00),
(498, 23, 'Ilmu Pengetahuan Sosial (IPS)', 6, 93.00),
(499, 23, 'Bahasa Inggris', 1, 92.00),
(500, 23, 'Bahasa Inggris', 2, 92.00),
(501, 23, 'Bahasa Inggris', 3, 93.00),
(502, 23, 'Bahasa Inggris', 4, 93.00),
(503, 23, 'Bahasa Inggris', 5, 94.00),
(504, 23, 'Bahasa Inggris', 6, 95.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tahapan`
--

CREATE TABLE `tahapan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kode` varchar(50) NOT NULL,
  `urutan` tinyint(4) NOT NULL DEFAULT 1,
  `icon` varchar(50) NOT NULL DEFAULT 'bi-circle',
  `deskripsi` text DEFAULT NULL,
  `halaman_key` varchar(50) NOT NULL DEFAULT 'pendaftar',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `tahapan`
--

INSERT INTO `tahapan` (`id`, `nama`, `kode`, `urutan`, `icon`, `deskripsi`, `halaman_key`, `is_active`, `created_at`) VALUES
(1, 'Pengimput Data', 'input_data', 2, 'bi-keyboard', 'Bertugas mengisi data pendaftar ke sistem', 'pendaftar', 1, '2026-05-21 00:39:10'),
(2, 'Proses Berkas', 'proses_berkas', 1, 'bi-folder-check', 'Memverifikasi dan memproses kelengkapan berkas', 'pendaftar', 1, '2026-05-21 00:39:10'),
(3, 'Ranking & Seleksi', 'ranking', 3, 'bi-trophy', 'Mengelola ranking dan proses penerimaan', 'ranking', 1, '2026-05-21 00:39:10'),
(4, 'Pengumuman', 'pengumuman', 4, 'bi-megaphone', 'Mengelola pengumuman publik PPDB', 'announcements', 1, '2026-05-21 00:39:10'),
(18, 'Kelola Meja Antrian', 'kelola_meja', 5, 'bi-grid-3x2-gap-fill', 'Mengelola konfigurasi meja antrian dan fase pendaftaran', 'meja', 1, '2026-05-24 02:11:27'),
(19, 'Kelola Gelombang', 'kelola_gelombang', 6, 'bi-calendar-week', 'Mengelola jadwal dan konfigurasi gelombang PPDB', 'gelombang', 1, '2026-05-24 02:11:27');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indeks untuk tabel `admin_tahapan`
--
ALTER TABLE `admin_tahapan`
  ADD PRIMARY KEY (`admin_id`,`tahap_id`),
  ADD KEY `fk_at_tahap` (`tahap_id`);

--
-- Indeks untuk tabel `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_tanggal_nomor_fase` (`tanggal`,`nomor`,`fase`),
  ADD KEY `meja_id` (`meja_id`);

--
-- Indeks untuk tabel `gelombang`
--
ALTER TABLE `gelombang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gelombang` (`gelombang`);

--
-- Indeks untuk tabel `meja`
--
ALTER TABLE `meja`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_meja` (`nomor_meja`);

--
-- Indeks untuk tabel `pendaftar`
--
ALTER TABLE `pendaftar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_pendaftaran` (`no_pendaftaran`),
  ADD KEY `jurusan` (`jurusan`),
  ADD KEY `gelombang` (`gelombang`),
  ADD KEY `status` (`status`);

--
-- Indeks untuk tabel `pendaftar_raport`
--
ALTER TABLE `pendaftar_raport`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pendaftar_mapel_smt` (`pendaftar_id`,`mata_pelajaran`,`semester`);

--
-- Indeks untuk tabel `tahapan`
--
ALTER TABLE `tahapan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT untuk tabel `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `antrian`
--
ALTER TABLE `antrian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=823;

--
-- AUTO_INCREMENT untuk tabel `gelombang`
--
ALTER TABLE `gelombang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `meja`
--
ALTER TABLE `meja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `pendaftar`
--
ALTER TABLE `pendaftar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT untuk tabel `pendaftar_raport`
--
ALTER TABLE `pendaftar_raport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=505;

--
-- AUTO_INCREMENT untuk tabel `tahapan`
--
ALTER TABLE `tahapan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `admin_tahapan`
--
ALTER TABLE `admin_tahapan`
  ADD CONSTRAINT `fk_at_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_at_tahap` FOREIGN KEY (`tahap_id`) REFERENCES `tahapan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `antrian`
--
ALTER TABLE `antrian`
  ADD CONSTRAINT `fk_antrian_meja` FOREIGN KEY (`meja_id`) REFERENCES `meja` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pendaftar_raport`
--
ALTER TABLE `pendaftar_raport`
  ADD CONSTRAINT `fk_raport_pendaftar` FOREIGN KEY (`pendaftar_id`) REFERENCES `pendaftar` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
