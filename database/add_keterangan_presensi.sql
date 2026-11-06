-- Keterangan izin pada presensi siswa (mis. "Mengikuti kegiatan OSIS").
-- Jalankan sekali di phpMyAdmin (atau cukup `php artisan migrate`).
ALTER TABLE `attendances` ADD COLUMN `keterangan` VARCHAR(255) NULL;
