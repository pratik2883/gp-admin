<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->string('category_snapshot', 50);
            $table->string('plan_name_snapshot');
            $table->string('plan_family_snapshot', 30)->nullable();
            $table->string('bed_slab_snapshot', 30)->nullable();
            $table->unsignedInteger('duration_months_snapshot');
            $table->decimal('price_snapshot', 10, 2);
            $table->string('currency_snapshot', 10)->default('INR');
            $table->string('status', 30)->default('pending_activation')->index();
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};
