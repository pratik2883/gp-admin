<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modify referrals.status from ENUM to VARCHAR(50) to support 'ipd' and future custom statuses safely
        DB::statement("ALTER TABLE referrals MODIFY status VARCHAR(50) NOT NULL DEFAULT 'sent'");

        // 2. Add ipd_at timestamp if not exists
        if (! Schema::hasColumn('referrals', 'ipd_at')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->timestamp('ipd_at')->nullable()->after('consulted_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('referrals', 'ipd_at')) {
            Schema::table('referrals', function (Blueprint $table) {
                $table->dropColumn('ipd_at');
            });
        }

        DB::statement("ALTER TABLE referrals MODIFY status ENUM('sent','accepted','consulted','closed') NOT NULL DEFAULT 'sent'");
    }
};
