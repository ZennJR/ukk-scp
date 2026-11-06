<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Peran (role) pengguna yang boleh LEBIH DARI SATU.
 *
 * Kasus utama: seorang Guru diangkat menjadi Wali Kelas. Ia tetap harus bisa
 * mengajar (jurnal & jadwal) sekaligus memverifikasi izin siswa kelasnya.
 *
 *  - users.id_role        = peran UTAMA (menentukan dashboard tujuan saat login).
 *  - user_roles           = peran TAMBAHAN. Untuk Wali Kelas (3) kolom id_kelas
 *                           memuat kelas yang diampu.
 *
 * Semua pengecekan hak akses di controller/view memakai Peran::punya(...)
 * (bukan lagi session('id_role') == X) supaya peran tambahan ikut dihitung.
 */
class Peran
{
    public const GURU = 2;
    public const WALI = 3;
    public const WAKA = 4;
    public const SISWA = 5;
    public const BK = 7;

    /** Peran staf yang boleh dipilih sebagai peran tambahan (Siswa tidak boleh dirangkap). */
    public const BISA_TAMBAHAN = [
        self::GURU => 'Guru Pengampu',
        self::WALI => 'Wali Kelas',
        self::BK   => 'Guru BK',
        self::WAKA => 'Waka / Admin',
    ];

    private static ?bool $tabelAda = null;
    private static array $cache = [];

    public static function tabelAda(): bool
    {
        if (self::$tabelAda === null) {
            self::$tabelAda = Schema::hasTable('user_roles');
        }
        return self::$tabelAda;
    }

    /** Buat tabel user_roles bila belum ada (aman dipanggil berulang). */
    public static function pastikanTabel(): void
    {
        if (self::tabelAda()) {
            return;
        }

        Schema::create('user_roles', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('id_user')->index();
            $table->unsignedInteger('id_role');
            $table->string('id_kelas', 20)->nullable();
            $table->unique(['id_user', 'id_role']);
        });
        self::$tabelAda = true;
    }

    /** Baris peran tambahan milik user: [id_role => id_kelas|null]. */
    public static function tambahanUser(int $idUser): array
    {
        if (!self::tabelAda()) {
            return [];
        }
        return DB::table('user_roles')->where('id_user', $idUser)
            ->pluck('id_kelas', 'id_role')->all();
    }

    /** Semua id_role milik user yang sedang login (utama + tambahan), tanpa duplikat. */
    public static function semua(): array
    {
        $idUser = (int) session('id_user');
        $utama  = (int) session('id_role');
        $kunci  = $idUser . ':' . $utama;

        if (!isset(self::$cache[$kunci])) {
            $peran = $utama ? [$utama] : [];
            if ($idUser) {
                foreach (array_keys(self::tambahanUser($idUser)) as $r) {
                    $peran[] = (int) $r;
                }
            }
            self::$cache[$kunci] = array_values(array_unique($peran));
        }
        return self::$cache[$kunci];
    }

    /** Apakah user yang login punya SALAH SATU dari peran ini? */
    public static function punya(int ...$roles): bool
    {
        if (!session()->has('id_user')) {
            return false;
        }
        return count(array_intersect(self::semua(), $roles)) > 0;
    }

    public static function label(int $idRole): string
    {
        return match ($idRole) {
            1 => 'Admin',
            2 => 'Guru',
            3 => 'Wali Kelas',
            4 => 'Waka Kurikulum',
            5 => 'Siswa',
            6 => 'Orang Tua',
            7 => 'Guru BK',
            default => 'Pengguna',
        };
    }

    /** Label gabungan, mis. "Guru · Wali Kelas". */
    public static function labelSemua(): string
    {
        $daftar = self::semua();
        if (!$daftar) {
            return 'Pengguna';
        }
        return implode(' · ', array_map(fn ($r) => self::label($r), $daftar));
    }

    /**
     * Batas kelas untuk Wali Kelas.
     * null  = tidak dibatasi (Waka/BK, atau Wali Kelas lama yang belum ditetapkan kelasnya).
     * array = hanya boleh melihat/memverifikasi siswa di kelas-kelas ini.
     */
    public static function kelasWali(): ?array
    {
        if (self::punya(self::WAKA, self::BK)) {
            return null;
        }
        if (!self::punya(self::WALI) || !self::tabelAda()) {
            return null;
        }
        $kelas = DB::table('user_roles')
            ->where('id_user', (int) session('id_user'))
            ->where('id_role', self::WALI)
            ->whereNotNull('id_kelas')
            ->pluck('id_kelas')->all();

        return $kelas ?: null;
    }

    /** Simpan peran tambahan + kelas wali (menimpa isi sebelumnya). */
    public static function simpanTambahan(int $idUser, int $roleUtama, array $tambahan, ?string $kelasWali): void
    {
        self::pastikanTabel();

        $tambahan = array_values(array_unique(array_map('intval', $tambahan)));
        // Siswa tidak boleh merangkap; peran utama tidak perlu diulang sebagai tambahan.
        $tambahan = array_values(array_filter($tambahan, fn ($r) => isset(self::BISA_TAMBAHAN[$r]) && $r !== $roleUtama));
        if ($roleUtama === self::SISWA) {
            $tambahan = [];
        }

        DB::table('user_roles')->where('id_user', $idUser)->delete();

        $kelasWali = $kelasWali !== null && trim($kelasWali) !== '' ? trim($kelasWali) : null;
        $jadiWali  = $roleUtama === self::WALI || in_array(self::WALI, $tambahan, true);

        // Wali Kelas utama yang ditetapkan kelasnya: simpan baris khusus kelas.
        if ($roleUtama === self::WALI && $kelasWali) {
            DB::table('user_roles')->insert(['id_user' => $idUser, 'id_role' => self::WALI, 'id_kelas' => $kelasWali]);
        }
        foreach ($tambahan as $r) {
            DB::table('user_roles')->insert([
                'id_user'  => $idUser,
                'id_role'  => $r,
                'id_kelas' => ($r === self::WALI && $jadiWali) ? $kelasWali : null,
            ]);
        }

        self::$cache = [];
    }
}
