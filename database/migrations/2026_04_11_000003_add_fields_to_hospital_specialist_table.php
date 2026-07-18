<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_specialist', function (Blueprint $table) {
            if (! Schema::hasColumn('hospital_specialist', 'is_super_specialist')) {
                $table->boolean('is_super_specialist')
                    ->default(false)
                    ->after('hospital_id');
            }

            if (! Schema::hasColumn('hospital_specialist', 'department')) {
                $table->string('department', 150)
                    ->nullable()
                    ->after('is_super_specialist');
            }

            if (! Schema::hasColumn('hospital_specialist', 'role')) {
                $table->string('role', 150)
                    ->nullable()
                    ->after('department');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hospital_specialist', function (Blueprint $table) {
            if (Schema::hasColumn('hospital_specialist', 'role')) {
                $table->dropColumn('role');
            }

            if (Schema::hasColumn('hospital_specialist', 'department')) {
                $table->dropColumn('department');
            }

            if (Schema::hasColumn('hospital_specialist', 'is_super_specialist')) {
                $table->dropColumn('is_super_specialist');
            }
        });
    }
};
