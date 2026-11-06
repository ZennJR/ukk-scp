<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\Kelas;
use App\Models\Siswa;

/**
 * Rekap presensi siswa yang bersumber dari presensi yang diisi guru saat membuat jurnal
 * (tabel attendances -> journals). Dapat dilihat oleh Waka Kurikulum (4), Wali Kelas (3), dan Guru BK (7).
 */
class PresensiRekapController extends Controller
{
    private function guard()
    {
        if (!\App\Support\Peran::punya(3, 4, 7)) {
            return redirect()->route('login')->withErrors([
                'error' => 'Akses ditolak! Rekap presensi khusus Waka Kurikulum, Wali Kelas, dan Guru BK.',
            ]);
        }
        return null;
    }

    private function tanggal($nilai): ?string
    {
        $nilai = is_string($nilai) ? trim($nilai) : '';
        return ($nilai !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) && strtotime($nilai)) ? $nilai : null;
    }

    /**
     * Rekap jumlah Hadir/Izin/Sakit/Alfa per siswa, bisa difilter kelas, rentang tanggal, dan nama/NISN.
     */
    public function index(Request $request)
    {
        if ($deny = $this->guard()) {
            return $deny;
        }

        $kelasId = trim((string) $request->query('kelas', ''));
        $keyword = trim((string) $request->query('q', ''));
        $dari    = $this->tanggal($request->query('dari'));
        $sampai  = $this->tanggal($request->query('sampai'));

        $batas = \App\Support\Peran::kelasWali(); // Wali Kelas: hanya kelas yang diampu
        if ($batas && !in_array($kelasId, $batas, true)) {
            $kelasId = '';
        }

        $siswaQuery = Siswa::with('kelas')->where('status_utama', 'Aktif');
        if ($batas && $kelasId === '') {
            $siswaQuery->whereIn('id_kelas', $batas);
        }
        if ($kelasId !== '') {
            $siswaQuery->where('id_kelas', $kelasId);
        }
        if ($keyword !== '') {
            $siswaQuery->where(function ($q) use ($keyword) {
                $q->where('nama_siswa', 'like', "%{$keyword}%")
                  ->orWhere('nisn', 'like', "%{$keyword}%");
            });
        }
        $siswaList = $siswaQuery->get()
            ->sortBy(fn ($s) => sprintf('%s|%s', $s->kelas->nama_kelas ?? 'zzz', $s->nama_siswa))
            ->values();

        $agregat = collect();
        if ($siswaList->isNotEmpty()) {
            $agregat = DB::table('attendances as a')
                ->join('journals as j', 'j.id', '=', 'a.journal_id')
                ->whereIn('a.student_id', $siswaList->pluck('id_siswa'))
                ->when($dari, fn ($q) => $q->where('j.date', '>=', $dari))
                ->when($sampai, fn ($q) => $q->where('j.date', '<=', $sampai))
                ->selectRaw("a.student_id,
                    SUM(a.status = 'Hadir') AS hadir,
                    SUM(a.status = 'Izin')  AS izin,
                    SUM(a.status = 'Sakit') AS sakit,
                    SUM(a.status = 'Alfa')  AS alfa,
                    COUNT(*) AS total")
                ->groupBy('a.student_id')
                ->get()
                ->keyBy('student_id');
        }

        $baris = $siswaList->map(function ($siswa) use ($agregat) {
            $a = $agregat->get($siswa->id_siswa);
            $total = (int) ($a->total ?? 0);
            $hadir = (int) ($a->hadir ?? 0);
            return (object) [
                'siswa'  => $siswa,
                'hadir'  => $hadir,
                'izin'   => (int) ($a->izin ?? 0),
                'sakit'  => (int) ($a->sakit ?? 0),
                'alfa'   => (int) ($a->alfa ?? 0),
                'total'  => $total,
                'persen' => $total > 0 ? round($hadir / $total * 100) : null,
            ];
        });

        $ringkasan = [
            'siswa' => $baris->count(),
            'hadir' => $baris->sum('hadir'),
            'izin'  => $baris->sum('izin'),
            'sakit' => $baris->sum('sakit'),
            'alfa'  => $baris->sum('alfa'),
        ];

        return view('presensi.rekap', [
            'baris'      => $baris,
            'ringkasan'  => $ringkasan,
            'kelasList'  => Kelas::orderBy('tingkat')->orderBy('id_jurusan')
                ->when($batas, fn ($q) => $q->whereIn('id_kelas', $batas))->get(),
            'kelasId'    => $kelasId,
            'keyword'    => $keyword,
            'dari'       => $dari,
            'sampai'     => $sampai,
        ]);
    }

    /**
     * Riwayat presensi satu siswa per pertemuan (tanggal, mapel, guru, status).
     */
    public function detail(Request $request, $id)
    {
        if ($deny = $this->guard()) {
            return $deny;
        }

        $siswa  = Siswa::with('kelas')->findOrFail($id);
        if (($batas = \App\Support\Peran::kelasWali()) && !in_array($siswa->id_kelas, $batas, true)) {
            return redirect()->route('presensi.rekap')->withErrors(['error' => 'Akses ditolak! Anda hanya dapat melihat siswa kelas yang Anda ampu.']);
        }
        $dari   = $this->tanggal($request->query('dari'));
        $sampai = $this->tanggal($request->query('sampai'));

        return view('presensi.detail', ['siswa' => $siswa, 'dari' => $dari, 'sampai' => $sampai]
            + $this->dataRiwayat($siswa, $dari, $sampai));
    }

    /**
     * Halaman "Presensi Saya" untuk siswa: hanya data milik siswa yang sedang login.
     */
    public function saya(Request $request)
    {
        if (!\App\Support\Peran::punya(5)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Siswa.']);
        }

        $siswa = Siswa::with('kelas')->where('nisn', \App\Support\Identitas::dariSesi())->first();
        $dari   = $this->tanggal($request->query('dari'));
        $sampai = $this->tanggal($request->query('sampai'));

        return view('siswa.presensi', [
            'siswa'  => $siswa,
            'dari'   => $dari,
            'sampai' => $sampai,
        ] + ($siswa ? $this->dataRiwayat($siswa, $dari, $sampai) : [
            'riwayat' => collect(),
            'hitung'  => ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alfa' => 0],
            'total'   => 0,
            'persen'  => null,
        ]));
    }

    /**
     * Riwayat presensi + hitungan per status untuk satu siswa pada rentang tanggal tertentu.
     */
    private function dataRiwayat(Siswa $siswa, ?string $dari, ?string $sampai): array
    {
        $riwayat = Attendance::with(['journal.jadwal.mataPelajaran', 'journal.jadwal.guru'])
            ->where('student_id', $siswa->id_siswa)
            ->whereHas('journal', function ($q) use ($dari, $sampai) {
                if ($dari) {
                    $q->where('date', '>=', $dari);
                }
                if ($sampai) {
                    $q->where('date', '<=', $sampai);
                }
            })
            ->get()
            ->sortByDesc(fn ($a) => ($a->journal->date ?? '') . sprintf('%08d', $a->id))
            ->values();

        $hitung = $riwayat->groupBy('status')->map->count();
        $total  = $riwayat->count();
        $hadir  = (int) $hitung->get('Hadir', 0);

        return [
            'riwayat' => $riwayat,
            'hitung'  => [
                'Hadir' => $hadir,
                'Izin'  => (int) $hitung->get('Izin', 0),
                'Sakit' => (int) $hitung->get('Sakit', 0),
                'Alfa'  => (int) $hitung->get('Alfa', 0),
            ],
            'total'   => $total,
            'persen'  => $total > 0 ? round($hadir / $total * 100) : null,
        ];
    }
}
