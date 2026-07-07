<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keanggotaan prodi. Nullable: admin (lintas prodi) & data lama boleh kosong.
     * Enrollment tetap lintas-prodi (tidak dibatasi kolom ini).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('prodi_id')->nullable()->after('role')->constrained('prodi')->nullOnDelete();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('prodi_id')->nullable()->after('user_id')->constrained('prodi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prodi_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prodi_id');
        });
    }
};
