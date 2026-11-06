<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom no_hp sudah ada sejak awal; email belum, jadi ditambahkan di sini.
        // Nullable karena data lama belum tentu punya email.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->after('nomor_induk');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
