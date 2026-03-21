-- ============================================================
-- Database Query Optimization - SQL Index Creation Script
-- SMK Laboratorium Jakarta Profil Sekolah
-- ============================================================
--
-- This script creates optimized indexes on frequently queried columns
-- to improve database performance significantly.
--
-- Run this once after initial database setup or whenever adding new features.
-- ============================================================

-- Users Table Indexes
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_email (email);

ALTER TABLE users ADD INDEX IF NOT EXISTS idx_nisn (nisn);

ALTER TABLE users ADD INDEX IF NOT EXISTS idx_role (role);

ALTER TABLE users
ADD INDEX IF NOT EXISTS idx_is_verified (is_verified);

ALTER TABLE users ADD INDEX IF NOT EXISTS idx_is_banned (is_banned);

ALTER TABLE users
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Data Peserta Table Indexes (Primary student data)
ALTER TABLE data_peserta
ADD INDEX IF NOT EXISTS idx_id_pendaftar (id_pendaftar);

ALTER TABLE data_peserta
ADD INDEX IF NOT EXISTS idx_jenis_jurusan (jenis_jurusan);

ALTER TABLE data_peserta ADD INDEX IF NOT EXISTS idx_kota (kota);

ALTER TABLE data_peserta
ADD INDEX IF NOT EXISTS idx_asal_sekolah (asal_sekolah);

ALTER TABLE data_peserta
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Composite index for filtering by jurusan and city
ALTER TABLE data_peserta
ADD INDEX IF NOT EXISTS idx_jurusan_kota (jenis_jurusan, kota);

-- Pendaftar Table Indexes (Application tracking)
ALTER TABLE pendaftar
ADD INDEX IF NOT EXISTS idx_id_pendaftar (id_pendaftar);

ALTER TABLE pendaftar
ADD INDEX IF NOT EXISTS idx_status_pendaftaran (status_pendaftaran);

ALTER TABLE pendaftar
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

ALTER TABLE pendaftar
ADD INDEX IF NOT EXISTS idx_updated_at (updated_at);

-- Hasil Daftar Table Indexes (Selection results)
ALTER TABLE hasil_daftar
ADD INDEX IF NOT EXISTS idx_id_pendaftar (id_pendaftar);

ALTER TABLE hasil_daftar
ADD INDEX IF NOT EXISTS idx_hasil_daftar (hasil_daftar);

ALTER TABLE hasil_daftar
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Unggah Dokumen Table Indexes (Document uploads)
ALTER TABLE unggah_dokumen
ADD INDEX IF NOT EXISTS idx_id_pendaftar (id_pendaftar);

ALTER TABLE unggah_dokumen
ADD INDEX IF NOT EXISTS idx_jenis_dokumen (jenis_dokumen);

ALTER TABLE unggah_dokumen
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Foreign Key Relationships (ensure referential integrity)
-- These help database optimizer understand relationships
ALTER TABLE data_peserta
ADD CONSTRAINT fk_data_peserta_users FOREIGN KEY (id_pendaftar) REFERENCES users (id_pendaftar) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE pendaftar
ADD CONSTRAINT fk_pendaftar_users FOREIGN KEY (id_pendaftar) REFERENCES users (id_pendaftar) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE hasil_daftar
ADD CONSTRAINT fk_hasil_daftar_users FOREIGN KEY (id_pendaftar) REFERENCES users (id_pendaftar) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE unggah_dokumen
ADD CONSTRAINT fk_unggah_dokumen_users FOREIGN KEY (id_pendaftar) REFERENCES users (id_pendaftar) ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================================
-- Index Size and Impact Analysis
-- ============================================================
--
-- After indexes are created, run these queries to analyze impact:
--
-- 1. Show all indexes and their sizes:
--    SELECT OBJECT_SCHEMA, OBJECT_NAME, INDEX_NAME, stat_user_seeks,
--           stat_user_scans, stat_user_lookups, stat_user_updates
--    FROM sys.dm_db_index_usage_stats;
--
-- 2. Show table and index sizes:
--    SELECT OBJECT_NAME(ips.object_id) AS TableName,
--           i.name AS IndexName,
--           ips.range_scan_count,
--           ips.singleton_lookup_count,
--           ips.leaf_insert_count
--    FROM sys.dm_db_index_usage_stats ips
--    INNER JOIN sys.indexes i ON ips.index_id = i.index_id;
--
-- ============================================================