<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            if (! Schema::hasColumn('specialists', 'hospital_name')) {
                $table->string('hospital_name', 190)->nullable()->after('clinic_name');
            }
            if (! Schema::hasColumn('specialists', 'clinic_street')) {
                $table->string('clinic_street', 255)->nullable()->after('clinic_address');
            }
            if (! Schema::hasColumn('specialists', 'clinic_area')) {
                $table->string('clinic_area', 190)->nullable()->after('clinic_street');
            }
            if (! Schema::hasColumn('specialists', 'clinic_pincode')) {
                $table->string('clinic_pincode', 12)->nullable()->after('clinic_city');
            }
            if (! Schema::hasColumn('specialists', 'years_of_experience')) {
                $table->unsignedInteger('years_of_experience')->nullable()->after('medical_council_name');
            }
            if (! Schema::hasColumn('specialists', 'sub_specialties')) {
                $table->text('sub_specialties')->nullable()->after('years_of_experience');
            }
            if (! Schema::hasColumn('specialists', 'key_procedures')) {
                $table->text('key_procedures')->nullable()->after('sub_specialties');
            }
            if (! Schema::hasColumn('specialists', 'languages')) {
                $table->json('languages')->nullable()->after('key_procedures');
            }
            if (! Schema::hasColumn('specialists', 'consultation_in_person')) {
                $table->boolean('consultation_in_person')->default(true)->after('languages');
            }
            if (! Schema::hasColumn('specialists', 'consultation_teleconsult')) {
                $table->boolean('consultation_teleconsult')->default(false)->after('consultation_in_person');
            }
            if (! Schema::hasColumn('specialists', 'bio')) {
                $table->text('bio')->nullable()->after('consultation_teleconsult');
            }
            if (! Schema::hasColumn('specialists', 'videos')) {
                $table->json('videos')->nullable()->after('bio');
            }
            if (! Schema::hasColumn('specialists', 'certificates')) {
                $table->json('certificates')->nullable()->after('videos');
            }
        });
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            foreach ([
                'hospital_name',
                'clinic_street',
                'clinic_area',
                'clinic_pincode',
                'years_of_experience',
                'sub_specialties',
                'key_procedures',
                'languages',
                'consultation_in_person',
                'consultation_teleconsult',
                'bio',
                'videos',
                'certificates',
            ] as $column) {
                if (Schema::hasColumn('specialists', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
