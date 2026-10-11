<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MR field onboarding (POST /api/mr/onboard-doctor) collects a different set of
 * details per role, but the doctor tables had nowhere to keep most of it:
 *
 *  - `gps` had no degree / photo / geo columns at all, so every GP field the MR
 *    filled in was silently dropped by mass-assignment protection.
 *  - `specialists` had no geo or clinic-photo columns.
 *  - `diagnostic_centers.location_id` was NOT NULL, so creating a center from
 *    the field (where there is no picked location) failed outright.
 *
 * Every column is nullable and guarded with hasColumn(), so this migration is
 * safe to re-run and does not touch existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gps', function (Blueprint $table) {
            if (! Schema::hasColumn('gps', 'degree')) {
                $table->string('degree', 120)->nullable()->after('designation');
            }
            if (! Schema::hasColumn('gps', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('degree');
            }
            if (! Schema::hasColumn('gps', 'clinic_photo_path')) {
                $table->string('clinic_photo_path')->nullable()->after('profile_photo_path');
            }
            if (! Schema::hasColumn('gps', 'visiting_card_path')) {
                $table->string('visiting_card_path')->nullable()->after('clinic_photo_path');
            }
            if (! Schema::hasColumn('gps', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('visiting_card_path');
            }
            if (! Schema::hasColumn('gps', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('gps', 'onboarded_by_mr_id')) {
                $table->foreignId('onboarded_by_mr_id')
                    ->nullable()
                    ->after('longitude')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('specialists', function (Blueprint $table) {
            if (! Schema::hasColumn('specialists', 'clinic_photo_path')) {
                $table->string('clinic_photo_path')->nullable()->after('profile_photo_path');
            }
            if (! Schema::hasColumn('specialists', 'visiting_card_path')) {
                $table->string('visiting_card_path')->nullable()->after('clinic_photo_path');
            }
            if (! Schema::hasColumn('specialists', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('visiting_card_path');
            }
            if (! Schema::hasColumn('specialists', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('specialists', 'onboarded_by_mr_id')) {
                $table->foreignId('onboarded_by_mr_id')
                    ->nullable()
                    ->after('longitude')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('diagnostic_centers', function (Blueprint $table) {
            if (! Schema::hasColumn('diagnostic_centers', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('name');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'clinic_photo_path')) {
                $table->string('clinic_photo_path')->nullable()->after('profile_photo_path');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'visiting_card_path')) {
                $table->string('visiting_card_path')->nullable()->after('clinic_photo_path');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('visiting_card_path');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'onboarded_by_mr_id')) {
                $table->foreignId('onboarded_by_mr_id')
                    ->nullable()
                    ->after('longitude')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // A center onboarded in the field has no admin-picked location, and the
        // original schema made the column NOT NULL with a foreign key.
        if (Schema::hasColumn('diagnostic_centers', 'location_id')) {
            Schema::table('diagnostic_centers', function (Blueprint $table) {
                $table->foreignId('location_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['gps', 'specialists', 'diagnostic_centers'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'onboarded_by_mr_id')) {
                    $table->dropConstrainedForeignId('onboarded_by_mr_id');
                }

                foreach ([
                    'longitude',
                    'latitude',
                    'visiting_card_path',
                    'clinic_photo_path',
                    'profile_photo_path',
                ] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }

                if ($tableName === 'gps' && Schema::hasColumn('gps', 'degree')) {
                    $table->dropColumn('degree');
                }
            });
        }
    }
};
