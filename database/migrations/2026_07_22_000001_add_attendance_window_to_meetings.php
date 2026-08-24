<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jendela absensi otomatis per pertemuan. Bila diisi, absensi terbuka
     * otomatis pada rentang ini tanpa dosen perlu klik "Mulai sesi".
     * Bila kosong, default mengikuti tanggal pertemuan (sepanjang hari itu).
     */
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dateTime('attend_opens_at')->nullable()->after('date');
            $table->dateTime('attend_closes_at')->nullable()->after('attend_opens_at');
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['attend_opens_at', 'attend_closes_at']);
        });
    }
};
