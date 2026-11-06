<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penyimpan OTP & token "Lupa Password". Tabel dibuat otomatis bila belum ada
 * (sama seperti pola Identitas::pastikanKolom), jadi aman walau migrasi belum dijalankan.
 */
class ResetPassword
{
    /** Masa berlaku token form password baru (menit) setelah OTP benar. */
    public const MASA_BERLAKU = 15;

    /** Masa berlaku kode OTP (menit). */
    public const OTP_MASA_BERLAKU = 5;

    /** Batas salah memasukkan OTP sebelum OTP dihanguskan. */
    public const OTP_MAKS_SALAH = 5;

    /** Jeda minimal antar pengiriman OTP (detik). */
    public const OTP_JEDA_KIRIM = 60;

    /** Berapa kali OTP boleh dikirim ulang dalam satu sesi. */
    public const OTP_MAKS_KIRIM_ULANG = 3;

    private static bool $siap = false;

    public static function pastikanTabel(): void
    {
        if (self::$siap) {
            return;
        }

        if (!Schema::hasTable('reset_password_tokens')) {
            Schema::create('reset_password_tokens', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('id_user')->index();
                $table->string('token_hash', 64)->unique(); // SHA-256 dari token; token asli tidak disimpan
                $table->timestamp('expires_at');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Tabel OTP hanya berisi data sementara. Jika sudah ada tetapi strukturnya versi lama
        // (tidak punya kolom baru), hapus lalu buat ulang.
        if (Schema::hasTable('reset_password_otps')
            && !Schema::hasColumns('reset_password_otps', ['id_user', 'otp_hash', 'attempts', 'resend_count', 'expires_at', 'last_sent_at'])) {
            Schema::drop('reset_password_otps');
        }

        if (!Schema::hasTable('reset_password_otps')) {
            Schema::create('reset_password_otps', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('id_user')->unique();
                $table->string('otp_hash', 64);               // HMAC dari OTP; OTP asli tidak disimpan
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
