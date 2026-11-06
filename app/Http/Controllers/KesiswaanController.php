<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Kelas;
use App\Support\Notifier;

class KesiswaanController extends Controller
{
    /** Role yang boleh memverifikasi izin: Wali Kelas (3) dan Guru BK (7). */
    private const ROLE_VERIFIKATOR = [3, 7];

    /** Role yang boleh melihat laporan harian: Wali Kelas, Waka Kurikulum, Guru BK. */
    private const ROLE_LAPORAN = [3, 4, 7];

    private function guard(array $roles, string $pesan)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }
        if (!\App\Support\Peran::punya(...$roles)) {
            return redirect()->route('login')->withErrors(['error' => $pesan]);
        }
        return null;
    }

    public function dashboard()
    {
        // Pengaman: hanya Wali Kelas (id_role == 3)
        if (!\App\Support\Peran::punya(3)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Wali Kelas.']);
        }

        $kelasDiampu = \App\Support\Peran::kelasWali();
        $namaKelasDiampu = $kelasDiampu
            ? Kelas::whereIn('id_kelas', $kelasDiampu)->get()->map(fn ($k) => $k->nama_kelas)->all()
            : [];

        return view('kesiswaan.dashboard', compact('namaKelasDiampu'));
    }

    public function index(Request $request)
    {
        if ($deny = $this->guard(self::ROLE_VERIFIKATOR, 'Akses ditolak! Verifikasi izin khusus Wali Kelas dan Guru BK.')) {
            return $deny;
        }

        // Ambil daftar pengajuan izin siswa, terbaru di atas (berdasarkan waktu pengajuan)
        $query = LeaveRequest::with('student')->orderByDesc('created_at')->orderByDesc('id');

        // Filter tanggal: tampilkan izin yang mencakup tanggal tersebut
        // (tanggal berada di antara start_date dan end_date).
        // Wali Kelas yang sudah ditetapkan kelasnya hanya melihat izin siswa di kelasnya.
        if ($batas = \App\Support\Peran::kelasWali()) {
            $query->whereHas('student', fn ($s) => $s->whereIn('id_kelas', $batas));
        }

        $tanggalFilter = $request->query('tanggal');
        if ($tanggalFilter) {
            $query->whereDate('start_date', '<=', $tanggalFilter)
                  ->whereDate('end_date', '>=', $tanggalFilter);
        }

        $pendingLeaves = $query->get();

        return view('kesiswaan.izin.index', compact('pendingLeaves', 'tanggalFilter'));
    }

    public function updateStatus(Request $request, $id)
    {
        if ($deny = $this->guard(self::ROLE_VERIFIKATOR, 'Akses ditolak! Verifikasi izin khusus Wali Kelas dan Guru BK.')) {
            return $deny;
        }

        $request->validate([
            'status_approval' => 'required|in:Approved,Rejected',
            'reject_reason'   => 'required_if:status_approval,Rejected|nullable|string|max:500',
        ], [
            'reject_reason.required_if' => 'Alasan penolakan wajib diisi.',
            'reject_reason.max'         => 'Alasan penolakan maksimal 500 karakter.',
        ]);

        $leave = LeaveRequest::with('student')->findOrFail($id);

        if (($batas = \App\Support\Peran::kelasWali()) && !in_array($leave->student->id_kelas ?? null, $batas, true)) {
            return redirect()->back()->withErrors(['error' => 'Akses ditolak! Anda hanya dapat memverifikasi izin siswa kelas yang Anda ampu.']);
        }

        $ditolak = $request->status_approval === 'Rejected';

        $leave->update([
            'status_approval' => $request->status_approval,
            'reject_reason'   => $ditolak ? trim($request->reject_reason) : null,
            'verified_by'     => session('id_user'),
        ]);

        if ($leave->student && $leave->student->nisn) {
            $pesan = 'Pengajuan izin Anda tanggal ' . date('d-m-Y', strtotime($leave->start_date))
                . ' s/d ' . date('d-m-Y', strtotime($leave->end_date))
                . ($ditolak ? ' ditolak. Alasan: ' . $leave->reject_reason : ' telah disetujui.');

            Notifier::untukNomorInduk(
                $leave->student->nisn,
                'izin',
                'Status Izin Diperbarui',
                mb_substr($pesan, 0, 255),
                route('siswa.izin.index')
            );
        }

        return redirect()->back()->with('success', 'Status pengajuan izin berhasil diperbarui!');
    }

    /**
     * Laporan izin siswa harian: siswa yang izinnya mencakup tanggal terpilih
     * (default hari ini), bisa difilter kelas dan status, dan siap dicetak.
     */
    public function laporanHarian(Request $request)
    {
        if ($deny = $this->guard(self::ROLE_LAPORAN, 'Akses ditolak! Laporan izin harian khusus Wali Kelas, Waka Kurikulum, dan Guru BK.')) {
            return $deny;
        }

        $tanggal = (string) $request->query('tanggal', '');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !strtotime($tanggal)) {
            $tanggal = now()->toDateString();
        }

        $kelasId = trim((string) $request->query('kelas', ''));
        $batas   = \App\Support\Peran::kelasWali();
        if ($batas && !in_array($kelasId, $batas, true)) {
            $kelasId = '';
        }
        $status  = (string) $request->query('status', '');
        if (!in_array($status, ['Pending', 'Approved', 'Rejected'], true)) {
            $status = '';
        }

        $laporan = LeaveRequest::with('student.kelas')
            ->whereDate('start_date', '<=', $tanggal)
            ->whereDate('end_date', '>=', $tanggal)
            ->when($status !== '', fn ($q) => $q->where('status_approval', $status))
            ->when($kelasId !== '', fn ($q) => $q->whereHas('student', fn ($s) => $s->where('id_kelas', $kelasId)))
            ->when($batas && $kelasId === '', fn ($q) => $q->whereHas('student', fn ($s) => $s->whereIn('id_kelas', $batas)))
            ->get()
            ->sortBy(fn ($l) => sprintf('%s|%s', $l->student->kelas->nama_kelas ?? 'zzz', $l->student->nama_siswa ?? ''))
            ->values();

        $ringkasan = [
            'total'    => $laporan->count(),
            'Approved' => $laporan->where('status_approval', 'Approved')->count(),
            'Pending'  => $laporan->where('status_approval', 'Pending')->count(),
            'Rejected' => $laporan->where('status_approval', 'Rejected')->count(),
            // Sakit bila alasan memuat kata "sakit" (sama dengan aturan sinkronisasi presensi)
            'sakit'    => $laporan->filter(fn ($l) => stripos((string) $l->reason, 'sakit') !== false)->count(),
        ];

        return view('kesiswaan.izin.laporan_harian', [
            'laporan'   => $laporan,
            'ringkasan' => $ringkasan,
            'tanggal'   => $tanggal,
            'kelasId'   => $kelasId,
            'status'    => $status,
            'kelasList' => Kelas::orderBy('tingkat')->orderBy('id_jurusan')
                ->when($batas, fn ($q) => $q->whereIn('id_kelas', $batas))->get(),
        ]);
    }
}
