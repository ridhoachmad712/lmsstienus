<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kaprodi ditunjuk di Prodi: satu dosen menjabat kaprodi prodi tertentu,
     * cukup dengan satu akun (tetap bisa mengajar + kelola prodinya).
     */
    public function up(): void
    {
        Schema::table('prodi', function (Blueprint $table) {
            $table->foreignId('kaprodi_id')->nullable()->after('code')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prodi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kaprodi_id');
        });
    }
};
