<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 50)->index();
            $table->string('plan_family', 30)->nullable()->index();
            $table->string('bed_slab', 30)->nullable()->index();
            $table->unsignedInteger('duration_months');
            $table->decimal('price', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->text('description')->nullable();
            $table->json('feature_points')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['category', 'plan_family']);
            $table->index(['category', 'bed_slab']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
