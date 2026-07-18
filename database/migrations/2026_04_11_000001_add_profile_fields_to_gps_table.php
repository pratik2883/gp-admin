<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gps', function (Blueprint $table) {
            if (! Schema::hasColumn('gps', 'registration_number')) {
                $table->string('registration_number', 100)
                    ->nullable()
                    ->after('user_id');
            }

            if (! Schema::hasColumn('gps', 'designation')) {
                $table->string('designation', 100)
                    ->nullable()
                    ->after('registration_number');
            }

            if (! Schema::hasColumn('gps', 'registration_type')) {
                $table->string('registration_type', 50)
                    ->nullable()
                    ->after('designation');
            }

            if (! Schema::hasColumn('gps', 'registration_council')) {
                $table->string('registration_council', 150)
                    ->nullable()
                    ->after('registration_type');
            }

            if (! Schema::hasColumn('gps', 'registration_valid_until')) {
                $table->date('registration_valid_until')
                    ->nullable()
                    ->after('registration_council');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gps', function (Blueprint $table) {
            if (Schema::hasColumn('gps', 'registration_valid_until')) {
                $table->dropColumn('registration_valid_until');
            }

            if (Schema::hasColumn('gps', 'registration_council')) {
                $table->dropColumn('registration_council');
            }

            if (Schema::hasColumn('gps', 'registration_type')) {
                $table->dropColumn('registration_type');
            }

            if (Schema::hasColumn('gps', 'designation')) {
                $table->dropColumn('designation');
            }

            if (Schema::hasColumn('gps', 'registration_number')) {
                $table->dropColumn('registration_number');
            }
        });
    }
};
