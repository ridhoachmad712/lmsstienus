<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tugas kelompok: bentuk tugas (individu/kelompok) + maks anggota,
     * tabel kelompok per-tugas + anggota, dan tautan pengumpulan → kelompok
     * (satu pengumpulan dipakai bersama seluruh anggota).
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->string('mode', 20)->default('individu')->after('type'); // individu | kelompok
            $table->unsignedTinyInteger('group_max')->nullable()->after('mode');
        });

        Schema::create('assignment_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('assignment_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['assignment_group_id', 'user_id']);
        });

        Schema::table('submissions', function (Blueprint $table) {
            $table->foreignId('assignment_group_id')->nullable()->after('user_id')
                ->constrained('assignment_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assignment_group_id');
        });
        Schema::dropIfExists('assignment_group_user');
        Schema::dropIfExists('assignment_groups');
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['mode', 'group_max']);
        });
    }
};
