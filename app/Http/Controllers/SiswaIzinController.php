<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Siswa;
use Illuminate\Support\Facades\DB;
use App\Support\Notifier;

class SiswaIzinController extends Controller
{
    public function dashboard()
    {
        // Pengaman: hanya Siswa (id_role == 5)
        if (!\App\Support\Peran::punya(5)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Siswa.']);
        }

        return view('siswa.dashboard');
    }

    public function index()
    {
        // Pengaman anti-bobol sesi siswa
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        // Cari data siswa berdasarkan username (yang berisi NISN)
        $usernameLogin = \App\Support\Identitas::dariSesi(); // NISN (tetap walau username diganti)
        $siswa = Siswa::where('nisn', $usernameLogin)->first();

        $riwayatIzin = collect();
        if ($siswa) {
            // Ambil riwayat izin berdasarkan id_siswa yang valid
            $riwayatIzin = LeaveRequest::where('student_id', $siswa->id_siswa)->get();
        }

        return view('siswa.izin.index', compact('riwayatIzin'));
    }

    public function store(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis!']);
        }

        // Cari data siswa berdasarkan username (yang berisi NISN)
        $usernameLogin = \App\Support\Identitas::dariSesi(); // NISN (tetap walau username diganti)
        $siswa = Siswa::where('nisn', $usernameLogin)->first();

        if (!$siswa) {
            return redirect()->back()->withErrors(['error' => 'Data master siswa dengan NISN tersebut tidak ditemukan!']);
        }

        // Tanggal Mulai TIDAK boleh dipercaya dari input siswa (form sudah dibuat
        // disabled di tampilan, tapi itu saja tidak cukup karena request bisa
        // dikirim manual lewat luar form/browser). Server selalu memaksa
        // tanggal mulai = tanggal hari ini, apa pun yang dikirim siswa.
        $request->merge(['start_date' => now()->toDateString()]);

        $request->validate([
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'end_date.required'         => 'Tanggal selesai wajib diisi.',
            'end_date.date'             => 'Tanggal selesai tidak valid.',
            'end_date.after_or_equal'   => 'Tanggal selesai tidak boleh sebelum hari ini.',
            'reason.required'           => 'Alasan wajib diisi.',
            'reason.max'                => 'Alasan maksimal 500 karakter.',
            'document.required'         => 'Bukti (surat dokter/foto) wajib diunggah.',
            'document.mimes'            => 'Format bukti harus PDF, JPG, atau PNG.',
            'document.max'              => 'Ukuran bukti maksimal 2 MB.',
            'document.uploaded'         => 'Bukti gagal diunggah. Pastikan ukurannya maksimal 2 MB lalu coba lagi.',
        ]);

        $docPath = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/izin'), $filename);
            $docPath = 'uploads/izin/' . $filename;
        }

        LeaveRequest::create([
            'student_id' => $siswa->id_siswa, // Menggunakan id_siswa yang tepat dari tabel siswa
            'applicant_id' => session('id_user'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'document_path' => $docPath,
            'status_approval' => 'Pending',
        ]);

        Notifier::kirimKeRole(
            3, // Wali Kelas / Kesiswaan
            'izin',
            'Pengajuan Izin Baru',
            "{$siswa->nama_siswa} mengajukan izin tanggal " . date('d-m-Y', strtotime($request->start_date)) . ' s/d ' . date('d-m-Y', strtotime($request->end_date)) . '.',
            route('kesiswaan.izin.index')
        );

        return redirect()->back()->with('success', 'Pengajuan izin berhasil dikirim dan menunggu verifikasi Wali Kelas/BK.');
    }

    /**
     * Hapus pengajuan izin milik siswa sendiri, HANYA selama statusnya masih Pending.
     * Pengajuan yang sudah Disetujui/Ditolak tidak boleh dihapus (arsip untuk presensi & Wali Kelas).
     */
    public function destroy($id)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis!']);
        }

        $siswa = Siswa::where('nisn', \App\Support\Identitas::dariSesi())->first();
        if (!$siswa) {
            return redirect()->back()->withErrors(['error' => 'Data siswa tidak ditemukan.']);
        }

        $izin = LeaveRequest::where('id', $id)->where('student_id', $siswa->id_siswa)->first();
        if (!$izin) {
            return redirect()->back()->withErrors(['error' => 'Pengajuan izin tidak ditemukan atau bukan milik Anda.']);
        }

        if ($izin->status_approval !== 'Pending') {
            return redirect()->back()->withErrors(['error' => 'Pengajuan yang sudah diproses (disetujui/ditolak) tidak dapat dihapus.']);
        }

        // Hapus hanya bila MASIH Pending (cegah balapan dengan Wali Kelas yang baru memverifikasi)
        $terhapus = LeaveRequest::where('id', $izin->id)->where('status_approval', 'Pending')->delete();
        if (!$terhapus) {
            return redirect()->back()->withErrors(['error' => 'Pengajuan baru saja diproses sehingga tidak dapat dihapus.']);
        }

        // Hapus berkas bukti (hanya di dalam uploads/izin)
        if ($izin->document_path && str_starts_with($izin->document_path, 'uploads/izin/')) {
            $berkas = public_path($izin->document_path);
            if (is_file($berkas)) {
                @unlink($berkas);
            }
        }

        return redirect()->back()->with('success', 'Pengajuan izin yang masih pending berhasil dihapus.');
    }
}
