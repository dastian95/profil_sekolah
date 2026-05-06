-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 06 Bulan Mei 2026 pada 00.21
-- Versi server: 8.0.30
-- Versi PHP: 8.5.5

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
-- Struktur dari tabel `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `details`, `created_at`) VALUES
(1, 3, 'VERIFY_DOC', 'Admin memverifikasi dokumen \'Akta Kelahiran\' milik siswa \'Aufa Dzaky Zuhdi Wicaksono\' (ID: 6). Status: diverifikasi.', '2026-02-08 11:23:10'),
(2, 3, 'VERIFY_DOC', 'Admin memverifikasi dokumen \'Kartu Keluarga\' milik siswa \'Aufa Dzaky Zuhdi Wicaksono\' (ID: 6). Status: diverifikasi.', '2026-02-08 11:23:12'),
(3, 3, 'VERIFY_DOC', 'Admin memverifikasi dokumen \'Ijazah/SKL\' milik siswa \'Aufa Dzaky Zuhdi Wicaksono\' (ID: 6). Status: diverifikasi.', '2026-02-08 11:23:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `announcements`
--

CREATE TABLE `announcements` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','danger','success') DEFAULT 'info',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `daftar_ulang`
--

CREATE TABLE `daftar_ulang` (
  `nisn` varchar(100) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `jurusan` enum('Rekayasa Perangkat Lunak (RPL)','Teknik Komputer dan Jaringan (TKJ)','Asisten Keperawatan (AP)','Tata Kecantikan Kulit dan Rambut (TKKR)') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `hasil` enum('diterima','tidak diterima') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `jalur_daftar` enum('Jalur Prestasi','Jalur Zonasi','Jalur Afirmasi','Jalur Perpindahan Orang Tua') NOT NULL,
  `id_pendaftar` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `data_peserta`
--

CREATE TABLE `data_peserta` (
  `id_pendaftar` int NOT NULL,
  `nisn` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `asal_sekolah` varchar(255) NOT NULL,
  `npsn` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `no_telp_ortu` varchar(100) NOT NULL,
  `no_telp_siswa` varchar(100) NOT NULL,
  `jurusan` enum('Rekayasa Perangkat Lunak (RPL)','Teknik Komputer dan Jaringan (TKJ)','Asisten Keperawatan (AP)','Tata Kecantikan Kulit dan Rambut (TKKR)') NOT NULL,
  `Kecamatan` text NOT NULL,
  `kota` varchar(50) NOT NULL,
  `provinsi` varchar(50) NOT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `jalur_daftar` enum('Jalur Prestasi','Jalur Zonasi','Jalur Afirmasi','Jalur Perpindahan Orang Tua') NOT NULL,
  `password` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `data_peserta`
--

INSERT INTO `data_peserta` (`id_pendaftar`, `nisn`, `nama`, `tanggal_lahir`, `asal_sekolah`, `npsn`, `email`, `no_telp_ortu`, `no_telp_siswa`, `jurusan`, `Kecamatan`, `kota`, `provinsi`, `alamat`, `foto`, `jalur_daftar`, `password`) VALUES
(6, '3082389086', 'Aufa Dzaky Zuhdi Wicaksono', '2008-02-01', 'SMPN 6 Kota Bekasi', NULL, 'aufadzwicaksono@gmail.com', '165658646616', '15665113211', 'Rekayasa Perangkat Lunak (RPL)', 'DUREN SAWIT', 'KOTA JAKARTA TIMUR', 'DKI JAKARTA', 'Jl. H. Abdul Halim No.79B, RT.001/RW.006, Jatiwaringin, Kec. Pd. Gede, Kota Bks, Jawa Barat 17411', 'foto_6_1769947997.jpg', 'Jalur Prestasi', '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `hasil_daftar`
--

CREATE TABLE `hasil_daftar` (
  `nisn` varchar(100) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `jurusan` enum('Rekayasa Perangkat Lunak (RPL)','Teknik Komputer dan Jaringan (TKJ)','Asisten Keperawatan (AP)','Tata Kecantikan Kulit dan Rambut (TKKR)') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `hasil` enum('diterima','tidak diterima') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `id_pendaftar` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_ujian`
