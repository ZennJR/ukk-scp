<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penyimpan OTP untuk perubahan email di halaman Profil.
 * Email baru baru disimpan ke akun setelah OTP yang dikirim ke email baru itu dikonfirmasi.
 */
class GantiEmail
{
    public const OTP_MASA_BERLAKU    = 5;   // menit
    public const OTP_MAKS_SALAH      = 5;
    public const OTP_JEDA_KIRIM      = 60;  // detik
    public const OTP_MAKS_KIRIM_ULANG = 3;

    private static bool $siap = false;

    public static function pastikanTabel(): void
    {
        if (self::$siap) {
            return;
        }

        if (!Schema::hasTable('email_change_otps')) {
            Schema::create('email_change_otps', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('id_user')->unique();
                $table->string('email_baru', 100);
                $table->string('otp_hash', 64);
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedTinyInteger('resend_count')->default(0);
                $table->timestamp('expires_at');
                $table->timestamp('last_sent_at');
                $table->timestamp('created_at')->nullable();
            });
        }

        self::$siap = true;
    }
}
