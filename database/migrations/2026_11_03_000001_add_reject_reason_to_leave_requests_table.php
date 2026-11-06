<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambah kolom alasan penolakan izin siswa (diisi Wali Kelas / Guru BK saat menolak).
 * Aman dijalankan berulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_requests') && !Schema::hasColumn('leave_requests', 'reject_reason')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->text('reject_reason')->nullable()->after('status_approval');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_requests', 'reject_reason')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropColumn('reject_reason');
            });
        }
    }
};
