<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('activated_at');
            $table->boolean('bonus_months_credited')->default(false)->after('trial_ends_at');
            $table->timestamp('expiry_reminder_sent_at')->nullable()->after('bonus_months_credited');
        });
    }

    public function down(): void
    {
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'bonus_months_credited', 'expiry_reminder_sent_at']);
        });
    }
};
