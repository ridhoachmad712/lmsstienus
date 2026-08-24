<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tugas kelompok: bentuk (individu/kelompok) + maks anggota, tabel kelompok
     * per-tugas + anggota, dan tautan pengumpulan → kelompok.
     *
     * Catatan: sengaja TANPA foreign key level-DB. Sebagian basis data produksi
     * lama memakai id bertipe INT (drift skema) sehingga FK dari BIGINT gagal
     * (errno 150). Integritas dijaga di level aplikasi (event model). Migrasi
     * dibuat idempoten agar aman dijalankan ulang setelah kegagalan sebelumnya.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('assignments', 'mode')) {
                $table->string('mode', 20)->default('individu')->after('type'); // individu | kelompok
            }
            if (! Schema::hasColumn('assignments', 'group_max')) {
                $table->unsignedTinyInteger('group_max')->nullable()->after('mode');
            }
        });

        // Buat ulang bersih (tabel kosong; aman bila sisa parsial dari migrasi gagal).
        Schema::dropIfExists('assignment_group_user');
        Schema::dropIfExists('assignment_groups');

        Schema::create('assignment_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->index();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('assignment_group_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_group_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            $table->unique(['assignment_group_id', 'user_id']);
        });

        if (! Schema::hasColumn('submissions', 'assignment_group_id')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('assignment_group_id')->nullable()->after('user_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('submissions', 'assignment_group_id')) {
            Schema::table('submissions', function (Blueprint $table) {
                $table->dropColumn('assignment_group_id');
            });
        }
        Schema::dropIfExists('assignment_group_user');
        Schema::dropIfExists('assignment_groups');
        Schema::table('assignments', function (Blueprint $table) {
            foreach (['mode', 'group_max'] as $col) {
                if (Schema::hasColumn('assignments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
