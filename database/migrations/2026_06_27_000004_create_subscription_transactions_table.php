<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_subscription_id')->constrained('user_subscriptions')->cascadeOnDelete();
            $table->string('transaction_uuid', 36)->unique();
            $table->string('gateway', 30)->default('ccavenue')->index();
            $table->string('order_id')->unique();
            $table->string('tracking_id')->nullable()->index();
            $table->string('status', 30)->default('initiated')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->string('payment_reference')->nullable();
            $table->string('order_status')->nullable();
            $table->string('failure_message')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
};
