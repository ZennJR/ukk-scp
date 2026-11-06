<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor induk = NIP (guru) / NISN (siswa) yang tertaut ke sebuah akun.
 *
 * Username boleh diganti kapan saja, tetapi nomor induk harus tetap.
 * Karena itu nomor induk disimpan di kolom users.nomor_induk.
 * Selama kolom itu masih kosong (akun lama), nomor induk = username.
 */
class Identitas
{
    private static ?bool $kolomAda = null;

    public static function kolomTersedia(): bool
    {
        if (self::$kolomAda === null) {
            self::$kolomAda = Schema::hasColumn('users', 'nomor_induk');
        }
        return self::$kolomAda;
    }

    /**
     * Tambahkan kolom users.nomor_induk jika belum ada (aman dipanggil berulang).
     */
    public static function pastikanKolom(): void
    {
        if (self::kolomTersedia()) {
            return;
        }

        DB::statement('ALTER TABLE users ADD COLUMN nomor_induk VARCHAR(50) NULL DEFAULT NULL AFTER username');
        self::$kolomAda = true;
    }

    /**
     * Cari akun untuk login memakai NISN/NIP (users.nomor_induk).
     * Username TIDAK dipakai untuk login. Satu-satunya fallback: akun lama yang
     * kolom nomor_induk-nya masih kosong (di akun lama, username memang berisi NIP/NISN).
     */
    public static function cariUserLogin(string $nomor)
    {
        return DB::table('users')
            ->where(function ($q) use ($nomor) {
                $q->where('nomor_induk', $nomor)
                  ->orWhere(function ($q2) use ($nomor) {
                      $q2->whereNull('nomor_induk')->where('username', $nomor);
                  });
            })
            ->first();
    }

    /**
     * Nomor induk milik baris user (objek dari DB::table('users')).
     */
    public static function dariUser($user): ?string
    {
        $nomor = $user->nomor_induk ?? null;
        return ($nomor !== null && $nomor !== '') ? $nomor : ($user->username ?? null);
    }

    /**
     * Nomor induk user yang sedang login (dari session, fallback ke username).
     */
    public static function dariSesi(): ?string
    {
        return session('nomor_induk') ?: session('username');
    }
}
