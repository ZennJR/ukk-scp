<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JadwalPelajaran;
use App\Models\SystemConfiguration;
use App\Models\Journal;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Jurusan;
use App\Support\Notifier;
use App\Support\KunciPutaran;

class JadwalPelajaranController extends Controller
{
    public function index(Request $request)
    {
        // PENGAMAN ANTI-BOBOL: Pastikan user sudah login
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Silakan login terlebih dahulu!']);
        }

        $idUser = session('id_user');
        $idRole = session('id_role'); 
        $username = \App\Support\Identitas::dariSesi(); // NIP (tetap walau username diganti)

        $guruLogin = null;
        
        $guruLogin = Guru::where('nip', $username)->first();

        // Ambil data pendukung
        $tahunAjaran = DB::table('tahun_ajaran')->get();
        $kelas = DB::table('kelas')->get();
        $mapels = DB::table('mata_pelajaran')->get(); 
        $gurus = DB::table('guru')->get();
        $ruangans = DB::table('ruangan')->get();
        $config = SystemConfiguration::first();

        // "Daftar Jadwal Pelajaran Aktif" menampilkan jadwal SEMUA guru (bukan cuma
        // jadwal milik guru yang sedang login), supaya tiap guru bisa saling lihat
        // jadwal guru lain juga (mis. untuk cek bentrok ruangan/jam). $guruLogin
        // tetap dipakai di form input untuk auto-isi "Guru Pengampu".
        $jadwals = $this->urutkanJadwal(
            JadwalPelajaran::with(['tahunAjaran', 'kelas', 'mataPelajaran', 'guru', 'ruangan'])->get()
        );
        $jurnalList = Journal::with(['jadwal.kelas', 'jadwal.mataPelajaran'])->get();

        // Jadwal yang dibuat/dimiliki guru yang sedang login (tabel "Jadwal Saya"), sudah terurut hari & jam.
        $jadwalSaya = $guruLogin
            ? $jadwals->where('id_guru', $guruLogin->id_guru)->values()
            : collect();

        // Status kunci per putaran. Waka (role 4) tidak terikat kunci; guru hanya bisa memilih putaran yang terbuka.
        $statusPutaran = KunciPutaran::status();
        $isWaka = \App\Support\Peran::punya(4);
        $putaranBuka = $isWaka ? array_keys($statusPutaran) : KunciPutaran::terbuka();

        // Putaran terpilih di form: bawaan dari tombol dashboard (?putaran=...), lalu putaran aktif, lalu yang pertama terbuka
        $putaranDipilih = null;
        foreach ([old('cycle_type'), $request->query('putaran'), $config->active_cycle ?? null] as $kandidat) {
            if ($kandidat && in_array($kandidat, $putaranBuka, true)) {
                $putaranDipilih = $kandidat;
                break;
            }
        }
        $putaranDipilih = $putaranDipilih ?? ($putaranBuka[0] ?? null);

