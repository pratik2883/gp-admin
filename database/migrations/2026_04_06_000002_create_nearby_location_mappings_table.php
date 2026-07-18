<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nearby_location_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->foreignId('nearby_location_id')
                ->constrained('locations')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['location_id', 'nearby_location_id'], 'nearby_location_unique');
            $table->index(['location_id', 'sort_order'], 'nearby_location_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nearby_location_mappings');
    }
};
