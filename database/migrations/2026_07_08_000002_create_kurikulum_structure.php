<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kurikulum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $table->string('name');            // mis. "Kurikulum 2021"
            $table->unsignedSmallInteger('year');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::table('mata_kuliah', function (Blueprint $table) {
            $table->foreignId('kurikulum_id')->nullable()->after('prodi_id')->constrained('kurikulum')->nullOnDelete();
            $table->unsignedTinyInteger('semester_no')->nullable()->after('sks'); // semester rekomendasi 1..8
            $table->string('jenis', 10)->default('wajib')->after('semester_no');   // wajib / pilihan
        });

        // Prasyarat: mata_kuliah_id butuh prasyarat_id lulus dulu.
        Schema::create('mata_kuliah_prasyarat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->foreignId('prasyarat_id')->constrained('mata_kuliah')->cascadeOnDelete();
            $table->unique(['mata_kuliah_id', 'prasyarat_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kurikulum_id')->nullable()->after('entry_year')->constrained('kurikulum')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kurikulum_id');
        });
        Schema::dropIfExists('mata_kuliah_prasyarat');
        Schema::table('mata_kuliah', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kurikulum_id');
            $table->dropColumn(['semester_no', 'jenis']);
        });
        Schema::dropIfExists('kurikulum');
    }
};
