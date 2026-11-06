<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\JurnalReminder;
use App\Support\KunciPutaran;
use App\Support\Notifier;

class WakaController extends Controller
{
    public function dashboard()
    {
        // PENGAMAN ANTI-BOBOL: Cek apakah user yang login memiliki id_role == 4 (Waka Kurikulum)
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Waka Kurikulum.']);
        }

        \App\Support\PelanggaranGuru::pindai();

        return view('waka.dashboard');
    }

    public function pengaturan()
    {
        // PENGAMAN ANTI-BOBOL: hanya Waka Kurikulum (id_role == 4)
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Waka Kurikulum.']);
        }

        $config = KunciPutaran::config();
        $statusPutaran = KunciPutaran::status();

        // Rekap guru yang belum mengisi jurnal (jadwal hari ini selesai > 30 menit), dipindai saat halaman dimuat
        $belumJurnal = JurnalReminder::belumDiisiSemua();

        $stat = [
            'total_guru'      => DB::table('guru')->count(),
            'jurnal_hari_ini' => DB::table('journals')->where('date', now()->format('Y-m-d'))->count(),
            'belum_diisi'     => $belumJurnal->count(),
            'terkunci'        => count(array_filter($statusPutaran)),
            'total_putaran'   => count($statusPutaran),
        ];

        $diperbaruiOleh = $config->updated_by
            ? DB::table('users')->where('id_user', $config->updated_by)->value('nama_lengkap')
            : null;

        return view('waka.pengaturan', compact('config', 'statusPutaran', 'belumJurnal', 'stat', 'diperbaruiOleh'));
    }

    public function updateConfig(Request $request)
    {
        // PENGAMAN ANTI-BOBOL: Pastikan hanya Waka Kurikulum yang bisa menyimpan perubahan konfigurasi
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Anda tidak memiliki hak untuk mengubah konfigurasi.']);
        }

        // Kolom system_configurations.active_cycle adalah ENUM('Putaran 1','Putaran 2').
        // Putaran yang dipilih menjadi putaran berjalan dan OTOMATIS terkunci.
        $request->validate([
            'active_cycle' => 'required|in:Putaran 1,Putaran 2',
        ]);

        $statusLama = KunciPutaran::status();

        KunciPutaran::setAktif($request->active_cycle, session('id_user'));

        $this->beritahuPerubahanKunci($statusLama);

        return back()->with('success', 'Putaran berjalan diubah menjadi ' . $request->active_cycle . ' dan otomatis terkunci.');
    }

    /**
     * Ganti putaran yang sedang berjalan (Putaran 1 / Putaran 2).
     * Putaran yang berjalan otomatis terkunci; putaran lainnya otomatis terbuka.
     * Seluruh dashboard guru mengikuti putaran ini.
     */
    public function setPutaranAktif(Request $request)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Anda tidak memiliki hak untuk mengubah konfigurasi.']);
        }

        $request->validate(['active_cycle' => 'required|in:Putaran 1,Putaran 2']);

        $statusLama = KunciPutaran::status();

        KunciPutaran::setAktif($request->active_cycle, session('id_user'));

        $this->beritahuPerubahanKunci($statusLama);

        return back()->with('success', "Putaran berjalan diubah menjadi {$request->active_cycle}. Putaran ini otomatis terkunci, putaran lain terbuka.");
    }

    /** Beri tahu guru & BK bila ada putaran yang statusnya berubah. */
    private function beritahuPerubahanKunci(array $statusLama): void
    {
        foreach (KunciPutaran::status() as $putaran => $terkunci) {
            if (($statusLama[$putaran] ?? null) === $terkunci) {
                continue;
            }

            Notifier::kirimKeRole(
                [2, 7],
                'jadwal',
                $terkunci ? "{$putaran} Sedang Berjalan (Terkunci)" : "{$putaran} Dibuka",
                $terkunci
                    ? "{$putaran} kini berjalan dan otomatis terkunci. Jadwal pada putaran ini tidak dapat ditambah lagi."
                    : "{$putaran} tidak lagi berjalan sehingga terbuka. Anda dapat membuat jadwal pada putaran ini.",
                route('guru.dashboard')
            );
        }
    }
}
