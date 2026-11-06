<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_change_otps')) {
            return;
        }

        Schema::create('email_change_otps', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_user')->unique();
            $table->string('email_baru', 100);
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
        Schema::dropIfExists('email_change_otps');
    }
};
