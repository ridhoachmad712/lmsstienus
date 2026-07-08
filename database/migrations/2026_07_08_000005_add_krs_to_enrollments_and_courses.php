<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // draft = di KRS mahasiswa, diajukan = menunggu wali, disetujui = enrollment aktif.
            // Default 'disetujui' → semua enrollment lama & penambahan langsung oleh dosen tetap aktif.
            $table->string('status')->default('disetujui')->index()->after('user_id');
            $table->timestamp('submitted_at')->nullable()->after('enrolled_at');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')
                ->constrained('users')->nullOnDelete(); // dosen wali penyetuju
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('quota')->nullable()->after('year'); // null = tanpa batas
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'submitted_at', 'approved_at']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('quota');
        });
    }
};