        return view('jadwal.index', compact('jadwals', 'jadwalSaya', 'jurnalList', 'config', 'tahunAjaran', 'kelas', 'mapels', 'gurus', 'ruangans', 'guruLogin', 'statusPutaran', 'putaranBuka', 'putaranDipilih', 'isWaka'));
    }

    /**
     * Uji anti-tabrakan. Sebuah jadwal dianggap bentrok jika pada HARI + PUTARAN yang sama
     * jamnya beririsan DAN salah satu dari ini sama: guru, kelas, atau ruangan.
     * Mengembalikan pesan error yang menyebut penyebab bentrok, atau null jika aman.
     */
    private function cariBentrok(Request $request, string $cycle, $kecualiId = null): ?string
    {
        $query = JadwalPelajaran::with(['guru', 'kelas', 'mataPelajaran', 'ruangan'])
            ->where('hari', $request->hari)
            ->where('cycle_type', $cycle)
            ->where(function ($q) use ($request) {
                $q->where('id_ruangan_default', $request->id_ruangan_default)
                  ->orWhere('id_kelas', $request->id_kelas)
                  ->orWhere('id_guru', $request->id_guru);
            })
            ->where('jam_mulai', '<', $request->jam_selesai)
            ->where('jam_selesai', '>', $request->jam_mulai);

        if ($kecualiId !== null) {
            $query->where('id_jadwal', '!=', $kecualiId);
        }

        $bentrok = $query->get();
        if ($bentrok->isEmpty()) {
            return null;
        }

        $pesan = [];
        foreach ($bentrok->take(3) as $b) {
            $sebab = [];
            if ($b->id_guru == $request->id_guru) {
                $sebab[] = 'guru ' . ($b->guru->nama_guru ?? $b->id_guru);
            }
            if ($b->id_kelas == $request->id_kelas) {
                $sebab[] = 'kelas ' . ($b->kelas->nama_kelas ?? $b->id_kelas);
            }
            if ($b->id_ruangan_default == $request->id_ruangan_default) {
                $sebab[] = 'ruangan ' . ($b->ruangan->nama_ruangan ?? $b->id_ruangan_default);
            }

            $pesan[] = implode(', ', $sebab) . ' sudah dipakai pada ' . $b->hari . ' '
                . substr($b->jam_mulai, 0, 5) . '-' . substr($b->jam_selesai, 0, 5)
                . ($b->jam_ke ? ' (' . $b->jam_ke . ')' : '')
                . ' untuk ' . ($b->kelas->nama_kelas ?? $b->id_kelas) . ' - ' . ($b->mataPelajaran->nama_mapel ?? $b->id_mapel);
        }

        return 'GAGAL! Jadwal bentrok: ' . implode('; ', $pesan) . '.';
    }

    /**
     * Urutkan jadwal: Senin -> Sabtu; di dalam tiap hari kelas X paling atas, lalu XI, lalu XII paling bawah;
     * untuk tingkat yang sama diurutkan jam mulai paling awal, lalu nama kelas (RPL 1, RPL 2, ...).
     */
    private function urutkanJadwal($jadwals)
    {
        $urutanHari = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6];
        $urutanTingkat = ['X' => 1, 'XI' => 2, 'XII' => 3];

        return $jadwals
            ->sort(function ($a, $b) use ($urutanHari, $urutanTingkat) {
                return ($urutanHari[$a->hari] ?? 9) <=> ($urutanHari[$b->hari] ?? 9)
                    ?: ($urutanTingkat[$a->kelas->tingkat ?? ''] ?? 9) <=> ($urutanTingkat[$b->kelas->tingkat ?? ''] ?? 9)
                    ?: strcmp((string) $a->jam_mulai, (string) $b->jam_mulai)
                    ?: strnatcasecmp((string) ($a->kelas->id_jurusan ?? ''), (string) ($b->kelas->id_jurusan ?? ''));
            })
            ->values();
    }

    public function store(Request $request)
    {
        if (!session()->has('id_user')) {
            return redirect()->route('login')->withErrors(['error' => 'Sesi habis, silakan login kembali!']);
        }

        // Putaran yang dipilih pengguna (Putaran 1 / Putaran 2)
        $putaran = (string) $request->input('cycle_type');
        if (!array_key_exists($putaran, KunciPutaran::DAFTAR)) {
            return redirect()->back()->withErrors(['error' => 'GAGAL! Pilih putaran jadwal terlebih dahulu.'])->withInput();
        }

        // Kunci berlaku per putaran dan mengikat guru; Waka Kurikulum (role 4) tidak terikat kunci
        if (!\App\Support\Peran::punya(4) && KunciPutaran::terkunci($putaran)) {
            return redirect()->back()->withErrors(['error' => "GAGAL! {$putaran} sedang berjalan sehingga otomatis terkunci. Silakan pilih putaran lain yang masih terbuka."])->withInput();
        }

        $username = \App\Support\Identitas::dariSesi();
        $guruLogin = Guru::where('nip', $username)->first();

        if ($guruLogin) {
            $request->merge([
                'id_guru' => $guruLogin->id_guru,
            ]);
        }

        $request->validate([
            'id_tahun_ajaran' => 'required',
            'id_kelas' => 'required',
            'id_mapel' => 'required',
            'id_guru' => 'required',
            'id_ruangan_default' => 'required',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);

        // Jam harus sesuai urutan jam pelajaran pada hari yang dipilih (config/jam_pelajaran.php)
        $slots = config('jam_pelajaran.' . $request->hari, []);
        $mulaiValid = collect($slots)->contains(fn ($s) => $s[1] . ':00' === $request->jam_mulai);
        $selesaiValid = collect($slots)->contains(fn ($s) => $s[2] . ':00' === $request->jam_selesai);
        if (!$mulaiValid || !$selesaiValid || $request->jam_selesai <= $request->jam_mulai) {
            return redirect()->back()->withErrors(['error' => 'GAGAL! Jam mulai/selesai tidak sesuai urutan jam pelajaran hari ' . $request->hari . '.'])->withInput();
        }

        $pesanBentrok = $this->cariBentrok($request, $putaran);
        if ($pesanBentrok) {
            return redirect()->back()->withErrors(['error' => $pesanBentrok])->withInput();
        }

        $jadwal = JadwalPelajaran::create(array_merge($request->all(), [
            'cycle_type' => $putaran,
        ]));

        $this->notifikasiJadwalUntukGuru($jadwal, 'Jadwal Baru Ditambahkan', 'ditambahkan');

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil ditambahkan!');
    }

    /**
     * Kirim notifikasi ke guru pengampu jadwal (dipakai saat tambah/ubah/hapus jadwal).
     */
    private function notifikasiJadwalUntukGuru(JadwalPelajaran $jadwal, string $judul, string $aksi): void
    {
        $guru = Guru::find($jadwal->id_guru);
        if (!$guru || !$guru->nip) {
            return;
        }

        $kelas = DB::table('kelas')->where('id_kelas', $jadwal->id_kelas)->first();
        $mapel = DB::table('mata_pelajaran')->where('id_mapel', $jadwal->id_mapel)->first();
        $namaKelas = $kelas ? trim(($kelas->tingkat ?? '') . ' ' . ($kelas->id_jurusan ?? '')) : $jadwal->id_kelas;
        $namaMapel = $mapel->nama_mapel ?? $jadwal->id_mapel;

        Notifier::untukNomorInduk(
            $guru->nip,
            'jadwal',
            $judul,
            "Jadwal {$namaMapel} kelas {$namaKelas} pada hari {$jadwal->hari} ({$jadwal->jam_mulai} - {$jadwal->jam_selesai}) telah {$aksi}.",
            route('jadwal.index')
        );
    }

    public function updateConfig(Request $request)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Hanya Waka Kurikulum yang dapat mengubah konfigurasi jadwal.']);
        }

        // Rute lama; kunci kini otomatis mengikuti putaran yang berjalan.
        $daftar = array_keys(KunciPutaran::DAFTAR);
        $aktif = in_array($request->active_cycle, $daftar, true)
            ? $request->active_cycle
            : KunciPutaran::aktif();

        KunciPutaran::setAktif($aktif, session('id_user'));

        return redirect()->back()->with('success', 'Konfigurasi berhasil diperbarui!');
    }

    // ==========================================
    // FITUR MANAJEMEN AKUN & ROLE (KHUSUS WAKA)
    // ==========================================

    /**
     * Daftar role yang bisa dipilih Waka saat membuat/mengedit akun.
     * Disamakan dengan angka id_role yang benar-benar dipakai sistem
     * (lihat AuthController::login & pengecekan session('id_role') di controller lain).
     */
    private function roleOptions()
    {
        return [
            4 => 'Waka / Admin',
            2 => 'Guru Pengampu',
            3 => 'Wali Kelas',
            7 => 'Guru BK',
            5 => 'Siswa',
        ];
    }

    public function indexAkun(Request $request)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Hanya Waka Kurikulum yang dapat mengelola akun.']);
        }

        $roleOptions = $this->roleOptions();

        $roleFilter = $request->query('role');
        $search = trim((string) $request->query('search', ''));

        \App\Support\Peran::pastikanTabel();

        // Daftar akun BARU dimuat setelah Waka memilih filter Role atau mengetik pencarian.
        // Tanpa filter, 1.800+ akun (beserta modal edit masing-masing) membuat halaman lag.
        $sudahFilter = ($roleFilter !== null && $roleFilter !== '') || $search !== '';
        $perHalaman  = 25;

        if ($sudahFilter) {
            $query = DB::table('users')->orderBy('id_user');

            if ($roleFilter !== null && $roleFilter !== '') {
                // Cocokkan peran utama ATAU peran tambahan (mis. Guru yang juga Wali Kelas)
                $query->where(function ($q) use ($roleFilter) {
                    $q->where('id_role', $roleFilter)
                      ->orWhereIn('id_user', DB::table('user_roles')->where('id_role', $roleFilter)->select('id_user'));
                });
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_lengkap', 'like', "%{$search}%")
                      ->orWhere('nomor_induk', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($perHalaman)->withQueryString();
            $halaman = collect($users->items());

            // Data pendukung hanya untuk akun yang tampil di halaman ini
            $nomorHalaman = $halaman->map(fn ($u) => $u->nomor_induk ?? $u->username)->filter()->values()->all();
            $kelasSiswa = DB::table('siswa')->whereIn('nisn', $nomorHalaman)->pluck('id_kelas', 'nisn');

            // Peran tambahan per akun: [id_user => [id_role => id_kelas|null]]
            $peranTambahan = [];
            foreach (DB::table('user_roles')->whereIn('id_user', $halaman->pluck('id_user')->all())->get() as $r) {
                $peranTambahan[$r->id_user][$r->id_role] = $r->id_kelas;
            }
        } else {
            $users = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perHalaman);
            $kelasSiswa = collect();
            $peranTambahan = [];
        }

        // Kelas master (untuk dropdown Jurusan -> Kelas di form)
        $kelasList = Kelas::orderBy('tingkat')->orderBy('id_jurusan')->get();
        $jurusanList = Jurusan::pluck('nama_jurusan', 'id_jurusan');
        $peranTambahanOpsi = \App\Support\Peran::BISA_TAMBAHAN;

        return view('jadwal.manajemen_akun', compact('users', 'roleOptions', 'roleFilter', 'search', 'sudahFilter', 'kelasList', 'jurusanList', 'kelasSiswa', 'peranTambahan', 'peranTambahanOpsi'));
    }

    public function storeAkun(Request $request)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        \App\Support\Identitas::pastikanKolom();

        // NISN maksimal 20 karakter (siswa.nisn), NIP maksimal 30 karakter (guru.nip)
        $max = $request->id_role == 5 ? 20 : 30;

        $request->validate([
            'nomor_induk'  => ['required', 'string', 'max:' . $max, 'regex:/^[A-Za-z0-9._-]+$/'],
            'nama_lengkap' => 'required|string|max:100',
            'password'     => 'required|min:8',
            'id_role'      => 'required',
            'id_kelas'     => 'required_if:id_role,5|nullable|exists:kelas,id_kelas',
            'peran_tambahan'   => 'nullable|array',
            'peran_tambahan.*' => 'integer|in:' . implode(',', array_keys(\App\Support\Peran::BISA_TAMBAHAN)),
            'id_kelas_wali'    => 'nullable|exists:kelas,id_kelas',
        ], [
            'nomor_induk.required' => 'NIP / NISN wajib diisi.',
            'nomor_induk.max'      => 'NIP / NISN maksimal :max karakter.',
            'nomor_induk.regex'    => 'NIP / NISN hanya boleh berisi huruf, angka, titik, garis bawah, dan strip (tanpa spasi).',
            'password.min'         => 'Password minimal :min karakter.',
            'id_kelas.required_if' => 'Jurusan dan kelas siswa wajib dipilih.',
            'id_kelas.exists'      => 'Kelas yang dipilih tidak ditemukan.',
            'id_kelas_wali.exists' => 'Kelas wali yang dipilih tidak ditemukan.',
        ]);

        $nomor = trim($request->nomor_induk);

        $dipakai = DB::table('users')
            ->where(fn ($q) => $q->where('nomor_induk', $nomor)->orWhere('username', $nomor))
            ->exists();
        if ($dipakai) {
            return back()->withErrors(['nomor_induk' => 'NIP / NISN tersebut sudah dipakai akun lain.'])->withInput();
        }

        // 1. Masukkan data ke tabel users. Kolom username hanya penyimpan internal (disamakan dengan NIP/NISN);
        //    login memakai NIP/NISN, nama tampilan memakai nama_lengkap.
        $idUserBaru = DB::table('users')->insertGetId([
            'username'      => $nomor,
            'nomor_induk'   => $nomor,
            'password_hash' => bcrypt($request->password),
            'nama_lengkap'  => $request->nama_lengkap,
            'id_role'       => $request->id_role,
        ], 'id_user');

        // Peran tambahan (mis. Guru + Wali Kelas) beserta kelas yang diampu
        \App\Support\Peran::simpanTambahan(
            (int) $idUserBaru,
            (int) $request->id_role,
            (array) $request->input('peran_tambahan', []),
            $request->input('id_kelas_wali')
        );

        // 2. Sinkronkan ke tabel 'guru' untuk SEMUA role staf (bukan Siswa),
        // yaitu Guru(2), Wali Kelas(3), Waka/Admin(4), Guru BK(7). Halaman Profil
        // mengecek NIP lewat tabel guru untuk semua role selain Siswa, jadi kalau
        // tidak disinkronkan di sini, NIP staf tsb akan muncul "Belum terdaftar
        // di data guru" walau sudah diisi saat pembuatan akun.
        // Role Siswa: buat/rapikan data induk siswa lengkap dengan kelasnya,
        // supaya siswa muncul di daftar presensi guru dan bisa mengajukan izin.
        if ((int) $request->id_role === 5) {
            $siswa = DB::table('siswa')->where('nisn', $nomor)->first();
            if ($siswa) {
                DB::table('siswa')->where('id_siswa', $siswa->id_siswa)->update([
                    'nama_siswa' => $request->nama_lengkap,
                    'id_kelas'   => $request->id_kelas,
                ]);
            } else {
                DB::table('siswa')->insert([
                    'nisn'         => $nomor,
                    'nama_siswa'   => $request->nama_lengkap,
                    'status_utama' => 'Aktif',
                    'id_kelas'     => $request->id_kelas,
                ]);
            }
        }

        if ((int) $request->id_role !== 5) {
            $cekGuru = DB::table('guru')->where('nip', $nomor)->exists();
            if (!$cekGuru) {
                DB::table('guru')->insert([
                    'nip'       => $nomor,
                    'nama_guru' => $request->nama_lengkap,
                ]);
            }
        }

        return redirect()->route('akun.index', ['search' => $nomor])->with('success', 'Akun baru berhasil dibuat dan data guru langsung terdaftar ke sistem!');
    }

    public function updateAkun(Request $request, $id)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $user = DB::table('users')->where('id_user', $id)->first();
        if (!$user) {
            return redirect()->route('akun.index')->withErrors(['error' => 'Akun tidak ditemukan.']);
        }

        \App\Support\Identitas::pastikanKolom();

        $isSiswa = $request->id_role == 5;
        $max = $isSiswa ? 20 : 30;

        $request->validate([
            'nomor_induk'  => ['required', 'string', 'max:' . $max, 'regex:/^[A-Za-z0-9._-]+$/'],
            'nama_lengkap' => 'required|string|max:100',
            'password'     => 'nullable|min:8',
            'id_role'      => 'required',
            'id_kelas'     => 'required_if:id_role,5|nullable|exists:kelas,id_kelas',
            'peran_tambahan'   => 'nullable|array',
            'peran_tambahan.*' => 'integer|in:' . implode(',', array_keys(\App\Support\Peran::BISA_TAMBAHAN)),
            'id_kelas_wali'    => 'nullable|exists:kelas,id_kelas',
        ], [
            'nomor_induk.required' => 'NIP / NISN wajib diisi.',
            'nomor_induk.max'      => 'NIP / NISN maksimal :max karakter.',
            'nomor_induk.regex'    => 'NIP / NISN hanya boleh berisi huruf, angka, titik, garis bawah, dan strip (tanpa spasi).',
            'password.min'         => 'Password minimal :min karakter.',
            'id_kelas.required_if' => 'Jurusan dan kelas siswa wajib dipilih.',
            'id_kelas.exists'      => 'Kelas yang dipilih tidak ditemukan.',
        ]);

        $nomorLama = \App\Support\Identitas::dariUser($user);
        $nomorBaru = trim($request->nomor_induk);
        $berubah   = $nomorBaru !== $nomorLama;

        // Data induk yang tertaut ke akun ini (dicari lewat NIP/NISN lama)
        $ownSiswa = $isSiswa ? DB::table('siswa')->where('nisn', $nomorLama)->first() : null;
        $ownGuru  = !$isSiswa ? DB::table('guru')->where('nip', $nomorLama)->first() : null;

        if ($berubah) {
            // NIP/NISN baru tidak boleh dipakai akun lain
            $dipakai = DB::table('users')->where('id_user', '!=', $id)
                ->where(fn ($q) => $q->where('nomor_induk', $nomorBaru)->orWhere('username', $nomorBaru))
                ->exists();
            if ($dipakai) {
                return back()->withErrors(['nomor_induk' => 'NIP / NISN tersebut sudah dipakai akun lain.'])->withInput();
            }

            // ...dan tidak boleh bentrok dengan data guru/siswa lain saat disinkronkan
            $bentrokSiswa = $ownSiswa && DB::table('siswa')->where('nisn', $nomorBaru)->where('id_siswa', '!=', $ownSiswa->id_siswa)->exists();
            $bentrokGuru  = $ownGuru && DB::table('guru')->where('nip', $nomorBaru)->where('id_guru', '!=', $ownGuru->id_guru)->exists();
            if ($bentrokSiswa || $bentrokGuru) {
                return back()->withErrors(['nomor_induk' => 'NIP / NISN tersebut sudah terdaftar pada data guru/siswa lain.'])->withInput();
            }
        }

        DB::transaction(function () use ($request, $id, $nomorBaru, $isSiswa, $ownSiswa, $ownGuru) {
            $data = [
                'username'     => $nomorBaru, // username hanya penyimpan internal, selalu sama dengan NIP/NISN
                'nomor_induk'  => $nomorBaru,
                'nama_lengkap' => $request->nama_lengkap,
                'id_role'      => $request->id_role,
            ];

            // Password hanya diganti kalau diisi (kosong = tetap pakai password lama)
            if ($request->filled('password')) {
                $data['password_hash'] = bcrypt($request->password);
            }

            DB::table('users')->where('id_user', $id)->update($data);

            // Sinkronkan NIP/NISN dan nama ke data induk
            if ($isSiswa) {
                if ($ownSiswa) {
                    DB::table('siswa')->where('id_siswa', $ownSiswa->id_siswa)->update([
                        'nisn'       => $nomorBaru,
                        'nama_siswa' => $request->nama_lengkap,
                        'id_kelas'   => $request->id_kelas,
                    ]);
                } elseif (!DB::table('siswa')->where('nisn', $nomorBaru)->exists()) {
                    // Akun siswa lama yang belum punya data induk siswa: buat sekarang
                    DB::table('siswa')->insert([
                        'nisn'         => $nomorBaru,
                        'nama_siswa'   => $request->nama_lengkap,
                        'status_utama' => 'Aktif',
                        'id_kelas'     => $request->id_kelas,
                    ]);
                } else {
                    DB::table('siswa')->where('nisn', $nomorBaru)->update([
                        'nama_siswa' => $request->nama_lengkap,
                        'id_kelas'   => $request->id_kelas,
                    ]);
                }
            } elseif ($ownGuru) {
                DB::table('guru')->where('id_guru', $ownGuru->id_guru)->update([
                    'nip'       => $nomorBaru,
                    'nama_guru' => $request->nama_lengkap,
                ]);
            } elseif (!$isSiswa && !DB::table('guru')->where('nip', $nomorBaru)->exists()) {
                // Role staf (Guru/Wali Kelas/Waka/BK) tapi belum punya data guru: buat baru
                DB::table('guru')->insert([
                    'nip'       => $nomorBaru,
                    'nama_guru' => $request->nama_lengkap,
                ]);
            }
        });

        // Peran tambahan (mis. Guru yang diangkat jadi Wali Kelas) + kelas yang diampu
        \App\Support\Peran::simpanTambahan(
            (int) $id,
            (int) $request->id_role,
            (array) $request->input('peran_tambahan', []),
            $request->input('id_kelas_wali')
        );

        // Kalau Waka mengubah akunnya sendiri, segarkan sesi
        if ((int) $id === (int) session('id_user')) {
            session([
                'username'     => $nomorBaru,
                'nomor_induk'  => $nomorBaru,
                'nama_lengkap' => $request->nama_lengkap,
                'id_role'      => $request->id_role,
            ]);
        }

        return redirect()->back()->with('success', 'Akun berhasil diperbarui!');
    }

    public function destroyAkun($id)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        // Cegah Waka menghapus akunnya sendiri yang sedang aktif login
        if ((int) $id === (int) session('id_user')) {
            return redirect()->route('akun.index')->withErrors(['error' => 'Tidak bisa menghapus akun yang sedang Anda gunakan untuk login.']);
        }

        $user = DB::table('users')->where('id_user', $id)->first();
        if (!$user) {
            return redirect()->route('akun.index')->withErrors(['error' => 'Akun tidak ditemukan.']);
        }

        DB::table('users')->where('id_user', $id)->delete();
        if (\App\Support\Peran::tabelAda()) {
            DB::table('user_roles')->where('id_user', $id)->delete();
        }

        return redirect()->back()->with('success', 'Akun "' . $user->nama_lengkap . '" berhasil dihapus!');
    }

    // ==========================================
    // FITUR REKAP JADWAL SELURUH GURU (KHUSUS WAKA)
    // ==========================================

    /**
     * Rekap jadwal untuk Waka Kurikulum.
     * Bawaan: hanya jadwal HARI INI. Jadwal lain tampil lewat filter (hari, putaran, kelas, guru/mapel).
     * Pemfilteran dilakukan di server supaya halaman tetap ringan walau jadwalnya ratusan.
     */
    public function rekapJadwalWaka(Request $request)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Khusus Waka Kurikulum.']);
        }

        $namaHari = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $hariIni = $namaHari[now()->dayOfWeekIso];
        $daftarPilihanHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $hariPilihan = (string) $request->query('hari', $hariIni);
        if ($hariPilihan !== 'Semua' && !in_array($hariPilihan, $daftarPilihanHari, true)) {
            $hariPilihan = $hariIni;
        }

        $putaranPilihan = (string) $request->query('putaran', '');
        if (!array_key_exists($putaranPilihan, KunciPutaran::DAFTAR)) {
            $putaranPilihan = '';
        }

        $kelasCari = trim(preg_replace('/\s+/', ' ', (string) $request->query('kelas', '')));
        $cari = trim((string) $request->query('q', ''));

        $query = JadwalPelajaran::with(['tahunAjaran', 'kelas', 'mataPelajaran', 'guru', 'ruangan']);

        if ($hariPilihan !== 'Semua') {
            $query->where('hari', $hariPilihan);
        }

        if ($putaranPilihan !== '') {
            $query->where('cycle_type', $putaranPilihan);
        }

        if ($kelasCari !== '') {
            $adaNamaKelas = \Illuminate\Support\Facades\Schema::hasColumn('kelas', 'nama_kelas');
            $idKelas = DB::table('kelas')->where(function ($q) use ($kelasCari, $adaNamaKelas) {
                $q->where('id_kelas', $kelasCari)
                  ->orWhereRaw("CONCAT(tingkat, ' ', COALESCE(id_jurusan, '')) LIKE ?", ["%{$kelasCari}%"])
                  ->orWhere('id_jurusan', 'like', "%{$kelasCari}%");
                if ($adaNamaKelas) {
                    $q->orWhere('nama_kelas', 'like', "%{$kelasCari}%");
                }
            })->pluck('id_kelas');
            $query->whereIn('id_kelas', $idKelas);
        }

        if ($cari !== '') {
            $idGuru = DB::table('guru')->where('nama_guru', 'like', "%{$cari}%")->pluck('id_guru');
            $idMapel = DB::table('mata_pelajaran')
                ->where('nama_mapel', 'like', "%{$cari}%")
                ->orWhere('id_mapel', $cari)
                ->pluck('id_mapel');
            $query->where(function ($w) use ($idGuru, $idMapel) {
                $w->whereIn('id_guru', $idGuru)->orWhereIn('id_mapel', $idMapel);
            });
        }

        $jadwals = $this->urutkanJadwal($query->get());

        // Data untuk dropdown di modal edit (dimuat sekali saja, bukan per jadwal)
        $tahunAjaran = DB::table('tahun_ajaran')->get();
        $kelas       = DB::table('kelas')->get();
        $mapels      = DB::table('mata_pelajaran')->get();
        $gurus       = DB::table('guru')->get();
        $ruangans    = DB::table('ruangan')->get();

        return view('jadwal.rekap_waka', compact(
            'jadwals', 'tahunAjaran', 'kelas', 'mapels', 'gurus', 'ruangans',
            'hariIni', 'hariPilihan', 'daftarPilihanHari', 'putaranPilihan', 'kelasCari', 'cari'
        ));
    }

    /** Setelah edit/hapus, kembali ke halaman rekap lengkap dengan filter yang sedang dipakai. */
    private function kembaliKeRekap(): string
    {
        $sebelumnya = url()->previous();
        return str_contains($sebelumnya, '/waka/jadwal/rekap') ? $sebelumnya : route('waka.jadwal.rekap');
    }

    public function updateJadwalWaka(Request $request, $id)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $jadwal = JadwalPelajaran::find($id);
        if (!$jadwal) {
            return redirect()->route('waka.jadwal.rekap')->withErrors(['error' => 'Jadwal tidak ditemukan.']);
        }

        $request->validate([
            'id_tahun_ajaran'     => 'required',
            'id_kelas'            => 'required',
            'id_mapel'            => 'required',
            'id_guru'             => 'required',
            'id_ruangan_default'  => 'required',
            'hari'                => 'required',
            'jam_mulai'           => 'required',
            'jam_selesai'         => 'required',
            'cycle_type'          => 'required',
        ]);

        // Uji anti-tabrakan, kecuali terhadap dirinya sendiri
        $pesanBentrok = $this->cariBentrok($request, $request->cycle_type, $id);
        if ($pesanBentrok) {
            return redirect()->back()->withErrors(['error' => $pesanBentrok])->withInput();
        }

        $jadwalGuruLamaNip = optional(Guru::find($jadwal->id_guru))->nip;

        $jadwal->update($request->only([
            'id_tahun_ajaran', 'id_kelas', 'id_mapel', 'id_guru',
            'id_ruangan_default', 'hari', 'jam_mulai', 'jam_selesai', 'cycle_type',
        ]));

        $jadwal->refresh();
        $this->notifikasiJadwalUntukGuru($jadwal, 'Jadwal Diperbarui', 'diperbarui');

        // Kalau guru pengampunya diganti, beri tahu juga guru yang lama.
        $guruBaru = Guru::find($jadwal->id_guru);
        if ($jadwalGuruLamaNip && (!$guruBaru || $guruBaru->nip !== $jadwalGuruLamaNip)) {
            Notifier::untukNomorInduk(
                $jadwalGuruLamaNip,
                'jadwal',
                'Jadwal Dialihkan',
                "Jadwal yang sebelumnya Anda ampu kini telah dialihkan ke guru lain.",
                route('jadwal.index')
            );
        }

        return redirect($this->kembaliKeRekap())->with('success', 'Jadwal pelajaran berhasil diperbarui!');
    }

    public function destroyJadwalWaka($id)
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak!']);
        }

        $jadwal = JadwalPelajaran::find($id);
        if (!$jadwal) {
            return redirect()->route('waka.jadwal.rekap')->withErrors(['error' => 'Jadwal tidak ditemukan.']);
        }

        $this->notifikasiJadwalUntukGuru($jadwal, 'Jadwal Dihapus', 'dihapus');

        $jadwal->delete();

        return redirect($this->kembaliKeRekap())->with('success', 'Jadwal pelajaran berhasil dihapus!');
    }
}