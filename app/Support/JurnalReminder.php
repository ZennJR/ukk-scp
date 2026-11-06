<?php

namespace App\Support;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menentukan jadwal HARI INI yang sudah selesai tetapi belum ada jurnalnya.
 * Dipindai saat halaman dashboard / jurnal dimuat (tanpa cron job).
 *
 * Jadwal dianggap milik hari ini jika tanggalnya hari ini, atau (tanggal kosong & nama harinya cocok).
 * Peringatan baru muncul bila jam selesai sudah lewat lebih dari BATAS_MENIT menit.
 */
class JurnalReminder
{
    /** Batas keterlambatan (menit) sebelum peringatan muncul. */
    public const BATAS_MENIT = 30;

    /**
     * Semua jadwal hari ini (putaran aktif) beserta status jurnalnya.
     * Tiap item mendapat atribut tambahan: status_jurnal, terlambat_menit, terlambat_teks.
     *
     * status_jurnal: terisi | terlambat | menunggu | berlangsung | akan_datang
     *
     * @param Guru|null $guru null = jadwal seluruh guru
     */
    public static function jadwalHariIni(?Guru $guru = null): Collection
    {
        $activeCycle = SystemConfiguration::first()->active_cycle ?? 'Putaran 1';
        $sekarang = now();
        $hariIni = $sekarang->format('Y-m-d');
        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'][$sekarang->dayOfWeekIso] ?? null;

        $query = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'ruangan', 'guru'])
            ->where('cycle_type', $activeCycle)
            ->whereNotNull('jam_mulai')
            ->whereNotNull('jam_selesai');

        if ($guru) {
            $query->where('id_guru', $guru->id_guru);
        }

        $jadwals = $query->get()->filter(function ($j) use ($hariIni, $namaHari) {
            return $j->tanggal
                ? Carbon::parse($j->tanggal)->format('Y-m-d') === $hariIni
                : ($namaHari !== null && $j->hari === $namaHari);
        });

        // Ruangan hasil tukar (yang sudah disetujui) berlaku untuk hari ini
        $jadwals = \App\Support\TukarRuangan::terapkan($jadwals->values(), $hariIni);

        $sudahIsi = Journal::whereIn('schedule_id', $jadwals->pluck('id_jadwal'))
            ->where('date', $hariIni)
            ->pluck('schedule_id')
            ->all();

        return $jadwals->map(function ($j) use ($sudahIsi, $sekarang, $hariIni) {
            $mulai = Carbon::parse($hariIni . ' ' . $j->jam_mulai);
            $selesai = Carbon::parse($hariIni . ' ' . $j->jam_selesai);

            $j->terlambat_menit = 0;
            if (in_array($j->id_jadwal, $sudahIsi)) {
                $j->status_jurnal = 'terisi';
            } elseif ($sekarang->lt($mulai)) {
                $j->status_jurnal = 'akan_datang';
            } elseif ($sekarang->lt($selesai)) {
                $j->status_jurnal = 'berlangsung';
            } else {
                $j->terlambat_menit = (int) $selesai->diffInMinutes($sekarang);
                $j->status_jurnal = $j->terlambat_menit > self::BATAS_MENIT ? 'terlambat' : 'menunggu';
            }
            $j->terlambat_teks = self::teksDurasi($j->terlambat_menit);

            return $j;
        })->sortBy('jam_mulai')->values();
    }

    /** Jadwal seorang guru yang selesai > 30 menit lalu dan belum dijurnal. */
    public static function belumDiisi(?Guru $guru): Collection
    {
        if (!$guru) {
            return collect();
        }

        return self::jadwalHariIni($guru)->where('status_jurnal', 'terlambat')->values();
    }

    /** Sama seperti belumDiisi tetapi untuk seluruh guru (rekap untuk Waka Kurikulum). */
    public static function belumDiisiSemua(): Collection
    {
        return self::jadwalHariIni()
            ->where('status_jurnal', 'terlambat')
            ->sortByDesc('terlambat_menit')
            ->values();
    }

    /** 45 -> "45 menit", 80 -> "1 jam 20 menit". */
    public static function teksDurasi(int $menit): string
    {
        if ($menit < 60) {
            return $menit . ' menit';
        }

        $jam = intdiv($menit, 60);
        $sisa = $menit % 60;

        return $jam . ' jam' . ($sisa ? ' ' . $sisa . ' menit' : '');
    }
}
