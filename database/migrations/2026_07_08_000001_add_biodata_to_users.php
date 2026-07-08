<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Biodata mahasiswa & dosen untuk SIAKAD. */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Umum
            $table->string('gender', 1)->nullable()->after('nim_nip');   // L / P
            $table->string('birth_place')->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->text('address')->nullable()->after('birth_date');

            // Mahasiswa
            $table->unsignedSmallInteger('entry_year')->nullable()->after('address'); // angkatan
            $table->string('student_status', 15)->default('aktif')->after('entry_year'); // aktif/cuti/lulus/keluar

            // Dosen
            $table->string('nidn', 30)->nullable()->after('student_status');
            $table->string('jabatan')->nullable()->after('nidn'); // jabatan akademik
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gender', 'birth_place', 'birth_date', 'address', 'entry_year', 'student_status', 'nidn', 'jabatan']);
        });
    }
};
