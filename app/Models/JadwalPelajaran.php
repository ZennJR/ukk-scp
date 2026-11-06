<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalPelajaran extends Model
{
    use HasFactory;

    protected $table = 'jadwal_pelajaran';
    protected $primaryKey = 'id_jadwal'; // <-- Paling penting agar Laravel membaca primary key yang benar
    public $timestamps = false; 

    protected $guarded = [];

    // Relasi-relasi ke tabel terkait
    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'id_tahun_ajaran', 'id_tahun_ajaran');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'id_mapel', 'id_mapel');
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan_default', 'id_ruangan');
    }

    /**
     * Label tampilan yang selalu berusaha menampilkan NAMA (bukan kode).
     * Memakai relasi bila sudah dimuat; jika kosong, dicari langsung ke tabelnya.
     * Kode hanya dipakai sebagai cadangan terakhir.
     */
    public function getKelasLabelAttribute(): string
    {
        $kelas = $this->kelas ?: Kelas::where('id_kelas', $this->id_kelas)->first();
        return (string) ($kelas->nama_kelas ?? $this->id_kelas);
    }

    public function getMapelLabelAttribute(): string
    {
        $mapel = $this->mataPelajaran ?: MataPelajaran::where('id_mapel', $this->id_mapel)->first();
        return (string) ($mapel->nama_mapel ?? $this->id_mapel);
    }

    public function getRuanganLabelAttribute(): string
    {
        $ruang = $this->ruangan ?: Ruangan::where('id_ruangan', $this->id_ruangan_default)->first();
        return (string) ($ruang->nama_ruangan ?? $this->id_ruangan_default);
    }

    /**
     * Label "jam ke-berapa" berdasarkan hari + jam mulai/selesai (config/jam_pelajaran.php).
     * Contoh: "Jam ke-8" atau "Jam ke-1 - 3". Null jika jam tidak cocok dengan slot
     * (mis. data lama yang diinput bebas).
     */
    public function getJamKeAttribute(): ?string
    {
        $slots = config('jam_pelajaran.' . $this->hari, []);
        $mulai = substr((string) $this->jam_mulai, 0, 5);
        $selesai = substr((string) $this->jam_selesai, 0, 5);

        $awal = collect($slots)->first(fn ($s) => $s[1] === $mulai);
        $akhir = collect($slots)->first(fn ($s) => $s[2] === $selesai);

        if (!$awal || !$akhir || $akhir[0] < $awal[0]) {
            return null;
        }

        return $awal[0] === $akhir[0]
            ? 'Jam ke-' . $awal[0]
            : 'Jam ke-' . $awal[0] . ' - ' . $akhir[0];
    }

    /**
     * Teks pencarian kelas (huruf kecil, beberapa penulisan dipisah "|") untuk filter di halaman jadwal.
     */
    public function getKelasSearchAttribute(): string
    {
        $k = $this->kelas;
        $varian = [
            trim(($k->tingkat ?? '') . ' ' . ($k->id_jurusan ?? '')),
            $k->nama_kelas ?? '',
            (string) $this->id_kelas,
        ];

        return mb_strtolower(implode('|', array_filter(array_map(fn ($v) => preg_replace('/\s+/', ' ', trim($v)), $varian))));
    }
}