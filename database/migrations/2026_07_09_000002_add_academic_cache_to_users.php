<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cache hasil hitung akademik (dipopulasi lazy + disegarkan saat kelas diselesaikan).
            $table->decimal('ipk_cache', 4, 2)->nullable()->after('student_status');
            $table->unsignedSmallInteger('sks_cache')->nullable()->after('ipk_cache');
            $table->decimal('ips_cache', 4, 2)->nullable()->after('sks_cache');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ipk_cache', 'sks_cache', 'ips_cache']);
        });
    }
};
