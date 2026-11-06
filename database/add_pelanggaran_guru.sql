-- Fitur: jam pengisian jurnal + rekap pelanggaran guru.
-- Jalankan sekali di db_skansaba lewat phpMyAdmin (atau cukup `php artisan migrate`).

-- 1) Jam guru mengisi jurnal (jalankan sekali; abaikan jika kolom sudah ada)
ALTER TABLE `journals` ADD COLUMN `filled_at` DATETIME NULL;

-- 2) Tabel pelanggaran guru
CREATE TABLE IF NOT EXISTS `pelanggaran_guru` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_guru` int UNSIGNED NOT NULL,
  `schedule_id` int UNSIGNED DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jenis` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mapel` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `batas_waktu` datetime NOT NULL,
  `jurnal_diisi_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pelanggaran_guru_jadwal_tanggal_unique` (`schedule_id`,`tanggal`),
  KEY `pelanggaran_guru_id_guru_index` (`id_guru`),
  KEY `pelanggaran_guru_tanggal_index` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
