<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JadwalPelajaran;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\Guru;
use App\Models\LeaveRequest;
use App\Models\Siswa;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Support\Notifier;

class JurnalController extends Controller
{
    public function index()
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $user = Auth::user();
        // Dicari lewat NIP (nomor induk), bukan username, agar tetap tertaut walau username diganti
        $username = \App\Support\Identitas::dariSesi() ?? ($user ? $user->username : null);

        $guru = Guru::where('nip', $username)->first();

        if (!$guru) {
            return redirect()->back()->with('error', 'Data guru tidak ditemukan untuk akun ini.');
        }

        // Catat pelanggaran (jurnal > batas jam tidak diisi) sebelum halaman ditampilkan
        \App\Support\PelanggaranGuru::pindai();

        // Semua jadwal guru (dipakai untuk riwayat jurnal)
        $jadwalSemua = JadwalPelajaran::where('id_guru', $guru->id_guru)->get();

        // Pilihan jadwal di form: HANYA jadwal hari ini yang belum dijurnal
        $jadwals = $this->jadwalBisaDijurnal($guru);

        $jurnalList = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran', 'attendances'])
            ->whereIn('schedule_id', $jadwalSemua->pluck('id_jadwal'))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
        
        // Peringatan jadwal hari ini yang sudah selesai tapi belum ada jurnalnya
        $jurnalBelumDiisi = \App\Support\JurnalReminder::belumDiisi($guru);

        $pelanggaranTerbaru = \App\Support\PelanggaranGuru::terbaruUntukGuru($guru);

