<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status kunci jadwal PER PUTARAN (Putaran 1 / Putaran 2).
 *
 * KUNCI OTOMATIS: putaran yang terkunci adalah putaran yang SEDANG BERJALAN
 * (system_configurations.active_cycle). Putaran lain otomatis terbuka sehingga
 * guru bisa menyusun jadwal untuk putaran berikutnya. Saat Waka mengganti putaran
 * aktif, kunci ikut berpindah; tidak ada lagi tombol kunci/buka manual.
 *
 * Kolom lock_putaran_1 / lock_putaran_2 / is_schedule_locked tetap diisi
 * (sebagai cerminan) supaya kode atau laporan lama yang membacanya tidak rusak.
 */
class KunciPutaran
{
    /** nama putaran (sesuai ENUM jadwal_pelajaran.cycle_type) => kolom kunci */
    public const DAFTAR = [
        'Putaran 1' => 'lock_putaran_1',
        'Putaran 2' => 'lock_putaran_2',
    ];

    private static bool $siap = false;

    /**
     * Tambahkan kolom kunci per putaran bila belum ada (aman dipanggil berulang).
     * Status kunci lama (is_schedule_locked) diwariskan ke kedua putaran.
     */
    public static function pastikanKolom(): void
    {
        if (self::$siap) {
            return;
        }

        if (Schema::hasTable('system_configurations')) {
            foreach (self::DAFTAR as $kolom) {
                if (!Schema::hasColumn('system_configurations', $kolom)) {
                    Schema::table('system_configurations', function ($table) use ($kolom) {
                        $table->boolean($kolom)->default(false);
                    });
                    DB::table('system_configurations')->update([$kolom => DB::raw('is_schedule_locked')]);
                }
            }
        }

        self::$siap = true;
    }

    /** Baris konfigurasi (dibuat otomatis jika belum ada). */
    public static function config(): object
    {
        self::pastikanKolom();

        $config = DB::table('system_configurations')->first();
        if (!$config) {
            DB::table('system_configurations')->insert([
                'id'                 => 1,
                'active_cycle'       => 'Putaran 1',
                'is_schedule_locked' => 0,
                'updated_by'         => session('id_user'),
                'updated_at'         => now(),
            ]);
            $config = DB::table('system_configurations')->first();
        }

        return $config;
    }

    /** Nama putaran yang sedang berjalan (aktif). */
    public static function aktif(): string
    {
        $aktif = self::config()->active_cycle ?? null;
        return isset(self::DAFTAR[$aktif]) ? $aktif : array_key_first(self::DAFTAR);
    }

    /**
     * Terkunci = putaran yang sedang berjalan.
     *
     * @return array<string,bool> contoh: ['Putaran 1' => true, 'Putaran 2' => false]
     */
    public static function status(): array
    {
        $aktif = self::aktif();

        $hasil = [];
        foreach (self::DAFTAR as $nama => $kolom) {
            $hasil[$nama] = $nama === $aktif;
        }

        return $hasil;
    }

    /** Putaran yang tidak dikenal dianggap terkunci (aman secara default). */
    public static function terkunci(string $putaran): bool
    {
        return self::status()[$putaran] ?? true;
    }

    /** @return string[] daftar putaran yang masih terbuka */
    public static function terbuka(): array
    {
        return array_keys(array_filter(self::status(), fn ($terkunci) => !$terkunci));
    }

    /**
     * Tetapkan putaran yang berjalan. Putaran ini otomatis terkunci, yang lain terbuka.
     */
    public static function setAktif(string $putaranAktif, $idUser): void
    {
        $config = self::config();

        $data = [];
        foreach (self::DAFTAR as $nama => $kolom) {
            $data[$kolom] = $nama === $putaranAktif ? 1 : 0;
        }

        $data['is_schedule_locked'] = count(self::DAFTAR) <= 1 ? 1 : 0;
        $data['active_cycle']       = $putaranAktif;
        $data['updated_by']         = $idUser;
        $data['updated_at']         = now();

        DB::table('system_configurations')->where('id', $config->id)->update($data);
    }

    /**
     * Kompatibilitas kode lama: daftar putaran terkunci diabaikan karena kunci
     * selalu mengikuti putaran aktif.
     */
    public static function simpan(array $putaranTerkunci, string $putaranAktif, $idUser): void
    {
        self::setAktif($putaranAktif, $idUser);
    }
}
