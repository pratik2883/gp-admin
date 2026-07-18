<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialist_specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_id')->constrained('specialists')->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained('specialties')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['specialist_id', 'specialty_id']);
            $table->index(['specialty_id', 'specialist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialist_specialties');
    }
};
