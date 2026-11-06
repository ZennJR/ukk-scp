<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_pelajaran', function (Blueprint $table) {
            // Tanggal pasti untuk jadwal ini (bukan cuma nama hari yang berulang).
            // Nullable dulu supaya data lama yang belum punya tanggal tidak error,
            // lalu diisi otomatis di bawah berdasarkan kolom 'hari' yang sudah ada.
            $table->date('tanggal')->nullable()->after('hari');
        });

        // Isi tanggal untuk baris lama (best-effort): pakai tanggal jatuh tempo
        // terdekat (hari ini atau minggu ini) yang cocok dengan nama harinya,
        // supaya data lama tidak kosong. Waka/Guru bisa mengoreksinya lagi lewat form.
        $hariKeAngka = [
            'Minggu' => 0, 'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3,
            'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6,
        ];

        $rows = \Illuminate\Support\Facades\DB::table('jadwal_pelajaran')->whereNull('tanggal')->get();
        foreach ($rows as $row) {
            $target = $hariKeAngka[$row->hari] ?? null;
            if ($target === null) {
                continue;
            }
            $today = new \DateTime('today');
            $diff = ($target - (int) $today->format('w') + 7) % 7;
            $tanggal = (clone $today)->modify("+{$diff} days")->format('Y-m-d');

            \Illuminate\Support\Facades\DB::table('jadwal_pelajaran')
                ->where('id_jadwal', $row->id_jadwal)
                ->update(['tanggal' => $tanggal]);
        }
    }

    public function down(): void
    {
        Schema::table('jadwal_pelajaran', function (Blueprint $table) {
            $table->dropColumn('tanggal');
        });
    }
};