        return view('jurnal.index', compact('jadwals', 'jurnalList', 'guru', 'jurnalBelumDiisi', 'pelanggaranTerbaru'));
    }

    /**
     * Guru yang sedang login (null jika tidak ditemukan).
     */
    private function guruLogin(): ?Guru
    {
        $nip = \App\Support\Identitas::dariSesi() ?? (Auth::user()->username ?? null);
        return $nip ? Guru::where('nip', $nip)->first() : null;
    }

    /**
     * Jadwal milik guru yang boleh dijurnal SEKARANG: hanya jadwal hari ini (putaran aktif)
     * yang belum punya jurnal pada hari ini.
     */
    private function jadwalBisaDijurnal(Guru $guru)
    {
        return \App\Support\JurnalReminder::jadwalHariIni($guru)
            ->where('status_jurnal', '!=', 'terisi')
            ->values();
    }

    /** Kolom attendances.keterangan sudah dibuat? (aman bila SQL/migrasi belum dijalankan) */
    private function kolomKeteranganAda(): bool
    {
        static $ada = null;
        return $ada ??= \Illuminate\Support\Facades\Schema::hasColumn('attendances', 'keterangan');
    }

    /** Keterangan hanya berlaku untuk status Izin buatan guru; dipangkas & dibatasi 255 karakter. */
    private function keteranganIzin(?string $status, $teks): ?string
    {
        if ($status !== 'Izin') {
            return null;
        }
        $teks = trim((string) $teks);
        return $teks === '' ? null : mb_substr($teks, 0, 255);
    }

    /** Jurnal hanya boleh diubah pada hari jurnal itu dibuat/bertanggal. */
    private function jurnalHariIni(Journal $jurnal): bool
    {
        return \Illuminate\Support\Carbon::parse($jurnal->date)->isToday();
    }

    private function sesiHabis()
    {
        return redirect()->route('login')->withErrors(['error' => 'Sesi habis, silakan login kembali!']);
    }

    /**
     * LANGKAH 1 (F.UC-08 Form Jurnal): validasi topik & foto, simpan sebagai draft sementara,
     * lalu lanjut ke halaman presensi. Belum ada data yang masuk ke tabel journals/attendances.
     */
    public function lanjut(Request $request)
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return $this->sesiHabis();
        }

        $guru = $this->guruLogin();
        if (!$guru) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Data guru tidak ditemukan untuk akun ini.']);
        }

        $pesanFoto = 'Format foto tidak valid atau ukuran melebihi batas.';
        $request->validate([
            'schedule_id'       => 'required',
            'topic_description' => 'required|string',
            'photo'             => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'schedule_id.required'       => 'Mohon lengkapi semua field wajib.',
            'topic_description.required' => 'Mohon lengkapi semua field wajib.',
            'photo.required'             => 'Mohon lengkapi semua field wajib.',
            'photo.image'                => $pesanFoto,
            'photo.mimes'                => $pesanFoto,
            'photo.max'                  => $pesanFoto,
            'photo.uploaded'             => $pesanFoto,
        ]);

        // Jadwal harus milik guru yang sedang login DAN merupakan jadwal hari ini yang belum dijurnal
        $jadwal = $this->jadwalBisaDijurnal($guru)->firstWhere('id_jadwal', (int) $request->schedule_id);
        if (!$jadwal) {
            return back()->withErrors(['schedule_id' => 'Jurnal hanya bisa diisi untuk jadwal hari ini yang belum dijurnal.'])->withInput();
        }

        // Buang draft lama (kalau ada), lalu simpan foto draft di storage non-publik
        $this->buangDraft();

        $file = $request->file('photo');
        $namaFile = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $folderDraft = storage_path('app/jurnal_draft');
        File::ensureDirectoryExists($folderDraft);
        $file->move($folderDraft, $namaFile);

        session(['jurnal_draft' => [
            'schedule_id'       => (int) $jadwal->id_jadwal,
            'topic_description' => $request->topic_description,
            'photo_name'        => $namaFile,
            'tanggal'           => date('Y-m-d'),
        ]]);

        return redirect()->route('jurnal.presensi');
    }

    /**
     * LANGKAH 2 (Presensi Siswa): daftar siswa kelas terkait. Siswa dengan izin Approved
     * pada tanggal jurnal otomatis berstatus Izin/Sakit (UC-16 Sinkronisasi Izin ke Presensi).
     */
    public function presensi()
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return $this->sesiHabis();
        }

        $guru = $this->guruLogin();
        $draft = session('jurnal_draft');
        if (!$guru || !$draft || !is_file(storage_path('app/jurnal_draft/' . $draft['photo_name']))) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Data jurnal belum diisi. Silakan isi form jurnal terlebih dahulu.']);
        }

        if (($draft['tanggal'] ?? null) !== date('Y-m-d')) {
            $this->buangDraft();
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jurnal hanya bisa diisi pada hari yang sama. Silakan isi ulang.']);
        }

        $jadwal = JadwalPelajaran::with(['kelas', 'mataPelajaran'])
            ->where('id_guru', $guru->id_guru)
            ->find($draft['schedule_id']);
        if (!$jadwal) {
            $this->buangDraft();
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jadwal tidak ditemukan.']);
        }

        $daftarSiswa = $this->daftarSiswa($jadwal, $draft['tanggal']);

        return view('jurnal.presensi', [
            'jadwal'      => $jadwal,
            'draft'       => $draft,
            'daftarSiswa' => $daftarSiswa,
        ]);
    }

    /**
     * Batalkan pengisian (kembali dari halaman presensi): hapus draft + foto sementara.
     */
    public function batal()
    {
        $this->buangDraft();
        return redirect()->route('jurnal.index');
    }

    /**
     * LANGKAH 3: simpan jurnal + presensi seluruh siswa dalam satu transaksi.
     */
    public function store(Request $request)
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return $this->sesiHabis();
        }

        $guru = $this->guruLogin();
        $draft = session('jurnal_draft');
        $fotoDraft = $draft ? storage_path('app/jurnal_draft/' . $draft['photo_name']) : null;
        if (!$guru || !$draft || !is_file($fotoDraft)) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Data jurnal belum diisi atau sudah kedaluwarsa. Silakan isi ulang.']);
        }

        if (($draft['tanggal'] ?? null) !== date('Y-m-d')) {
            $this->buangDraft();
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jurnal hanya bisa disimpan pada hari yang sama dengan jadwal. Silakan isi ulang.']);
        }

        $request->validate([
            'attendance'   => 'nullable|array',
            'attendance.*' => 'in:Hadir,Sakit,Izin,Alfa',
            'keterangan'   => 'nullable|array',
            'keterangan.*' => 'nullable|string|max:255',
        ], [
            'attendance.*.in'    => 'Status presensi tidak valid.',
            'keterangan.*.max'   => 'Keterangan izin maksimal 255 karakter.',
        ]);

        $jadwal = JadwalPelajaran::with(['kelas', 'mataPelajaran', 'guru'])
            ->where('id_guru', $guru->id_guru)
            ->find($draft['schedule_id']);
        if (!$jadwal) {
            $this->buangDraft();
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jadwal tidak ditemukan.']);
        }

        // Status final: izin Approved selalu dipaksa dari sistem; sisanya sesuai pilihan guru (default Hadir)
        $input = $request->input('attendance', []);
        $inputKet = $request->input('keterangan', []);
        $adaKet = $this->kolomKeteranganAda();
        $presensi = [];
        foreach ($this->daftarSiswa($jadwal, $draft['tanggal']) as $siswa) {
            $statusFinal = $siswa->status_izin ?? ($input[$siswa->id_siswa] ?? 'Hadir');
            $baris = [
                'student_id' => $siswa->id_siswa,
                'status'     => $statusFinal,
            ];
            if ($adaKet) {
                // Izin dari pengajuan disetujui dikunci sistem; keterangan hanya untuk Izin pilihan guru
                $baris['keterangan'] = $siswa->status_izin ? null : $this->keteranganIzin($statusFinal, $inputKet[$siswa->id_siswa] ?? null);
            }
            $presensi[] = $baris;
        }

        // Pindahkan foto dari draft ke folder publik
        $folderPublik = public_path('uploads/jurnal');
        File::ensureDirectoryExists($folderPublik);
        $fotoFinal = $folderPublik . DIRECTORY_SEPARATOR . $draft['photo_name'];
        if (!@rename($fotoDraft, $fotoFinal)) {
            return back()->withErrors(['error' => 'Gagal menyimpan foto bukti. Coba lagi.']);
        }

        DB::beginTransaction();
        try {
            $waktuIsi = now();
            $dataJurnal = [
                'schedule_id'       => $draft['schedule_id'],
                'date'              => $draft['tanggal'],
                'topic_description' => $draft['topic_description'],
                'photo_path'        => 'uploads/jurnal/' . $draft['photo_name'],
            ];
            // Jam guru mengisi jurnal (aktif setelah kolom journals.filled_at dibuat)
            if (\App\Support\PelanggaranGuru::kolomFilledAtAda()) {
                $dataJurnal['filled_at'] = $waktuIsi;
            }
            $journalId = DB::table('journals')->insertGetId($dataJurnal);

            foreach ($presensi as &$baris) {
                $baris['journal_id'] = $journalId;
            }
            unset($baris);

            if (!empty($presensi)) {
                DB::table('attendances')->insert($presensi);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            @rename($fotoFinal, $fotoDraft); // kembalikan foto ke draft agar guru bisa mencoba lagi
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }

        session()->forget('jurnal_draft');

        // Diisi melewati batas waktu (jam selesai + 5 jam) => tercatat pelanggaran 'Terlambat'
        \App\Support\PelanggaranGuru::catatBilaTerlambat($jadwal, $draft['tanggal'], $waktuIsi);

        $namaKelas = $jadwal->kelas->nama_kelas ?? $jadwal->id_kelas;
        $namaMapel = $jadwal->mataPelajaran->nama_mapel ?? $jadwal->id_mapel;
        $namaGuru = $jadwal->guru->nama_guru ?? 'Guru';
        Notifier::kirimKeRole(
            4, // Waka Kurikulum
            'jurnal',
            'Jurnal Mengajar Baru',
            "{$namaGuru} mengisi jurnal untuk kelas {$namaKelas} - {$namaMapel}.",
            route('waka.jurnal.rekap')
        );

        return redirect()->route('jurnal.index')->with('success', 'Jurnal mengajar dan presensi siswa berhasil disimpan!');
    }

    /**
     * Siswa aktif di kelas jadwal. Properti tambahan `status_izin` berisi 'Izin'/'Sakit'
     * bila siswa punya pengajuan Approved yang mencakup tanggal tersebut, selain itu null.
     */
    private function daftarSiswa(JadwalPelajaran $jadwal, string $tanggal)
    {
        $siswaList = Siswa::where('id_kelas', $jadwal->id_kelas)
            ->where('status_utama', 'Aktif')
            ->orderBy('nama_siswa')
            ->get();

        if ($siswaList->isEmpty()) {
            return $siswaList;
        }

        $izin = LeaveRequest::whereIn('student_id', $siswaList->pluck('id_siswa'))
            ->where('status_approval', 'Approved')
            ->whereDate('start_date', '<=', $tanggal)
            ->whereDate('end_date', '>=', $tanggal)
            ->orderByDesc('id')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

        return $siswaList->map(function ($siswa) use ($izin) {
            $pengajuan = $izin->get($siswa->id_siswa);
            // Alasan memuat kata "sakit" -> Sakit, selain itu -> Izin
            $siswa->status_izin = $pengajuan
                ? (stripos((string) $pengajuan->reason, 'sakit') !== false ? 'Sakit' : 'Izin')
                : null;
            $siswa->alasan_izin = $pengajuan->reason ?? null;
            return $siswa;
        });
    }

    /**
     * Jurnal milik guru yang sedang login (null bila bukan miliknya / tidak ada).
     */
    private function jurnalMilikGuru(Guru $guru, $id): ?Journal
    {
        $jurnal = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran'])->find($id);
        if (!$jurnal || !$jurnal->jadwal || (int) $jurnal->jadwal->id_guru !== (int) $guru->id_guru) {
            return null;
        }
        return $jurnal;
    }

    /**
     * Form edit daftar hadir siswa pada jurnal yang sudah tersimpan (hanya guru pemilik jurnal).
     * Izin/Sakit yang sudah Approved pada tanggal jurnal tetap dikunci dari sistem.
     */
    public function editPresensi($id)
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return $this->sesiHabis();
        }

        $guru = $this->guruLogin();
        $jurnal = $guru ? $this->jurnalMilikGuru($guru, $id) : null;
        if (!$jurnal) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jurnal tidak ditemukan atau bukan milik Anda.']);
        }

        if (!$this->jurnalHariIni($jurnal)) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jurnal hanya bisa diedit pada hari yang sama. Hubungi Waka Kurikulum bila perlu perubahan.']);
        }

        $tersimpan = Attendance::where('journal_id', $jurnal->id)->pluck('status', 'student_id');
        $ketTersimpan = $this->kolomKeteranganAda()
            ? Attendance::where('journal_id', $jurnal->id)->pluck('keterangan', 'student_id')
            : collect();

        $daftarSiswa = $this->daftarSiswa($jurnal->jadwal, $jurnal->date)
            ->map(function ($siswa) use ($tersimpan, $ketTersimpan) {
                $siswa->status_tersimpan = $tersimpan->get($siswa->id_siswa, 'Hadir');
                $siswa->keterangan_tersimpan = $ketTersimpan->get($siswa->id_siswa);
                return $siswa;
            });

        return view('jurnal.edit_presensi', [
            'jurnal'      => $jurnal,
            'jadwal'      => $jurnal->jadwal,
            'daftarSiswa' => $daftarSiswa,
        ]);
    }

    /**
     * Simpan perubahan daftar hadir siswa pada jurnal yang sudah ada.
     */
    public function updatePresensi(Request $request, $id)
    {
        if (!Auth::check() && !session()->has('id_user')) {
            return $this->sesiHabis();
        }

        $guru = $this->guruLogin();
        $jurnal = $guru ? $this->jurnalMilikGuru($guru, $id) : null;
        if (!$jurnal) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jurnal tidak ditemukan atau bukan milik Anda.']);
        }

        if (!$this->jurnalHariIni($jurnal)) {
            return redirect()->route('jurnal.index')->withErrors(['error' => 'Jurnal hanya bisa diedit pada hari yang sama. Hubungi Waka Kurikulum bila perlu perubahan.']);
        }

        $request->validate([
            'attendance'   => 'nullable|array',
            'attendance.*' => 'in:Hadir,Sakit,Izin,Alfa',
            'keterangan'   => 'nullable|array',
            'keterangan.*' => 'nullable|string|max:255',
        ], [
            'attendance.*.in'    => 'Status presensi tidak valid.',
            'keterangan.*.max'   => 'Keterangan izin maksimal 255 karakter.',
        ]);

        $input = $request->input('attendance', []);
        $inputKet = $request->input('keterangan', []);
        $adaKet = $this->kolomKeteranganAda();

        DB::beginTransaction();
        try {
            foreach ($this->daftarSiswa($jurnal->jadwal, $jurnal->date) as $siswa) {
                // Izin Approved selalu dipaksa dari sistem; selain itu ikut pilihan guru
                $status = $siswa->status_izin ?? ($input[$siswa->id_siswa] ?? 'Hadir');

                $data = ['status' => $status];
                if ($adaKet) {
                    $data['keterangan'] = $siswa->status_izin ? null : $this->keteranganIzin($status, $inputKet[$siswa->id_siswa] ?? null);
                }

                DB::table('attendances')->updateOrInsert(
                    ['journal_id' => $jurnal->id, 'student_id' => $siswa->id_siswa],
                    $data
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan perubahan presensi: ' . $e->getMessage()]);
        }

        return redirect()->route('jurnal.index')->with('success', 'Daftar hadir siswa berhasil diperbarui!');
    }

    /** Hapus draft jurnal di session beserta foto sementaranya. */
    private function buangDraft(): void
    {
        $draft = session('jurnal_draft');
        if ($draft && !empty($draft['photo_name'])) {
            $path = storage_path('app/jurnal_draft/' . basename($draft['photo_name']));
            if (is_file($path)) {
                @unlink($path);
            }
        }
        session()->forget('jurnal_draft');
    }

    /**
     * Fitur Rekap Jurnal untuk Waka Kurikulum
     */
    public function rekapWaka()
    {
        // Pastikan yang mengakses adalah Waka Kurikulum (id_role == 4)
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Khusus Waka Kurikulum.']);
        }

        \App\Support\PelanggaranGuru::pindai();

        // Ambil semua data jurnal beserta relasi jadwal, kelas, mata pelajaran, dan data gurunya
        $jurnalList = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran', 'jadwal.guru'])
            ->orderBy('date', 'desc')
            ->get();

        return view('jurnal.rekap_waka', compact('jurnalList'));
    }

    /**
     * Edit jurnal (khusus Waka Kurikulum): tanggal, topik/materi, dan foto (opsional).
     */
    public function updateWaka(Request $request, $id)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Khusus Waka Kurikulum.']);
        }

        $jurnal = Journal::with('jadwal.guru')->find($id);
        if (!$jurnal) {
            return redirect()->route('waka.jurnal.rekap')->withErrors(['error' => 'Jurnal tidak ditemukan.']);
        }

        $request->validate([
            'date' => 'required|date',
            'topic_description' => 'required|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $jurnal->date = $request->date;
        $jurnal->topic_description = $request->topic_description;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/jurnal'), $filename);

            $this->hapusFileFoto($jurnal->photo_path); // buang foto lama
            $jurnal->photo_path = 'uploads/jurnal/' . $filename;
        }

        $jurnal->save();
        $this->notifikasiJurnalUntukGuru($jurnal, 'Jurnal Diubah', 'diubah');

        return redirect()->route('waka.jurnal.rekap')->with('success', 'Jurnal mengajar berhasil diperbarui!');
    }

    /**
     * Hapus jurnal (khusus Waka Kurikulum), termasuk file fotonya.
     */
    public function destroyWaka($id)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Khusus Waka Kurikulum.']);
        }

        $jurnal = Journal::with('jadwal.guru')->find($id);
        if (!$jurnal) {
            return redirect()->route('waka.jurnal.rekap')->withErrors(['error' => 'Jurnal tidak ditemukan.']);
        }

        $this->notifikasiJurnalUntukGuru($jurnal, 'Jurnal Dihapus', 'dihapus');
        $this->hapusFileFoto($jurnal->photo_path);
        DB::table('attendances')->where('journal_id', $jurnal->id)->delete();
        $jurnal->delete();

        return redirect()->route('waka.jurnal.rekap')->with('success', 'Jurnal mengajar berhasil dihapus!');
    }

    /** Hapus file foto jurnal dari public/uploads/jurnal (hanya di folder itu). */
    private function hapusFileFoto(?string $path): void
    {
        if (!$path || !str_starts_with($path, 'uploads/jurnal/') || str_contains($path, '..')) {
            return;
        }
        $full = public_path($path);
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function notifikasiJurnalUntukGuru(Journal $jurnal, string $judul, string $aksi): void
    {
        $jadwal = $jurnal->jadwal;
        $nip = $jadwal->guru->nip ?? null;
        if (!$jadwal || !$nip) {
            return;
        }

        $namaKelas = $jadwal->kelas->nama_kelas ?? $jadwal->id_kelas;
        $namaMapel = $jadwal->mataPelajaran->nama_mapel ?? $jadwal->id_mapel;

        Notifier::untukNomorInduk(
            $nip,
            'jurnal',
            $judul,
            "Jurnal mengajar {$namaMapel} kelas {$namaKelas} tanggal {$jurnal->date} telah {$aksi} oleh Waka Kurikulum.",
            route('jurnal.index')
        );
    }
}