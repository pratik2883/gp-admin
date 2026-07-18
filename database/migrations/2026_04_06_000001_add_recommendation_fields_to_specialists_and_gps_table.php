<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            if (! Schema::hasColumn('specialists', 'location_id')) {
                $table->foreignId('location_id')
                    ->nullable()
                    ->after('clinic_city')
                    ->constrained('locations')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('specialists', 'is_premium')) {
                $table->boolean('is_premium')
                    ->default(false)
                    ->after('location_id');
            }

            if (! Schema::hasColumn('specialists', 'priority_order')) {
                $table->integer('priority_order')
                    ->nullable()
                    ->after('is_premium');
            }

            $table->index(['location_id', 'is_premium', 'priority_order'], 'specialists_reco_index');
        });

        Schema::table('gps', function (Blueprint $table) {
            if (! Schema::hasColumn('gps', 'default_location_id')) {
                $table->foreignId('default_location_id')
                    ->nullable()
                    ->after('city')
                    ->constrained('locations')
                    ->nullOnDelete();
            }

            $table->index(['default_location_id'], 'gps_default_location_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            if (Schema::hasColumn('specialists', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }

            if (Schema::hasColumn('specialists', 'is_premium')) {
                $table->dropColumn('is_premium');
            }

            if (Schema::hasColumn('specialists', 'priority_order')) {
                $table->dropColumn('priority_order');
            }

            $table->dropIndex('specialists_reco_index');
        });

        Schema::table('gps', function (Blueprint $table) {
            if (Schema::hasColumn('gps', 'default_location_id')) {
                $table->dropForeign(['default_location_id']);
                $table->dropColumn('default_location_id');
            }

            $table->dropIndex('gps_default_location_id_index');
        });
    }
};
