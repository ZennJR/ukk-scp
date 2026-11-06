<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jam guru mengisi jurnal
        if (Schema::hasTable('journals') && !Schema::hasColumn('journals', 'filled_at')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dateTime('filled_at')->nullable();
            });
        }

        if (!Schema::hasTable('pelanggaran_guru')) {
            Schema::create('pelanggaran_guru', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('id_guru')->index();
                $table->unsignedInteger('schedule_id')->nullable();
                $table->date('tanggal')->index();
                $table->string('jenis', 30);                    // 'Tidak Mengisi' | 'Terlambat'
                $table->string('kelas', 100)->nullable();       // snapshot (jadwal bisa dihapus)
                $table->string('mapel', 100)->nullable();
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->dateTime('batas_waktu');                // jam selesai + batas jam
                $table->dateTime('jurnal_diisi_at')->nullable();
                $table->string('status', 20)->default('Aktif'); // 'Aktif' | 'Dibatalkan'
                $table->string('catatan', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['schedule_id', 'tanggal'], 'pelanggaran_guru_jadwal_tanggal_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggaran_guru');
        if (Schema::hasTable('journals') && Schema::hasColumn('journals', 'filled_at')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropColumn('filled_at');
            });
        }
    }
};
