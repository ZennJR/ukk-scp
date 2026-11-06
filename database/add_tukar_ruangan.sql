-- Fitur tukar ruangan antar guru (berlaku untuk SATU tanggal tertentu).
-- Jalankan sekali di phpMyAdmin (atau cukup `php artisan migrate`).
CREATE TABLE IF NOT EXISTS `tukar_ruangan` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `id_jadwal_pemohon` int UNSIGNED NOT NULL,
  `id_jadwal_tujuan` int UNSIGNED NOT NULL,
  `id_guru_pemohon` int UNSIGNED NOT NULL,
  `id_guru_tujuan` int UNSIGNED NOT NULL,
  `id_ruangan_pemohon` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_ruangan_tujuan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruangan_pemohon_nama` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruangan_tujuan_nama` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info_pemohon` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `info_tujuan` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jam_mulai` time NOT NULL,
  `alasan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu',
  `alasan_tolak` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tukar_ruangan_tanggal_index` (`tanggal`),
  KEY `tukar_ruangan_pemohon_index` (`id_guru_pemohon`),
  KEY `tukar_ruangan_tujuan_index` (`id_guru_tujuan`),
  KEY `tukar_ruangan_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
