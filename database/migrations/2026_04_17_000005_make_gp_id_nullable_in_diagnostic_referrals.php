<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('diagnostic_referrals')) {
            return;
        }
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE diagnostic_referrals DROP FOREIGN KEY diagnostic_referrals_gp_id_foreign');
        DB::statement('ALTER TABLE diagnostic_referrals MODIFY gp_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE diagnostic_referrals ADD CONSTRAINT diagnostic_referrals_gp_id_foreign FOREIGN KEY (gp_id) REFERENCES gps(id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        if (! Schema::hasTable('diagnostic_referrals')) {
            return;
        }
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE diagnostic_referrals DROP FOREIGN KEY diagnostic_referrals_gp_id_foreign');
        DB::statement('ALTER TABLE diagnostic_referrals MODIFY gp_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE diagnostic_referrals ADD CONSTRAINT diagnostic_referrals_gp_id_foreign FOREIGN KEY (gp_id) REFERENCES gps(id) ON DELETE CASCADE');
    }
};
