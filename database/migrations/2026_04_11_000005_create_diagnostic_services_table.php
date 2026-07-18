<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_center_id')->constrained('diagnostic_centers')->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['diagnostic_center_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_services');
    }
};
