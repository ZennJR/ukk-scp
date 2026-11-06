-- Jalankan sekali pada database yang sudah ada (alternatif dari `php artisan migrate`)
ALTER TABLE `leave_requests`
  ADD COLUMN `reject_reason` TEXT NULL DEFAULT NULL AFTER `status_approval`;
