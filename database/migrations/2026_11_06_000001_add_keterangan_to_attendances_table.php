<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendances') && !Schema::hasColumn('attendances', 'keterangan')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->string('keterangan', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('attendances', 'keterangan')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }
};
