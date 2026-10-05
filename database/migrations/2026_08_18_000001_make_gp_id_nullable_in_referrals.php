<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referrals')) {
            return;
        }
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE referrals DROP FOREIGN KEY referrals_gp_id_foreign');
        DB::statement('ALTER TABLE referrals MODIFY gp_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE referrals ADD CONSTRAINT referrals_gp_id_foreign FOREIGN KEY (gp_id) REFERENCES gps(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        if (! Schema::hasTable('referrals')) {
            return;
        }
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE referrals DROP FOREIGN KEY referrals_gp_id_foreign');
        DB::statement('ALTER TABLE referrals MODIFY gp_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE referrals ADD CONSTRAINT referrals_gp_id_foreign FOREIGN KEY (gp_id) REFERENCES gps(id) ON DELETE CASCADE');
    }
};