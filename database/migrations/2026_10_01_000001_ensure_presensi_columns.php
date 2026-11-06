<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memastikan struktur yang dibutuhkan fitur Presensi Siswa (UC-08) tersedia:
 *  - tabel attendances (journal_id, student_id, status)
 *  - kolom siswa.id_kelas (kelas tempat siswa terdaftar)
 * Aman dijalankan di database yang sudah punya tabel/kolom tersebut (db_skansaba.sql).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('journal_id');
                $table->integer('student_id');
                $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alfa'])->default('Hadir');
                $table->index('journal_id');
                $table->index('student_id');
            });
        }

        if (Schema::hasTable('siswa') && !Schema::hasColumn('siswa', 'id_kelas')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('id_kelas', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Sengaja kosong: tabel/kolom ini sudah ada di database sekolah sebelum migrasi ini.
    }
};
