<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Support\Identitas;
use App\Support\Notifier;
use App\Support\Peran;
use App\Support\TukarRuangan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TukarRuanganController extends Controller
{
    private const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    private function guruLogin(): ?Guru
    {
        $nip = Identitas::dariSesi();
        return $nip ? Guru::where('nip', $nip)->first() : null;
    }

    /** Tanggal yang boleh dipilih: hari ini s.d. 13 hari ke depan, hanya Senin-Jumat (tanpa Sabtu & Minggu). */
    private function daftarTanggal(): array
    {
        $hasil = [];
        for ($i = 0; $i < 14; $i++) {
            $t = now()->startOfDay()->addDays($i);
            if ($t->dayOfWeekIso >= 6) {
                continue;
            }
            $hasil[$t->format('Y-m-d')] = self::HARI[$t->dayOfWeekIso] . ', ' . $t->format('d-m-Y') . ($i === 0 ? ' (hari ini)' : '');
        }
        return $hasil;
    }

    private function bukanGuru()
    {
        return !Peran::punya(2, 7)
            ? redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Guru.'])
            : null;
    }

    /** Kirim notifikasi ke guru berdasarkan id_guru (lewat NIP). */
    private function beritahuGuru(int $idGuru, string $judul, string $pesan): void
    {
        $g = Guru::find($idGuru);
        if ($g && $g->nip) {
            Notifier::untukNomorInduk($g->nip, 'tukar_ruangan', $judul, $pesan, route('guru.tukar-ruangan'));
        }
    }

    // ------------------------------------------------------------------ GURU

    public function index(Request $request)
    {
        if ($tolak = $this->bukanGuru()) {
            return $tolak;
        }

        $guru = $this->guruLogin();
        if (!$guru) {
            return redirect()->route('guru.dashboard')->withErrors(['error' => 'Data guru tidak ditemukan untuk akun ini.']);
        }

        $dasar = [
            'tabelAda' => TukarRuangan::tabelAda(), 'guru' => $guru, 'pilihanTanggal' => [], 'tanggal' => null,
            'jadwalSaya' => collect(), 'dipilih' => null, 'kandidat' => collect(), 'masuk' => collect(), 'keluar' => collect(),
        ];
        if (!$dasar['tabelAda']) {
            return view('guru.tukar_ruangan', $dasar);
        }

        TukarRuangan::kedaluwarsakan();

        $pilihanTanggal = $this->daftarTanggal();
        $tanggal = $request->query('tanggal');
        if (!isset($pilihanTanggal[$tanggal])) {
            $tanggal = array_key_first($pilihanTanggal);
        }

        $semua = TukarRuangan::jadwalPadaTanggal(Carbon::parse($tanggal));
        $terpakai = TukarRuangan::idJadwalTerpakai($tanggal);
        $layak = function ($j) use ($terpakai, $tanggal) {
            return !in_array((int) $j->id_jadwal, $terpakai, true) && !TukarRuangan::mulaiTerlewat($tanggal, $j->jam_mulai);
        };

        $jadwalSaya = $semua->filter(fn ($j) => (int) $j->id_guru === (int) $guru->id_guru)->filter($layak)->sortBy('jam_mulai')->values();
        $dipilih = $jadwalSaya->firstWhere('id_jadwal', (int) $request->query('jadwal'));

        $kandidat = collect();
        if ($dipilih) {
            $kandidat = $semua->filter(function ($j) use ($guru, $dipilih, $layak) {
                return (int) $j->id_guru !== (int) $guru->id_guru
                    && $j->id_ruangan_default !== $dipilih->id_ruangan_default
                    && TukarRuangan::bentrokWaktu($dipilih, $j)
                    && $layak($j);
            })->sortBy('jam_mulai')->values();
        }

        $tandai = function ($baris) {
            $baris->bisa_batal = in_array($baris->status, TukarRuangan::AKTIF, true)
                && !TukarRuangan::mulaiTerlewat($baris->tanggal, $baris->jam_mulai);
            return $baris;
        };

        $masuk = DB::table('tukar_ruangan as t')
            ->leftJoin('guru as g', 'g.id_guru', '=', 't.id_guru_pemohon')
            ->where('t.id_guru_tujuan', $guru->id_guru)
            ->orderByDesc('t.id')->limit(30)
            ->get(['t.*', 'g.nama_guru as nama_lawan'])->map($tandai);

        $keluar = DB::table('tukar_ruangan as t')
            ->leftJoin('guru as g', 'g.id_guru', '=', 't.id_guru_tujuan')
            ->where('t.id_guru_pemohon', $guru->id_guru)
            ->orderByDesc('t.id')->limit(30)
            ->get(['t.*', 'g.nama_guru as nama_lawan'])->map($tandai);

        return view('guru.tukar_ruangan', array_merge($dasar, compact('pilihanTanggal', 'tanggal', 'jadwalSaya', 'dipilih', 'kandidat', 'masuk', 'keluar')));
    }

    public function store(Request $request)
    {
        if ($tolak = $this->bukanGuru()) {
            return $tolak;
        }
        $guru = $this->guruLogin();
        if (!$guru || !TukarRuangan::tabelAda()) {
            return back()->withErrors(['error' => 'Fitur tukar ruangan belum siap. Hubungi admin.']);
        }

        $request->validate([
            'tanggal'          => 'required|date_format:Y-m-d',
            'id_jadwal_saya'   => 'required|integer',
            'id_jadwal_tujuan' => 'required|integer',
            'alasan'           => 'required|string|max:255',
        ], [
            'id_jadwal_saya.required'   => 'Pilih jadwal Anda yang ruangannya ingin ditukar.',
            'id_jadwal_tujuan.required' => 'Pilih jadwal guru lain yang ingin ditukar ruangannya.',
            'alasan.required'           => 'Alasan tukar ruangan wajib diisi.',
            'alasan.max'                => 'Alasan maksimal 255 karakter.',
        ]);

        $tanggal = $request->tanggal;
        if (!isset($this->daftarTanggal()[$tanggal])) {
            return back()->withErrors(['error' => 'Tanggal tidak valid. Pilih hari ini sampai 13 hari ke depan pada hari Senin-Jumat.'])->withInput();
        }

        $semua = TukarRuangan::jadwalPadaTanggal(Carbon::parse($tanggal));
        $saya = $semua->firstWhere('id_jadwal', (int) $request->id_jadwal_saya);
        $tujuan = $semua->firstWhere('id_jadwal', (int) $request->id_jadwal_tujuan);

        if (!$saya || (int) $saya->id_guru !== (int) $guru->id_guru) {
            return back()->withErrors(['error' => 'Jadwal Anda tidak valid untuk tanggal tersebut.'])->withInput();
        }
        if (!$tujuan || (int) $tujuan->id_guru === (int) $guru->id_guru) {
            return back()->withErrors(['error' => 'Jadwal tujuan harus milik guru lain pada tanggal yang sama.'])->withInput();
        }
        if ($saya->id_ruangan_default === $tujuan->id_ruangan_default) {
            return back()->withErrors(['error' => 'Kedua jadwal sudah memakai ruangan yang sama.'])->withInput();
        }
        if (!TukarRuangan::bentrokWaktu($saya, $tujuan)) {
            return back()->withErrors(['error' => 'Jam kedua jadwal tidak bersinggungan, sehingga ruangan tidak bisa ditukar.'])->withInput();
        }

        $jamMulai = min(substr($saya->jam_mulai, 0, 8), substr($tujuan->jam_mulai, 0, 8));
        if (TukarRuangan::mulaiTerlewat($tanggal, $jamMulai)) {
            return back()->withErrors(['error' => 'Kelas sudah dimulai, tukar ruangan tidak bisa diajukan lagi.'])->withInput();
        }

        $terpakai = TukarRuangan::idJadwalTerpakai($tanggal);
        if (in_array((int) $saya->id_jadwal, $terpakai, true) || in_array((int) $tujuan->id_jadwal, $terpakai, true)) {
            return back()->withErrors(['error' => 'Salah satu jadwal sudah terlibat pertukaran ruangan lain pada tanggal itu.'])->withInput();
        }

        DB::table('tukar_ruangan')->insert([
            'tanggal'              => $tanggal,
            'id_jadwal_pemohon'    => $saya->id_jadwal,
            'id_jadwal_tujuan'     => $tujuan->id_jadwal,
            'id_guru_pemohon'      => $guru->id_guru,
            'id_guru_tujuan'       => $tujuan->id_guru,
            'id_ruangan_pemohon'   => $saya->id_ruangan_default,
            'id_ruangan_tujuan'    => $tujuan->id_ruangan_default,
            'ruangan_pemohon_nama' => $saya->ruangan_label,
            'ruangan_tujuan_nama'  => $tujuan->ruangan_label,
            'info_pemohon'         => TukarRuangan::infoJadwal($saya),
            'info_tujuan'          => TukarRuangan::infoJadwal($tujuan),
            'jam_mulai'            => $jamMulai,
            'alasan'               => $request->alasan,
            'status'               => 'Menunggu',
            'created_at'           => now(),
        ]);

        $this->beritahuGuru(
            (int) $tujuan->id_guru,
            'Permintaan Tukar Ruangan',
            "{$guru->nama_guru} mengajak tukar ruangan pada " . Carbon::parse($tanggal)->format('d-m-Y')
            . ": ruang {$saya->ruangan_label} \u{2194} {$tujuan->ruangan_label}. Alasan: {$request->alasan}"
        );

        return redirect()->route('guru.tukar-ruangan')->with('success', 'Permintaan tukar ruangan dikirim. Menunggu persetujuan ' . ($tujuan->guru->nama_guru ?? 'guru tujuan') . '.');
    }

    /** Baris pertukaran milik guru (sebagai pemohon atau tujuan). */
    private function barisMilik(Guru $guru, $id, string $sebagai)
    {
        $kolom = $sebagai === 'tujuan' ? 'id_guru_tujuan' : 'id_guru_pemohon';
        return DB::table('tukar_ruangan')->where('id', $id)->where($kolom, $guru->id_guru)->first();
    }

    public function setuju($id)
    {
        if ($tolak = $this->bukanGuru()) {
            return $tolak;
        }
        $guru = $this->guruLogin();
        $r = $guru ? $this->barisMilik($guru, $id, 'tujuan') : null;
        if (!$r) {
            return back()->withErrors(['error' => 'Permintaan tidak ditemukan.']);
        }
        if ($r->status !== 'Menunggu') {
            return back()->withErrors(['error' => 'Permintaan ini sudah diproses atau kedaluwarsa.']);
        }
        if (TukarRuangan::mulaiTerlewat($r->tanggal, $r->jam_mulai)) {
            DB::table('tukar_ruangan')->where('id', $r->id)->update(['status' => 'Kedaluwarsa', 'responded_at' => now()]);
            return back()->withErrors(['error' => 'Kelas sudah dimulai, permintaan kedaluwarsa.']);
        }

        // Pastikan data jadwal belum berubah & tidak bentrok dengan pertukaran lain
        $a = JadwalPelajaran::find($r->id_jadwal_pemohon);
        $b = JadwalPelajaran::find($r->id_jadwal_tujuan);
        if (!$a || !$b || $a->id_ruangan_default !== $r->id_ruangan_pemohon || $b->id_ruangan_default !== $r->id_ruangan_tujuan) {
            DB::table('tukar_ruangan')->where('id', $r->id)->update(['status' => 'Dibatalkan', 'responded_at' => now(), 'alasan_tolak' => 'Jadwal/ruangan berubah sebelum disetujui.']);
            return back()->withErrors(['error' => 'Jadwal atau ruangan sudah berubah, permintaan dibatalkan otomatis.']);
        }
        $terpakai = TukarRuangan::idJadwalTerpakai($r->tanggal, (int) $r->id);
        if (in_array((int) $r->id_jadwal_pemohon, $terpakai, true) || in_array((int) $r->id_jadwal_tujuan, $terpakai, true)) {
            return back()->withErrors(['error' => 'Salah satu jadwal sudah terlibat pertukaran ruangan lain.']);
        }

        $berhasil = DB::table('tukar_ruangan')->where('id', $r->id)->where('status', 'Menunggu')
            ->update(['status' => 'Disetujui', 'responded_at' => now()]);
        if (!$berhasil) {
            return back()->withErrors(['error' => 'Permintaan baru saja diproses.']);
        }

        $tgl = Carbon::parse($r->tanggal)->format('d-m-Y');
        $this->beritahuGuru((int) $r->id_guru_pemohon, 'Tukar Ruangan Disetujui',
            "{$guru->nama_guru} menyetujui tukar ruangan {$tgl}. Anda memakai ruang {$r->ruangan_tujuan_nama}.");
        Notifier::kirimKeRole(4, 'tukar_ruangan', 'Tukar Ruangan Disetujui',
            "Tukar ruangan {$tgl}: {$r->info_pemohon} (ke {$r->ruangan_tujuan_nama}) \u{2194} {$r->info_tujuan} (ke {$r->ruangan_pemohon_nama}).",
            route('waka.tukar-ruangan.rekap'));

        return back()->with('success', "Tukar ruangan disetujui. Pada {$tgl}, Anda memakai ruang {$r->ruangan_pemohon_nama}.");
    }

    public function tolak(Request $request, $id)
    {
        if ($tolak = $this->bukanGuru()) {
            return $tolak;
        }
        $request->validate(['alasan_tolak' => 'nullable|string|max:255']);

        $guru = $this->guruLogin();
        $r = $guru ? $this->barisMilik($guru, $id, 'tujuan') : null;
        if (!$r || $r->status !== 'Menunggu') {
            return back()->withErrors(['error' => 'Permintaan tidak ditemukan atau sudah diproses.']);
        }

        DB::table('tukar_ruangan')->where('id', $r->id)->where('status', 'Menunggu')
            ->update(['status' => 'Ditolak', 'responded_at' => now(), 'alasan_tolak' => $request->alasan_tolak ?: null]);

        $this->beritahuGuru((int) $r->id_guru_pemohon, 'Tukar Ruangan Ditolak',
            "{$guru->nama_guru} menolak tukar ruangan " . Carbon::parse($r->tanggal)->format('d-m-Y')
            . ($request->alasan_tolak ? ". Alasan: {$request->alasan_tolak}" : '.'));

        return back()->with('success', 'Permintaan tukar ruangan ditolak.');
    }

    /** Pemohon membatalkan (selama Menunggu, atau Disetujui tapi kelas belum dimulai). */
    public function batal($id)
    {
        if ($tolak = $this->bukanGuru()) {
            return $tolak;
        }
        $guru = $this->guruLogin();
        $r = $guru ? $this->barisMilik($guru, $id, 'pemohon') : null;
        if (!$r || !in_array($r->status, TukarRuangan::AKTIF, true)) {
            return back()->withErrors(['error' => 'Permintaan tidak ditemukan atau tidak bisa dibatalkan.']);
        }
        if (TukarRuangan::mulaiTerlewat($r->tanggal, $r->jam_mulai)) {
            return back()->withErrors(['error' => 'Kelas sudah dimulai, pertukaran tidak bisa dibatalkan lagi.']);
        }

        DB::table('tukar_ruangan')->where('id', $r->id)->update(['status' => 'Dibatalkan', 'responded_at' => now()]);

        $this->beritahuGuru((int) $r->id_guru_tujuan, 'Tukar Ruangan Dibatalkan',
            "{$guru->nama_guru} membatalkan tukar ruangan " . Carbon::parse($r->tanggal)->format('d-m-Y') . '. Ruangan kembali seperti jadwal semula.');

        return back()->with('success', 'Permintaan tukar ruangan dibatalkan.');
    }

    // ------------------------------------------------------------------ WAKA

    public function rekapWaka(Request $request)
    {
        if (!Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Waka Kurikulum.']);
        }

        $tabelAda = TukarRuangan::tabelAda();
        if (!$tabelAda) {
            return view('waka.tukar_ruangan_rekap', ['tabelAda' => false, 'list' => collect(), 'stat' => [], 'status' => 'semua']);
        }

        TukarRuangan::kedaluwarsakan();

        $status = $request->query('status', 'semua');
        $query = DB::table('tukar_ruangan as t')
            ->leftJoin('guru as gp', 'gp.id_guru', '=', 't.id_guru_pemohon')
            ->leftJoin('guru as gt', 'gt.id_guru', '=', 't.id_guru_tujuan')
            ->select('t.*', 'gp.nama_guru as nama_pemohon', 'gt.nama_guru as nama_tujuan');

        if (in_array($status, ['Menunggu', 'Disetujui', 'Ditolak', 'Dibatalkan', 'Kedaluwarsa'], true)) {
            $query->where('t.status', $status);
        } else {
            $status = 'semua';
        }

        $list = $query->orderByDesc('t.tanggal')->orderByDesc('t.id')->limit(300)->get();
        $hitung = DB::table('tukar_ruangan')->select('status', DB::raw('COUNT(*) as n'))->groupBy('status')->pluck('n', 'status');

        $stat = [
            'total'     => (int) $hitung->sum(),
            'menunggu'  => (int) ($hitung['Menunggu'] ?? 0),
            'disetujui' => (int) ($hitung['Disetujui'] ?? 0),
            'ditolak'   => (int) (($hitung['Ditolak'] ?? 0) + ($hitung['Dibatalkan'] ?? 0) + ($hitung['Kedaluwarsa'] ?? 0)),
        ];

        return view('waka.tukar_ruangan_rekap', compact('tabelAda', 'list', 'stat', 'status'));
    }
}
