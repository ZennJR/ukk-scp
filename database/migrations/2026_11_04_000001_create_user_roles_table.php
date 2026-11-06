<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peran tambahan per akun. users.id_role tetap menjadi peran UTAMA,
 * sehingga seorang Guru bisa sekaligus Wali Kelas (id_kelas = kelas yang diampu).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_roles')) {
            return;
        }

        Schema::create('user_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_user')->index();
            $table->unsignedInteger('id_role');
            $table->string('id_kelas', 20)->nullable();
            $table->unique(['id_user', 'id_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
