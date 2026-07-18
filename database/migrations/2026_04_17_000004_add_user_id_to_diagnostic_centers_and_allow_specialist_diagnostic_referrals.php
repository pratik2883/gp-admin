<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_centers', function (Blueprint $table) {
            if (! Schema::hasColumn('diagnostic_centers', 'user_id')) {
                $table
                    ->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
                $table->unique('user_id');
            }
        });

        Schema::table('diagnostic_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('diagnostic_referrals', 'specialist_id')) {
                $table
                    ->foreignId('specialist_id')
                    ->nullable()
                    ->after('gp_id')
                    ->constrained('specialists')
                    ->nullOnDelete();
                $table->index(['specialist_id', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_referrals', 'specialist_id')) {
                $table->dropIndex(['specialist_id', 'status']);
                $table->dropForeign(['specialist_id']);
                $table->dropColumn('specialist_id');
            }
        });

        Schema::table('diagnostic_centers', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_centers', 'user_id')) {
                $table->dropUnique(['user_id']);
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
