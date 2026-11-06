<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek tiap 15 menit (jam sekolah) apakah ada guru yang belum mengisi jurnal
// untuk jadwal hari ini yang jam pelajarannya sudah lewat, lalu kirim notifikasi.
// Catatan: scheduler Laravel butuh cron job "* * * * * php artisan schedule:run"
// aktif di server agar baris ini benar-benar berjalan otomatis.
Schedule::command('jadwal:cek-jurnal')->everyFifteenMinutes()->between('06:00', '17:00');

// Catat pelanggaran guru (jurnal tidak diisi > 5 jam setelah jadwal selesai). Tanpa cron,
// pemindaian yang sama tetap berjalan otomatis setiap halaman jurnal/dashboard/rekap dimuat.
Schedule::command('pelanggaran:pindai')->everyTenMinutes();
