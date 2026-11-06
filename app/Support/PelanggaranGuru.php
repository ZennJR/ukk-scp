<?php

namespace App\Support;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pelanggaran guru: jurnal tidak diisi dalam N jam (default 5) setelah jam SELESAI jadwal.
 *
 *  - 'Tidak Mengisi' : sampai batas waktu lewat, jurnal belum ada sama sekali.
 *  - 'Terlambat'     : jurnal akhirnya diisi, tetapi setelah batas waktu.
 *
 * Dipindai saat halaman dimuat (tanpa cron) dan juga oleh `php artisan pelanggaran:pindai`.
 */
class PelanggaranGuru
{
    private static ?bool $tabelAda = null;
    private static ?bool $kolomFilledAt = null;

    public static function tabelAda(): bool
    {
        return self::$tabelAda ??= Schema::hasTable('pelanggaran_guru');
    }

    public static function kolomFilledAtAda(): bool
    {
        return self::$kolomFilledAt ??= Schema::hasColumn('journals', 'filled_at');
    }

    public static function batasJam(): int
    {
        return max(1, (int) config('pelanggaran.batas_jam', 5));
    }

    /** Batas akhir pengisian jurnal untuk jadwal pada tanggal tertentu. */
    public static function batasWaktu($jadwal, $tanggal): Carbon
    {
        $tgl = Carbon::parse($tanggal)->format('Y-m-d');
        return Carbon::parse($tgl . ' ' . $jadwal->jam_selesai)->addHours(self::batasJam());
    }

    private static function tanggalMulai(): Carbon
    {
        return Carbon::parse(config('pelanggaran.mulai_berlaku', '2026-11-06'))->startOfDay();
    }

