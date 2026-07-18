<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hospital_specialist', function (Blueprint $table) {
            $table->id();

            $table->foreignId('specialist_id')
                ->constrained('specialists')
                ->onDelete('cascade');

            $table->foreignId('hospital_id')
                ->constrained('hospitals')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['specialist_id', 'hospital_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_specialist');
    }
};
