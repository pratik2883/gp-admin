<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role_subtype')) {
                $table->string('role_subtype', 50)
                    ->nullable()
                    ->after('role')
                    ->index();
            }
        });

        DB::table('users')
            ->where('role', 'specialist')
            ->whereNull('role_subtype')
            ->update(['role_subtype' => 'specialist']);

        if (Schema::hasTable('diagnostic_centers')) {
            $diagnosticUserIds = DB::table('diagnostic_centers')
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($diagnosticUserIds !== []) {
                DB::table('users')
                    ->whereIn('id', $diagnosticUserIds)
                    ->update(['role_subtype' => 'diagnostic_center']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_subtype')) {
                $table->dropIndex(['role_subtype']);
                $table->dropColumn('role_subtype');
            }
        });
    }
};