--

CREATE TABLE `jadwal_ujian` (
  `id` int NOT NULL,
  `jurusan` varchar(100) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` varchar(50) NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `materi` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `jadwal_ujian`
--

INSERT INTO `jadwal_ujian` (`id`, `jurusan`, `tanggal`, `waktu`, `lokasi`, `materi`) VALUES
(1, 'Rekayasa Perangkat Lunak (RPL)', '2026-06-20', '08:00 - 10:00 WIB', 'Lab Komputer 1 (Gedung A)', 'Logika Algoritma, Matematika Dasar, Bahasa Inggris'),
(2, 'Teknik Komputer dan Jaringan (TKJ)', '2026-06-21', '08:00 - 10:00 WIB', 'Lab Jaringan (Gedung B)', 'Dasar Komputer, Matematika Dasar, Bahasa Inggris'),
(3, 'Asisten Keperawatan (AP)', '2026-06-22', '08:00 - 10:00 WIB', 'Ruang Teori 1 (Gedung C)', 'Biologi Dasar, Matematika Dasar, Bahasa Inggris'),
(4, 'Tata Kecantikan Kulit dan Rambut (TKKR)', '2026-06-23', '08:00 - 10:00 WIB', 'Ruang Praktik Kecantikan (Gedung D)', 'Pengetahuan Umum, Matematika Dasar, Bahasa Inggris');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_dokumen`
--

CREATE TABLE `jenis_dokumen` (
  `id_jenis` int NOT NULL,
  `nama_dokumen` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `jenis_dokumen`
--

INSERT INTO `jenis_dokumen` (`id_jenis`, `nama_dokumen`) VALUES
(1, 'Kartu Keluarga'),
(2, 'Akta Kelahiran'),
(3, 'Ijazah/SKL'),
(4, 'Pas Foto'),
(5, 'Kartu Siswa');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 6, 'Dokumen Ijazah/SKL telah diverifikasi.', 0, '2026-02-01 13:09:59'),
(2, 6, 'Dokumen Ijazah/SKL ditolak. Mohon periksa kembali.', 0, '2026-02-01 13:10:01'),
(3, 6, 'Dokumen Akta Kelahiran diverifikasi.', 0, '2026-02-08 11:23:09'),
(4, 6, 'Dokumen Kartu Keluarga diverifikasi.', 0, '2026-02-08 11:23:12'),
(5, 6, 'Dokumen Ijazah/SKL diverifikasi.', 0, '2026-02-08 11:23:20');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pendaftar`
--

CREATE TABLE `pendaftar` (
  `id_pendaftar` int NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nisn` char(10) NOT NULL,
  `status_pendaftaran` enum('Draft','Terkirim') DEFAULT 'Draft',
  `tanggal_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `pendaftar`
--

INSERT INTO `pendaftar` (`id_pendaftar`, `nama_lengkap`, `nisn`, `status_pendaftaran`, `tanggal_daftar`) VALUES
(1, 'Budi Santoso', '0123456789', 'Draft', '2026-01-11 22:14:33'),
(6, 'Aufa Dzaky Zuhdi Wicaksono', '3082389086', 'Draft', '2026-02-01 12:28:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `unggah_dokumen`
--

CREATE TABLE `unggah_dokumen` (
  `id_unggah` int NOT NULL,
  `id_pendaftar` int DEFAULT NULL,
  `id_jenis` int DEFAULT NULL,
  `nama_file` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `catatan_admin` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `unggah_dokumen`
--

INSERT INTO `unggah_dokumen` (`id_unggah`, `id_pendaftar`, `id_jenis`, `nama_file`, `is_verified`, `catatan_admin`) VALUES
(1, 1, 1, 'kk_budi.pdf', 1, NULL),
(2, 1, 2, 'akta_budi.jpg', 0, NULL),
(4, 6, 3, '6_3_1769948897.jpg', 1, ''),
(5, 6, 2, '6_2_1769948911.jpg', 1, ''),
(6, 6, 1, '6_1_1769949433.jpg', 1, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_pendaftar` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `verification_token` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `is_banned` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_pendaftar`, `name`, `nisn`, `email`, `password`, `role`, `verification_token`, `is_verified`, `created_at`, `reset_token`, `reset_token_expires_at`, `is_banned`) VALUES
(3, 'admin', NULL, 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, 1, '2026-01-23 07:14:40', '8e73c7f159b89acd62cbf9fc40239793b352dfda2096c01890f73d3cbf6079ce', '2026-03-20 06:50:34', 0),
(6, 'Aufa Dzaky Zuhdi Wicaksono', '3082389086', 'aufadzwicaksono@gmail.com', '$2y$12$fmNRsZmCzBU.9xJQZw3P/e5Bq3OeOQ6J57Pv8oYxZfeLAIEaiwgX6', 'user', '87286bcb9d8e4b178167e773a9cf5496', 1, '2026-01-24 08:32:51', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `user_activity_logs`
--

INSERT INTO `user_activity_logs` (`id`, `user_id`, `action`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 6, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 09:45:18'),
(2, 6, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 09:45:20'),
(3, 6, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 10:10:45'),
(4, 6, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-09 10:11:05'),
(5, 6, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '2026-03-21 06:43:19'),
(6, 6, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.112.0 Chrome/142.0.7444.265 Electron/39.8.0 Safari/537.36', '2026-03-21 06:44:08'),
(7, 6, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 06:28:07'),
(8, 3, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', '2026-05-05 07:14:16');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `daftar_ulang`
--
ALTER TABLE `daftar_ulang`
  ADD PRIMARY KEY (`id_pendaftar`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `data_peserta`
--
ALTER TABLE `data_peserta`
  ADD PRIMARY KEY (`id_pendaftar`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `hasil_daftar`
--
ALTER TABLE `hasil_daftar`
  ADD PRIMARY KEY (`id_pendaftar`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `jadwal_ujian`
--
ALTER TABLE `jadwal_ujian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jurusan` (`jurusan`);

--
-- Indeks untuk tabel `jenis_dokumen`
--
ALTER TABLE `jenis_dokumen`
  ADD PRIMARY KEY (`id_jenis`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `pendaftar`
--
ALTER TABLE `pendaftar`
  ADD PRIMARY KEY (`id_pendaftar`),
  ADD UNIQUE KEY `nisn` (`nisn`);

--
-- Indeks untuk tabel `unggah_dokumen`
--
ALTER TABLE `unggah_dokumen`
  ADD PRIMARY KEY (`id_unggah`),
  ADD KEY `id_pendaftar` (`id_pendaftar`),
  ADD KEY `id_jenis` (`id_jenis`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_pendaftar`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nisn` (`nisn`);

--
-- Indeks untuk tabel `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `daftar_ulang`
--
ALTER TABLE `daftar_ulang`
  MODIFY `id_pendaftar` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `data_peserta`
--
ALTER TABLE `data_peserta`
  MODIFY `id_pendaftar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `hasil_daftar`
--
ALTER TABLE `hasil_daftar`
  MODIFY `id_pendaftar` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jadwal_ujian`
--
ALTER TABLE `jadwal_ujian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `jenis_dokumen`
--
ALTER TABLE `jenis_dokumen`
  MODIFY `id_jenis` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `pendaftar`
--
ALTER TABLE `pendaftar`
  MODIFY `id_pendaftar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `unggah_dokumen`
--
ALTER TABLE `unggah_dokumen`
  MODIFY `id_unggah` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_pendaftar` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_pendaftar`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `unggah_dokumen`
--
ALTER TABLE `unggah_dokumen`
  ADD CONSTRAINT `unggah_dokumen_ibfk_1` FOREIGN KEY (`id_pendaftar`) REFERENCES `pendaftar` (`id_pendaftar`) ON DELETE CASCADE,
  ADD CONSTRAINT `unggah_dokumen_ibfk_2` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_dokumen` (`id_jenis`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
