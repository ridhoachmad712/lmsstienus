<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete(); // null = seluruh kampus
            $table->string('title');
            $table->text('body');
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->index('prodi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_announcements');
    }
};
