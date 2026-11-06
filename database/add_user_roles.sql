-- Peran tambahan per akun (Guru yang juga Wali Kelas, dll).
-- Jalankan sekali di db_skansaba (atau cukup `php artisan migrate`).
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int UNSIGNED NOT NULL,
  `id_role` int UNSIGNED NOT NULL,
  `id_kelas` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_roles_id_user_id_role_unique` (`id_user`,`id_role`),
  KEY `user_roles_id_user_index` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
