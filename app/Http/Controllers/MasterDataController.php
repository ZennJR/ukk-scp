<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kelola data induk: Ruangan, Kelas, dan Mata Pelajaran.
 * Khusus Waka Kurikulum (id_role == 4). Data ini dipakai saat menyusun jadwal pelajaran.
 */
class MasterDataController extends Controller
{
    private function tolakJikaBukanWaka()
    {
        if (!\App\Support\Peran::punya(4)) {
            return redirect()->route('login')->withErrors(['error' => 'Akses ditolak! Halaman ini khusus untuk Waka Kurikulum.']);
        }
        return null;
    }

    public function ruangan()
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $ruangan = Ruangan::orderBy('id_ruangan')->get();

        return view('waka.master-data.ruangan', compact('ruangan'));
    }

    public function kelas()
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $kelas = Kelas::orderBy('tingkat')->orderBy('id_jurusan')->get();
        $jurusanOptions = Jurusan::orderBy('nama_jurusan')->pluck('nama_jurusan', 'id_jurusan');

        return view('waka.master-data.kelas', compact('kelas', 'jurusanOptions'));
    }

    public function mapel()
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $mapel = MataPelajaran::orderBy('nama_mapel')->get();

        return view('waka.master-data.mapel', compact('mapel'));
    }

    // ================= RUANGAN =================

    public function storeRuangan(Request $request)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $request->validate([
            'id_ruangan'   => 'required|string|max:20|unique:ruangan,id_ruangan',
            'nama_ruangan' => 'required|string|max:50',
            'jenis'        => 'nullable|string|max:20',
        ], [], [
            'id_ruangan' => 'Kode Ruangan', 'nama_ruangan' => 'Nama Ruangan', 'jenis' => 'Jenis',
        ]);

        Ruangan::create([
            'id_ruangan'   => trim($request->id_ruangan),
            'nama_ruangan' => trim($request->nama_ruangan),
            'jenis'        => $request->jenis ?: null,
        ]);

        return redirect()->route('waka.master-data.ruangan')->with('success', 'Ruangan "' . $request->nama_ruangan . '" berhasil ditambahkan.');
    }

    public function updateRuangan(Request $request, string $id)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $ruangan = Ruangan::where('id_ruangan', $id)->first();
        if (!$ruangan) {
            return redirect()->route('waka.master-data.ruangan')->withErrors(['error' => 'Ruangan tidak ditemukan.']);
        }

        $request->validate([
            'nama_ruangan' => 'required|string|max:50',
            'jenis'        => 'nullable|string|max:20',
        ]);

        $ruangan->update([
            'nama_ruangan' => trim($request->nama_ruangan),
            'jenis'        => $request->jenis ?: null,
        ]);

        return redirect()->route('waka.master-data.ruangan')->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroyRuangan(string $id)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $ruangan = Ruangan::where('id_ruangan', $id)->first();
        if (!$ruangan) {
            return redirect()->route('waka.master-data.ruangan')->withErrors(['error' => 'Ruangan tidak ditemukan.']);
        }

        $dipakai = DB::table('jadwal_pelajaran')->where('id_ruangan_default', $id)->exists();
        if ($dipakai) {
            return redirect()->route('waka.master-data.ruangan')->withErrors([
                'error' => 'Ruangan "' . $ruangan->nama_ruangan . '" tidak dapat dihapus karena masih dipakai pada jadwal pelajaran. Hapus atau ubah jadwal tersebut terlebih dahulu.',
            ]);
        }

        $ruangan->delete();

        return redirect()->route('waka.master-data.ruangan')->with('success', 'Ruangan "' . $ruangan->nama_ruangan . '" berhasil dihapus.');
    }

    // ================= KELAS =================

    public function storeKelas(Request $request)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $request->validate([
            'id_kelas'   => 'required|string|max:20|unique:kelas,id_kelas',
            'tingkat'    => 'required|in:X,XI,XII',
            'id_jurusan' => 'nullable|string|exists:jurusan,id_jurusan',
        ], [], [
            'id_kelas' => 'Kode Kelas', 'tingkat' => 'Tingkat', 'id_jurusan' => 'Jurusan',
        ]);

        Kelas::create([
            'id_kelas'   => trim($request->id_kelas),
            'tingkat'    => $request->tingkat,
            'id_jurusan' => $request->id_jurusan ?: null,
        ]);

        return redirect()->route('waka.master-data.kelas')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function updateKelas(Request $request, string $id)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $kelas = Kelas::where('id_kelas', $id)->first();
        if (!$kelas) {
            return redirect()->route('waka.master-data.kelas')->withErrors(['error' => 'Kelas tidak ditemukan.']);
        }

        $request->validate([
            'tingkat'    => 'required|in:X,XI,XII',
            'id_jurusan' => 'nullable|string|exists:jurusan,id_jurusan',
        ]);

        $kelas->update([
            'tingkat'    => $request->tingkat,
            'id_jurusan' => $request->id_jurusan ?: null,
        ]);

        return redirect()->route('waka.master-data.kelas')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroyKelas(string $id)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $kelas = Kelas::where('id_kelas', $id)->first();
        if (!$kelas) {
            return redirect()->route('waka.master-data.kelas')->withErrors(['error' => 'Kelas tidak ditemukan.']);
        }

        $jumlahJadwal = DB::table('jadwal_pelajaran')->where('id_kelas', $id)->count();
        if ($jumlahJadwal > 0) {
            return redirect()->route('waka.master-data.kelas')->withErrors([
                'error' => 'Kelas "' . $kelas->nama_kelas . '" tidak dapat dihapus: masih dipakai pada ' . $jumlahJadwal . ' jadwal pelajaran. Menghapusnya akan ikut menghapus jadwal-jadwal tersebut, jadi hapus atau pindahkan jadwalnya terlebih dahulu.',
            ]);
        }

        $nama = $kelas->nama_kelas;
        $kelas->delete();

        return redirect()->route('waka.master-data.kelas')->with('success', 'Kelas "' . $nama . '" berhasil dihapus.');
    }

    // ================= MATA PELAJARAN =================

    public function storeMapel(Request $request)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $request->validate([
            'id_mapel'   => 'required|string|max:20|unique:mata_pelajaran,id_mapel',
            'nama_mapel' => 'required|string|max:100',
        ], [], [
            'id_mapel' => 'Kode Mapel', 'nama_mapel' => 'Nama Mapel',
        ]);

        MataPelajaran::create([
            'id_mapel'   => strtoupper(trim($request->id_mapel)),
            'nama_mapel' => trim($request->nama_mapel),
        ]);

        return redirect()->route('waka.master-data.mapel')->with('success', 'Mata pelajaran "' . $request->nama_mapel . '" berhasil ditambahkan.');
    }

    public function updateMapel(Request $request, string $id)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $mapel = MataPelajaran::where('id_mapel', $id)->first();
        if (!$mapel) {
            return redirect()->route('waka.master-data.mapel')->withErrors(['error' => 'Mata pelajaran tidak ditemukan.']);
        }

        $request->validate([
            'nama_mapel' => 'required|string|max:100',
        ]);

        $mapel->update(['nama_mapel' => trim($request->nama_mapel)]);

        return redirect()->route('waka.master-data.mapel')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroyMapel(string $id)
    {
        if ($redirect = $this->tolakJikaBukanWaka()) {
            return $redirect;
        }

        $mapel = MataPelajaran::where('id_mapel', $id)->first();
        if (!$mapel) {
            return redirect()->route('waka.master-data.mapel')->withErrors(['error' => 'Mata pelajaran tidak ditemukan.']);
        }

        $dipakai = DB::table('jadwal_pelajaran')->where('id_mapel', $id)->exists();
        if ($dipakai) {
            return redirect()->route('waka.master-data.mapel')->withErrors([
                'error' => 'Mata pelajaran "' . $mapel->nama_mapel . '" tidak dapat dihapus karena masih dipakai pada jadwal pelajaran. Hapus atau ubah jadwal tersebut terlebih dahulu.',
            ]);
        }

        $mapel->delete();

        return redirect()->route('waka.master-data.mapel')->with('success', 'Mata pelajaran "' . $mapel->nama_mapel . '" berhasil dihapus.');
    }
}
