<?php

namespace App\Support;

use App\Models\JadwalPelajaran;
use App\Models\Ruangan;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tukar ruangan antar guru untuk SATU tanggal tertentu (tidak mengubah jadwal mingguan).
 *
 * Syarat: dua jadwal milik guru berbeda, pada tanggal yang sama, jamnya bersinggungan,
 * dan ruangannya berbeda. Pemohon mengajukan, guru tujuan menyetujui/menolak.
 * Setelah Disetujui, tampilan jadwal hari itu (dashboard/jurnal guru) memakai ruangan hasil tukar.
 */
class TukarRuangan
{
    public const AKTIF = ['Menunggu', 'Disetujui'];

    private static ?bool $tabelAda = null;

    public static function tabelAda(): bool
    {
        return self::$tabelAda ??= Schema::hasTable('tukar_ruangan');
    }

    /** Jadwal (putaran aktif) yang jatuh pada tanggal tertentu, lengkap dengan relasinya. */
    public static function jadwalPadaTanggal(Carbon $tgl): Collection
    {
        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'][$tgl->dayOfWeekIso] ?? null;
        $ymd = $tgl->format('Y-m-d');
        $putaran = SystemConfiguration::first()->active_cycle ?? 'Putaran 1';

        return JadwalPelajaran::with(['kelas', 'mataPelajaran', 'guru', 'ruangan'])
            ->where('cycle_type', $putaran)
            ->whereNotNull('jam_mulai')
            ->whereNotNull('jam_selesai')
            ->get()
            ->filter(function ($j) use ($ymd, $namaHari) {
                return $j->tanggal
                    ? Carbon::parse($j->tanggal)->format('Y-m-d') === $ymd
                    : ($namaHari !== null && $j->hari === $namaHari);
            })
            ->values();
    }

    /** Dua jadwal berjalan pada jam yang bersinggungan (ruangan dipakai bersamaan). */
    public static function bentrokWaktu($a, $b): bool
    {
        $am = substr((string) $a->jam_mulai, 0, 5);
        $as = substr((string) $a->jam_selesai, 0, 5);
        $bm = substr((string) $b->jam_mulai, 0, 5);
        $bs = substr((string) $b->jam_selesai, 0, 5);

        return $am < $bs && $bm < $as;
    }

    /** Jam mulai pada tanggal itu sudah lewat (kelas sudah/sedang berlangsung). */
    public static function mulaiTerlewat(string $tanggal, string $jamMulai): bool
    {
        return now()->gte(Carbon::parse($tanggal . ' ' . $jamMulai));
    }

    /** ID jadwal yang sudah terlibat pertukaran aktif (Menunggu/Disetujui) pada tanggal tersebut. */
    public static function idJadwalTerpakai(string $tanggal, ?int $kecualiId = null): array
    {
        if (!self::tabelAda()) {
            return [];
        }

        $query = DB::table('tukar_ruangan')->where('tanggal', $tanggal)->whereIn('status', self::AKTIF);
        if ($kecualiId) {
            $query->where('id', '!=', $kecualiId);
        }

        $ids = [];
        foreach ($query->get(['id_jadwal_pemohon', 'id_jadwal_tujuan']) as $r) {
            $ids[] = (int) $r->id_jadwal_pemohon;
            $ids[] = (int) $r->id_jadwal_tujuan;
        }

        return $ids;
    }

    public static function infoJadwal($j): string
    {
        return $j->kelas_label . ' - ' . $j->mapel_label . ' (' . substr($j->jam_mulai, 0, 5) . '-' . substr($j->jam_selesai, 0, 5) . ')';
    }

    /** Permintaan Menunggu yang jam mulainya sudah lewat otomatis menjadi Kedaluwarsa. */
    public static function kedaluwarsakan(): void
    {
        if (!self::tabelAda()) {
            return;
        }

        try {
            DB::table('tukar_ruangan')
                ->where('status', 'Menunggu')
                ->where(function ($q) {
                    $q->where('tanggal', '<', now()->format('Y-m-d'))
                      ->orWhere(function ($q2) {
                          $q2->where('tanggal', now()->format('Y-m-d'))->where('jam_mulai', '<=', now()->format('H:i:s'));
                      });
                })
                ->update(['status' => 'Kedaluwarsa', 'responded_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Terapkan ruangan hasil tukar (yang sudah Disetujui) pada daftar jadwal untuk tanggal tertentu.
     * Hanya mengubah relasi `ruangan` di memori (tidak menyentuh database) dan menandai `ruangan_ditukar`.
     */
    public static function terapkan(Collection $jadwals, string $tanggal): Collection
    {
        if (!self::tabelAda() || $jadwals->isEmpty()) {
            return $jadwals;
        }

        try {
            $ids = $jadwals->pluck('id_jadwal')->all();
            $rows = DB::table('tukar_ruangan')
                ->where('tanggal', $tanggal)
                ->where('status', 'Disetujui')
                ->where(function ($q) use ($ids) {
                    $q->whereIn('id_jadwal_pemohon', $ids)->orWhereIn('id_jadwal_tujuan', $ids);
                })
                ->get();

            if ($rows->isEmpty()) {
                return $jadwals;
            }

            $peta = [];
            foreach ($rows as $r) {
                $peta[(int) $r->id_jadwal_pemohon] = $r->id_ruangan_tujuan;
                $peta[(int) $r->id_jadwal_tujuan] = $r->id_ruangan_pemohon;
            }

            $ruang = Ruangan::whereIn('id_ruangan', array_values($peta))->get()->keyBy('id_ruangan');

            foreach ($jadwals as $j) {
                $baru = $peta[(int) $j->id_jadwal] ?? null;
                if ($baru !== null && $ruang->has($baru)) {
                    $j->setRelation('ruangan', $ruang->get($baru));
                    $j->ruangan_ditukar = true;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $jadwals;
    }
}
