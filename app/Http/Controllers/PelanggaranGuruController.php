<?php

namespace App\Http\Controllers;

use App\Support\PelanggaranGuru;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelanggaranGuruController extends Controller
{
    private function bukanWaka()
    {
        return !\App\Support\Peran::punya(4)
            ? redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Waka Kurikulum.'])
            : null;
    }

    /**
     * Rekap pelanggaran guru (jurnal tidak diisi / terlambat diisi > batas jam dari jadwal selesai).
     */
    public function rekap(Request $request)
    {
        if ($tolak = $this->bukanWaka()) {
            return $tolak;
        }

        $tabelAda = PelanggaranGuru::tabelAda();
        $batasJam = PelanggaranGuru::batasJam();
        $gurus = DB::table('guru')->orderBy('nama_guru')->get(['id_guru', 'nama_guru']);

        if (!$tabelAda) {
            return view('waka.pelanggaran_rekap', [
                'tabelAda' => false, 'batasJam' => $batasJam, 'gurus' => $gurus,
                'list' => collect(), 'perGuru' => collect(), 'stat' => [], 'filter' => [],
            ]);
        }

        PelanggaranGuru::pindai();

        $bulan = $request->query('bulan', now()->format('Y-m'));          // 'YYYY-MM' atau 'semua'
        $idGuru = $request->query('guru', '');
        $jenis = $request->query('jenis', '');
        $status = $request->query('status', 'Aktif');                      // Aktif | Dibatalkan | semua

        $query = DB::table('pelanggaran_guru as p')
            ->leftJoin('guru as g', 'g.id_guru', '=', 'p.id_guru')
            ->select('p.*', 'g.nama_guru', 'g.nip');

        if ($bulan !== 'semua' && preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $awal = Carbon::createFromFormat('Y-m-d', $bulan . '-01')->startOfMonth();
            $query->whereBetween('p.tanggal', [$awal->format('Y-m-d'), $awal->copy()->endOfMonth()->format('Y-m-d')]);
        } else {
            $bulan = 'semua';
        }
        if ($idGuru !== '') {
            $query->where('p.id_guru', (int) $idGuru);
        }
        if (in_array($jenis, ['Tidak Mengisi', 'Terlambat'], true)) {
            $query->where('p.jenis', $jenis);
        } else {
            $jenis = '';
        }
        if (in_array($status, ['Aktif', 'Dibatalkan'], true)) {
            $query->where('p.status', $status);
        } else {
            $status = 'semua';
        }

        $list = $query->orderByDesc('p.tanggal')->orderByDesc('p.jam_selesai')->orderBy('g.nama_guru')->get();

        $aktif = $list->where('status', 'Aktif');
        $perGuru = $aktif->groupBy('id_guru')->map(function ($baris) {
            return (object) [
                'nama_guru'     => $baris->first()->nama_guru ?? ('Guru #' . $baris->first()->id_guru),
                'nip'           => $baris->first()->nip,
                'total'         => $baris->count(),
                'tidak_mengisi' => $baris->where('jenis', 'Tidak Mengisi')->count(),
                'terlambat'     => $baris->where('jenis', 'Terlambat')->count(),
            ];
        })->sortByDesc('total')->values();

        $stat = [
            'total'         => $aktif->count(),
            'guru'          => $perGuru->count(),
            'tidak_mengisi' => $aktif->where('jenis', 'Tidak Mengisi')->count(),
            'terlambat'     => $aktif->where('jenis', 'Terlambat')->count(),
        ];

        return view('waka.pelanggaran_rekap', [
            'tabelAda' => true,
            'batasJam' => $batasJam,
            'gurus'    => $gurus,
            'list'     => $list,
            'perGuru'  => $perGuru,
            'stat'     => $stat,
            'filter'   => compact('bulan', 'idGuru', 'jenis', 'status'),
        ]);
    }

    /**
     * Batalkan / aktifkan kembali sebuah pelanggaran (mis. jadwal ternyata dibatalkan, guru sakit/dinas).
     * Data tidak dihapus agar tidak tercatat ulang oleh pemindaian otomatis.
     */
    public function ubahStatus(Request $request, $id)
    {
        if ($tolak = $this->bukanWaka()) {
            return $tolak;
        }

        $request->validate([
            'status' => 'required|in:Aktif,Dibatalkan',
            'catatan' => 'nullable|string|max:255',
        ]);

        $baris = DB::table('pelanggaran_guru')->where('id', $id)->first();
        if (!$baris) {
            return back()->withErrors(['error' => 'Data pelanggaran tidak ditemukan.']);
        }

        DB::table('pelanggaran_guru')->where('id', $id)->update([
            'status'  => $request->status,
            'catatan' => $request->status === 'Dibatalkan' ? $request->catatan : null,
        ]);

        return back()->with('success', $request->status === 'Dibatalkan'
            ? 'Pelanggaran dibatalkan dan tidak dihitung lagi.'
            : 'Pelanggaran diaktifkan kembali.');
    }
}
