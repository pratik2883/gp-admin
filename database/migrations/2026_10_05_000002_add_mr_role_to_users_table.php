<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            // SQLite has no `MODIFY COLUMN`, and `enum()` is stored as a varchar
            // plus a CHECK constraint. Skipping the widen entirely (the previous
            // behaviour) left `check ("role" in ('gp','specialist',
            // 'hospital_admin','admin'))` in place, so every MR test died with
            // "CHECK constraint failed: role" and the new role was unusable on
            // SQLite. Laravel 12 rebuilds the table for `change()`, which drops
            // the stale constraint.
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 50)->default('gp')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'gp'");
    }

    public function down(): void
    {
    }
};
