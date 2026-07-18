<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('trial_days')->default(0)->after('duration_months');
            $table->unsignedInteger('bonus_months')->default(0)->after('trial_days');
            $table->boolean('is_trialable')->default(false)->after('bonus_months');
            $table->string('offer_label', 100)->nullable()->after('is_trialable');
            $table->string('offer_badge_color', 20)->nullable()->after('offer_label');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['trial_days', 'bonus_months', 'is_trialable', 'offer_label', 'offer_badge_color']);
        });
    }
};
