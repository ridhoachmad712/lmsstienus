<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // mis. "Sesi 1"
            $table->string('start_time', 5);   // HH:MM
            $table->string('end_time', 5);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('room')->constrained('rooms')->nullOnDelete();
            $table->foreignId('time_slot_id')->nullable()->after('room_id')->constrained('time_slots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
            $table->dropConstrainedForeignId('time_slot_id');
        });
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('rooms');
    }
};
