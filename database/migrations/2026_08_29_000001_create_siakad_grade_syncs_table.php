<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('siakad_schedule_id')->nullable()->index()->after('mata_kuliah_id');
            $table->timestamp('grades_finalized_at')->nullable()->after('status');
            $table->foreignId('grades_finalized_by')->nullable()->after('grades_finalized_at')
                ->constrained('users')->nullOnDelete();
        });

        Schema::create('siakad_grade_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('siakad_schedule_id')->nullable();
            $table->unsignedInteger('siakad_academic_year_id')->nullable();
            $table->decimal('numeric_score', 5, 2);
            $table->string('letter_grade', 5)->nullable();
            $table->decimal('quality_point', 4, 2)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siakad_grade_syncs');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grades_finalized_by');
            $table->dropIndex(['siakad_schedule_id']);
            $table->dropColumn(['siakad_schedule_id', 'grades_finalized_at']);
        });
    }
};
