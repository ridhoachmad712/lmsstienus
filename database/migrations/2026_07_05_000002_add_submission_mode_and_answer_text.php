<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bentuk jawaban tugas: berkas (default), teks langsung, atau keduanya.
     * `answer_text` menampung jawaban yang diketik mahasiswa langsung di aplikasi.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('submission_mode', 10)->default('file')->after('type');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->text('answer_text')->nullable()->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('submission_mode');
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn('answer_text');
        });
    }
};
