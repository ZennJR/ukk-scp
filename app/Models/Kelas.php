<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Kelas extends Model {
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $guarded = [];

    // Nama kelas: pakai kolom nama_kelas bila ada di DB; jika tidak, dirakit dari tingkat + id_jurusan
    // (mis. "XI RPL 1"); jika keduanya kosong, jatuh ke kode kelas.
    public function getNamaKelasAttribute()
    {
        $tersimpan = trim((string) ($this->attributes['nama_kelas'] ?? ''));
        if ($tersimpan !== '') {
            return $tersimpan;
        }

        $nama = trim(($this->tingkat ?? '') . ' ' . ($this->id_jurusan ?? ''));
        return $nama !== '' ? $nama : $this->id_kelas;
    }
}