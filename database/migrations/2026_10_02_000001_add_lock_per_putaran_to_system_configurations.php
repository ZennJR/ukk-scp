<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['lock_putaran_1', 'lock_putaran_2'] as $kolom) {
            if (!Schema::hasColumn('system_configurations', $kolom)) {
                Schema::table('system_configurations', function (Blueprint $table) use ($kolom) {
                    $table->boolean($kolom)->default(false);
                });
                // Warisi status kunci global yang lama
                DB::table('system_configurations')->update([$kolom => DB::raw('is_schedule_locked')]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('system_configurations', function (Blueprint $table) {
            $table->dropColumn(['lock_putaran_1', 'lock_putaran_2']);
        });
    }
};