    /** Jadwal (putaran aktif) yang jatuh pada tanggal tertentu. */
    private static function jadwalPadaTanggal(Carbon $tgl): Collection
    {
        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'][$tgl->dayOfWeekIso] ?? null;
        $ymd = $tgl->format('Y-m-d');
        $putaran = SystemConfiguration::first()->active_cycle ?? 'Putaran 1';

        return JadwalPelajaran::with(['kelas', 'mataPelajaran'])
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

    /**
     * Pindai jadwal beberapa hari terakhir; catat pelanggaran untuk yang lewat batas dan belum dijurnal.
     * Dibatasi 1x per menit agar ringan. Tidak pernah melempar error ke halaman.
     *
     * @return int jumlah pelanggaran baru
     */
    public static function pindai(bool $paksa = false): int
    {
        if (!self::tabelAda()) {
            return 0;
        }

        try {
            if (!$paksa && !Cache::add('pelanggaran_guru_pindai', 1, 60)) {
                return 0;
            }

            $sekarang = now();
            $mulai = self::tanggalMulai();
            $awal = $sekarang->copy()->startOfDay()->subDays(max(0, (int) config('pelanggaran.lookback_hari', 3)));
            if ($awal->lt($mulai)) {
                $awal = $mulai->copy();
            }

            $baru = 0;
            for ($tgl = $awal->copy(); $tgl->lte($sekarang->copy()->startOfDay()); $tgl->addDay()) {
                $ymd = $tgl->format('Y-m-d');
                $jadwals = self::jadwalPadaTanggal($tgl);
                if ($jadwals->isEmpty()) {
                    continue;
                }

                $sudahIsi = Journal::whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
                    ->where('date', $ymd)
                    ->pluck('schedule_id')
                    ->all();

                foreach ($jadwals as $j) {
                    if (in_array($j->id_jadwal, $sudahIsi)) {
                        continue;
                    }
                    $batas = self::batasWaktu($j, $ymd);
                    if ($sekarang->lt($batas)) {
                        continue;
                    }
                    if (self::catat($j, $ymd, 'Tidak Mengisi', $batas, null)) {
                        $baru++;
                    }
                }
            }

            return $baru;
        } catch (\Throwable $e) {
            report($e);
            return 0;
        }
    }

    /**
     * Dipanggil saat jurnal disimpan: bila lewat batas waktu, catat/ubah jadi pelanggaran 'Terlambat'.
     */
    public static function catatBilaTerlambat(JadwalPelajaran $jadwal, string $tanggal, Carbon $diisiAt): void
    {
        if (!self::tabelAda() || Carbon::parse($tanggal)->lt(self::tanggalMulai())) {
            return;
        }

        try {
            $batas = self::batasWaktu($jadwal, $tanggal);
            if ($diisiAt->lte($batas)) {
                return;
            }

            $ada = DB::table('pelanggaran_guru')
                ->where('schedule_id', $jadwal->id_jadwal)
                ->where('tanggal', $tanggal)
                ->first();

            if ($ada) {
                DB::table('pelanggaran_guru')->where('id', $ada->id)->update([
                    'jenis'           => 'Terlambat',
                    'jurnal_diisi_at' => $diisiAt,
                ]);
                return;
            }

            self::catat($jadwal, $tanggal, 'Terlambat', $batas, $diisiAt);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function catat(JadwalPelajaran $j, string $tanggal, string $jenis, Carbon $batas, ?Carbon $diisiAt): bool
    {
        $namaKelas = $j->kelas->nama_kelas ?? $j->id_kelas;
        $namaMapel = $j->mataPelajaran->nama_mapel ?? $j->id_mapel;

        $dibuat = DB::table('pelanggaran_guru')->insertOrIgnore([
            'id_guru'         => $j->id_guru,
            'schedule_id'     => $j->id_jadwal,
            'tanggal'         => $tanggal,
            'jenis'           => $jenis,
            'kelas'           => $namaKelas,
            'mapel'           => $namaMapel,
            'jam_mulai'       => $j->jam_mulai,
            'jam_selesai'     => $j->jam_selesai,
            'batas_waktu'     => $batas,
            'jurnal_diisi_at' => $diisiAt,
            'status'          => 'Aktif',
            'created_at'      => now(),
        ]);

        if (!$dibuat) {
            return false;
        }

        self::beritahu($j, $tanggal, $jenis, $namaKelas, $namaMapel);
        return true;
    }

    private static function beritahu(JadwalPelajaran $j, string $tanggal, string $jenis, $kelas, $mapel): void
    {
        $guru = Guru::find($j->id_guru);
        $namaGuru = $guru->nama_guru ?? 'Guru';
        $tglTeks = Carbon::parse($tanggal)->format('d-m-Y');
        $jam = substr($j->jam_mulai, 0, 5) . '-' . substr($j->jam_selesai, 0, 5);
        $batas = self::batasJam();

        $isi = $jenis === 'Terlambat'
            ? "jurnal kelas {$kelas} - {$mapel} ({$tglTeks}, {$jam}) diisi lewat {$batas} jam dari jam selesai jadwal."
            : "jurnal kelas {$kelas} - {$mapel} ({$tglTeks}, {$jam}) tidak diisi lebih dari {$batas} jam setelah jadwal selesai.";

        if ($guru && $guru->nip) {
            Notifier::untukNomorInduk(
                $guru->nip,
                'pelanggaran',
                'Peringatan: Pelanggaran Jurnal',
                'Anda tercatat melanggar: ' . $isi,
                route('jurnal.index'),
                "pelanggaran_{$j->id_jadwal}_{$tanggal}"
            );
        }

        Notifier::kirimKeRole(
            4,
            'pelanggaran',
            'Pelanggaran Guru Baru',
            "{$namaGuru}: " . $isi,
            route('waka.pelanggaran.rekap')
        );
    }

    /** Pelanggaran aktif seorang guru beberapa hari terakhir (untuk banner peringatan). */
    public static function terbaruUntukGuru(?Guru $guru, int $hari = 7): Collection
    {
        if (!$guru || !self::tabelAda()) {
            return collect();
        }

        return DB::table('pelanggaran_guru')
            ->where('id_guru', $guru->id_guru)
            ->where('status', 'Aktif')
            ->where('tanggal', '>=', now()->subDays($hari)->format('Y-m-d'))
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_selesai')
            ->get();
    }

    /** Total pelanggaran aktif seorang guru pada bulan berjalan. */
    public static function totalBulanIni(?Guru $guru): int
    {
        if (!$guru || !self::tabelAda()) {
            return 0;
        }

        return DB::table('pelanggaran_guru')
            ->where('id_guru', $guru->id_guru)
            ->where('status', 'Aktif')
            ->whereBetween('tanggal', [now()->startOfMonth()->format('Y-m-d'), now()->endOfMonth()->format('Y-m-d')])
            ->count();
    }
}
