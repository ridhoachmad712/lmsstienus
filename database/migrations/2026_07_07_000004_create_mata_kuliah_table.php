<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedTinyInteger('sks')->default(3);
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table) {
            // Kelas (course) menunjuk ke satu mata kuliah katalog; banyak kelas
            // boleh menunjuk MK yang sama (kelas paralel, beda dosen).
            $table->foreignId('mata_kuliah_id')->nullable()->after('prodi_id')->constrained('mata_kuliah')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mata_kuliah_id');
        });

        Schema::dropIfExists('mata_kuliah');
    }
};
