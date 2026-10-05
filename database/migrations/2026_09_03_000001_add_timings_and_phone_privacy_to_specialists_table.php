<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            if (! Schema::hasColumn('specialists', 'clinic_timings')) {
                $table->text('clinic_timings')->nullable()->after('clinic_pincode');
            }
            if (! Schema::hasColumn('specialists', 'hospital_visiting_hours')) {
                $table->text('hospital_visiting_hours')->nullable()->after('clinic_timings');
            }
            if (! Schema::hasColumn('specialists', 'available_days')) {
                $table->json('available_days')->nullable()->after('hospital_visiting_hours');
            }
            if (! Schema::hasColumn('specialists', 'show_mobile_number')) {
                $table->boolean('show_mobile_number')->default(true)->after('available_days');
            }
            if (! Schema::hasColumn('specialists', 'show_whatsapp_number')) {
                $table->boolean('show_whatsapp_number')->default(true)->after('show_mobile_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            foreach ([
                'clinic_timings',
                'hospital_visiting_hours',
                'available_days',
                'show_mobile_number',
                'show_whatsapp_number',
            ] as $column) {
                if (Schema::hasColumn('specialists', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
