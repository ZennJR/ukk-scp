<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tukar_ruangan')) {
            return;
        }

        Schema::create('tukar_ruangan', function (Blueprint $table) {
            $table->increments('id');
            $table->date('tanggal')->index();
            $table->unsignedInteger('id_jadwal_pemohon');
            $table->unsignedInteger('id_jadwal_tujuan');
            $table->unsignedInteger('id_guru_pemohon')->index();
            $table->unsignedInteger('id_guru_tujuan')->index();
            $table->string('id_ruangan_pemohon', 20);
            $table->string('id_ruangan_tujuan', 20);
            $table->string('ruangan_pemohon_nama', 100)->nullable();
            $table->string('ruangan_tujuan_nama', 100)->nullable();
            $table->string('info_pemohon', 200)->nullable();
            $table->string('info_tujuan', 200)->nullable();
            $table->time('jam_mulai');
            $table->string('alasan', 255);
            $table->string('status', 20)->default('Menunggu')->index();
            $table->string('alasan_tolak', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('responded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tukar_ruangan');
    }
};
