<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data OTP hanya sementara, jadi tabel versi lama aman dihapus dan dibuat ulang.
        Schema::dropIfExists('reset_password_otps');

        Schema::create('reset_password_otps', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_user')->unique();
            $table->string('otp_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reset_password_otps');
    }
};
