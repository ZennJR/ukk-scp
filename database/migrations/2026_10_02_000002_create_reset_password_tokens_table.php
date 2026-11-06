<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reset_password_tokens')) {
            return;
        }

        Schema::create('reset_password_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_user')->index();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reset_password_tokens');
    }
};
