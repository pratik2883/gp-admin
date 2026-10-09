<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite indexes for the hot list/claim query shapes.
     *
     * Each index is shaped so the equality predicates come first and any range /
     * ordinal-ordering column comes last, which is what lets MySQL satisfy the
     * ORDER BY from the index and drop `Using filesort`.
     */
    public function up(): void
    {
        // GP referral list: `where gp_id = ? and status = ? order by created_at desc`.
        // Strictly supersedes referrals_gp_id_status_index for the ordered read.
        Schema::table('referrals', function (Blueprint $table) {
            $table->index(['gp_id', 'status', 'created_at'], 'referrals_gp_id_status_created_at_index');
        });

        // Specialist lead list: `where specialist_id = ? order by created_at desc`.
        Schema::table('referrals', function (Blueprint $table) {
            $table->index(['specialist_id', 'created_at'], 'referrals_specialist_id_created_at_index');
        });

        // DCR / calendar day lookup: `where mr_user_id = ? and visit_date ...`.
        Schema::table('mr_visits', function (Blueprint $table) {
            $table->index(['mr_user_id', 'visit_date'], 'mr_visits_mr_user_id_visit_date_index');
        });

        // One attendance record per MR per day. Also serves the check-in lookup.
        Schema::table('mr_attendances', function (Blueprint $table) {
            $table->unique(['mr_user_id', 'date'], 'mr_attendances_mr_user_id_date_unique');
        });

        // Expiring-subscription sweeps: `where status = ? and ends_at between ...`.
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->index(['status', 'ends_at'], 'user_subscriptions_status_ends_at_index');
        });

        // Queue worker claim:
        // `where queue = ? and reserved_at is null and available_at <= ? order by id`.
        Schema::table('jobs', function (Blueprint $table) {
            $table->index(
                ['queue', 'reserved_at', 'available_at', 'id'],
                'jobs_queue_reserved_at_available_at_id_index'
            );
        });

        // The composite above covers every query the standalone index served.
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_index');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->index('queue', 'jobs_queue_index');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_reserved_at_available_at_id_index');
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex('user_subscriptions_status_ends_at_index');
        });

        Schema::table('mr_attendances', function (Blueprint $table) {
            $table->dropUnique('mr_attendances_mr_user_id_date_unique');
        });

        Schema::table('mr_visits', function (Blueprint $table) {
            $table->dropIndex('mr_visits_mr_user_id_visit_date_index');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropIndex('referrals_specialist_id_created_at_index');
            $table->dropIndex('referrals_gp_id_status_created_at_index');
        });
    }
};
