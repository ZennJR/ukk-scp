<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_user');
            $table->string('type', 50);
            $table->string('title', 150);
            $table->string('message', 255);
            $table->string('url', 255)->nullable();
            // Kunci unik opsional dipakai untuk mencegah notifikasi kembar,
            // contoh: pengingat jurnal untuk jadwal & tanggal yang sama.
            $table->string('unique_key', 150)->nullable()->unique();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
            $table->index(['id_user', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
