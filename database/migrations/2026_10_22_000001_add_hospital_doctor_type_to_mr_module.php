<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MR field force can visit and register hospitals too, but the module only knew
 * three doctor types.
 *
 * In the doctor apps a "Hospital" is not a fourth registration form: it is a
 * Specialist account with `role_subtype = hospital`, backed by a row in the
 * `hospitals` directory. MR had no way to record either, so this migration:
 *
 *  - widens `mr_visits.doctor_type` to accept `hospital`,
 *  - lets a visit point at the real `hospitals` row,
 *  - attributes a hospital directory record to the MR who onboarded it.
 *
 * Every step is guarded, so re-running is safe and existing rows are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mr_visits')) {
            if (DB::getDriverName() === 'sqlite') {
                // The enum is stored as a varchar plus a CHECK constraint, and
                // SQLite cannot MODIFY a column. Laravel rebuilds the table for
                // `change()`, which drops the stale check constraint — the same
                // fix already used for `users.role`.
                Schema::table('mr_visits', function (Blueprint $table) {
                    $table->string('doctor_type', 30)->default('gp')->change();
                });
            } else {
                DB::statement(
                    "ALTER TABLE mr_visits MODIFY COLUMN doctor_type "
                    ."ENUM('gp','specialist','diagnostic_center','hospital') "
                    ."NOT NULL DEFAULT 'gp'"
                );
            }

            if (! Schema::hasColumn('mr_visits', 'hospital_id')) {
                Schema::table('mr_visits', function (Blueprint $table) {
                    $table->foreignId('hospital_id')
                        ->nullable()
                        ->after('doctor_user_id')
                        ->constrained('hospitals')
                        ->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('hospitals') && ! Schema::hasColumn('hospitals', 'onboarded_by_mr_id')) {
            Schema::table('hospitals', function (Blueprint $table) {
                $table->foreignId('onboarded_by_mr_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mr_visits') && Schema::hasColumn('mr_visits', 'hospital_id')) {
            Schema::table('mr_visits', function (Blueprint $table) {
                $table->dropConstrainedForeignId('hospital_id');
            });
        }

        if (Schema::hasTable('hospitals') && Schema::hasColumn('hospitals', 'onboarded_by_mr_id')) {
            Schema::table('hospitals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('onboarded_by_mr_id');
            });
        }

        if (Schema::hasTable('mr_visits')) {
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('mr_visits', function (Blueprint $table) {
                    $table->string('doctor_type', 30)->default('gp')->change();
                });
            } else {
                DB::statement(
                    "ALTER TABLE mr_visits MODIFY COLUMN doctor_type "
                    ."ENUM('gp','specialist','diagnostic_center') "
                    ."NOT NULL DEFAULT 'gp'"
                );
            }
        }
    }
};
