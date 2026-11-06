<?php

namespace App\Console\Commands;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\SystemConfiguration;
use App\Support\Notifier;
use Illuminate\Console\Command;

class CekJurnalBelumDiisi extends Command
{
    /**
     * Dijalankan berkala (lihat routes/console.php) untuk mengingatkan guru
     * yang jam pelajarannya sudah lewat hari ini tapi belum mengisi jurnal.
     */
    protected $signature = 'jadwal:cek-jurnal';

    protected $description = 'Kirim notifikasi pengingat ke guru yang belum mengisi jurnal untuk jadwal hari ini yang sudah selesai';

    public function handle(): int
    {
        $hariIni = now()->format('Y-m-d');
        $jamSekarang = now()->format('H:i:s');
        $config = SystemConfiguration::first();
        $activeCycle = $config->active_cycle ?? 'Putaran 1';

        // Jadwal dianggap milik hari ini jika:
        //  - tanggalnya persis hari ini, ATAU
        //  - tanggalnya kosong (jadwal mingguan dari form) dan nama harinya cocok.
        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'][now()->dayOfWeekIso] ?? null;
        if (!$namaHari) {
            $this->info('Hari Minggu, tidak ada jadwal.');
            return self::SUCCESS;
        }

        $jadwalSelesai = JadwalPelajaran::with(['kelas', 'mataPelajaran'])
            ->where(function ($q) use ($hariIni, $namaHari) {
                $q->where('tanggal', $hariIni)
                  ->orWhere(function ($q2) use ($namaHari) {
                      $q2->whereNull('tanggal')->where('hari', $namaHari);
                  });
            })
            ->where('cycle_type', $activeCycle)
            ->where('jam_selesai', '<=', $jamSekarang)
            ->get();

        $terkirim = 0;

        foreach ($jadwalSelesai as $jadwal) {
            $sudahIsi = Journal::where('schedule_id', $jadwal->id_jadwal)
                ->where('date', $hariIni)
                ->exists();

            if ($sudahIsi) {
                continue;
            }

            $guru = Guru::find($jadwal->id_guru);
            if (!$guru || !$guru->nip) {
                continue;
            }

            $namaKelas = $jadwal->kelas->nama_kelas ?? $jadwal->id_kelas;
            $namaMapel = $jadwal->mataPelajaran->nama_mapel ?? $jadwal->id_mapel;

            $dikirim = Notifier::untukNomorInduk(
                $guru->nip,
                'jurnal_reminder',
                'Pengingat Jurnal Mengajar',
                "Anda belum mengisi jurnal untuk kelas {$namaKelas} - {$namaMapel} hari ini ({$jadwal->jam_mulai} - {$jadwal->jam_selesai}).",
                route('jurnal.index'),
                "jurnal_reminder_{$jadwal->id_jadwal}_{$hariIni}"
            );

            if ($dikirim) {
                $terkirim++;
            }
        }

        $this->info("Pengingat jurnal terkirim: {$terkirim}");
        return self::SUCCESS;
    }
}