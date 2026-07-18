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

        if (! Schema::hasColumn('referrals', 'referral_type')) {
            DB::statement("ALTER TABLE referrals ADD COLUMN referral_type ENUM('specialist','hospital') NOT NULL DEFAULT 'specialist' AFTER hospital_id");
            DB::statement('ALTER TABLE referrals ADD INDEX referrals_referral_type_index (referral_type)');
        }

        if (! Schema::hasColumn('referrals', 'priority')) {
            DB::statement("ALTER TABLE referrals ADD COLUMN priority ENUM('routine','urgent') NOT NULL DEFAULT 'routine' AFTER appointment_type");
        }

        if (! Schema::hasColumn('referrals', 'department')) {
            DB::statement('ALTER TABLE referrals ADD COLUMN department VARCHAR(150) NULL AFTER referral_type');
        }

        if (Schema::hasColumn('referrals', 'specialist_id')) {
            try {
                DB::statement('ALTER TABLE referrals DROP FOREIGN KEY referrals_specialist_id_foreign');
            } catch (\Throwable $e) {
            }
            DB::statement('ALTER TABLE referrals MODIFY specialist_id BIGINT UNSIGNED NULL');
            try {
                DB::statement('ALTER TABLE referrals ADD CONSTRAINT referrals_specialist_id_foreign FOREIGN KEY (specialist_id) REFERENCES specialists(id) ON DELETE CASCADE');
            } catch (\Throwable $e) {
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('referrals')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasColumn('referrals', 'specialist_id')) {
            try {
                DB::statement('ALTER TABLE referrals DROP FOREIGN KEY referrals_specialist_id_foreign');
            } catch (\Throwable $e) {
            }
            DB::statement('ALTER TABLE referrals MODIFY specialist_id BIGINT UNSIGNED NOT NULL');
            try {
                DB::statement('ALTER TABLE referrals ADD CONSTRAINT referrals_specialist_id_foreign FOREIGN KEY (specialist_id) REFERENCES specialists(id) ON DELETE CASCADE');
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasColumn('referrals', 'department')) {
            DB::statement('ALTER TABLE referrals DROP COLUMN department');
        }

        if (Schema::hasColumn('referrals', 'priority')) {
            DB::statement('ALTER TABLE referrals DROP COLUMN priority');
        }

        if (Schema::hasColumn('referrals', 'referral_type')) {
            DB::statement('ALTER TABLE referrals DROP INDEX referrals_referral_type_index');
            DB::statement('ALTER TABLE referrals DROP COLUMN referral_type');
        }
    }
};
