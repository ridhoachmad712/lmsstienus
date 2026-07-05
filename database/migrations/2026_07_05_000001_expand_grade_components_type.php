<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti kolom `type` (enum terbatas) menjadi string agar mendukung
     * tipe standar baru: kehadiran, project. Validasi nilai dilakukan di
     * controller (App\Models\GradeComponent::TYPES).
     */
    public function up(): void
    {
        Schema::table('grade_components', function (Blueprint $table) {
            $table->string('type', 20)->default('tugas')->change();
        });
    }

    public function down(): void
    {
        Schema::table('grade_components', function (Blueprint $table) {
            $table->enum('type', ['tugas', 'kuis', 'uts', 'uas', 'lainnya'])->default('tugas')->change();
        });
    }
};
