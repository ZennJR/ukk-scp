<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Support\Identitas;
use App\Support\JurnalReminder;
use App\Support\KunciPutaran;

class GuruController extends Controller
{
    public function dashboard()
    {
        // Pengaman anti-bobol: hanya role Guru (2) dan BK (7)
        if (!\App\Support\Peran::punya(2, 7)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Guru.']);
        }

        $guru = Guru::where('nip', Identitas::dariSesi())->first();

        // Status kunci tiap putaran & putaran yang sedang berjalan (diatur Waka Kurikulum)
        $statusPutaran = KunciPutaran::status();
        $putaranAktif = KunciPutaran::config()->active_cycle ?? 'Putaran 1';

        // Dashboard Alert: dipindai saat halaman dimuat (tanpa cron job)
        $jadwalHariIni = JurnalReminder::jadwalHariIni($guru);
        $jurnalBelumDiisi = $jadwalHariIni->where('status_jurnal', 'terlambat')->values();

        $stat = [
            'jadwal'  => $jadwalHariIni->count(),
            'terisi'  => $jadwalHariIni->where('status_jurnal', 'terisi')->count(),
            'belum'   => $jurnalBelumDiisi->count(),
            'terkunci' => (bool) ($statusPutaran[$putaranAktif] ?? true),
        ];

        \App\Support\PelanggaranGuru::pindai();
        $pelanggaranTerbaru = \App\Support\PelanggaranGuru::terbaruUntukGuru($guru);

        return view('guru.dashboard', compact('jadwalHariIni', 'jurnalBelumDiisi', 'statusPutaran', 'putaranAktif', 'stat', 'pelanggaranTerbaru'));
    }
}
