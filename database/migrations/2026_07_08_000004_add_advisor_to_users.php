<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Dosen pembimbing akademik (wali) untuk mahasiswa. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('advisor_id')->nullable()->after('kurikulum_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advisor_id');
        });
    }
};
