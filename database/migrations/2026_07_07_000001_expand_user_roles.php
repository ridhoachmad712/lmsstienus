<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti kolom `role` (enum dosen/mahasiswa) menjadi string agar mendukung
     * peran kampus: admin & kaprodi. Nilai divalidasi di aplikasi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('mahasiswa')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['dosen', 'mahasiswa'])->default('mahasiswa')->change();
        });
    }
};
