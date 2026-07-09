<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('lainnya'); // krs, kuliah, uts, uas, nilai, libur, lainnya
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('year');
            $table->string('semester'); // Ganjil / Genap / Antara
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['year', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_events');
    }
};
