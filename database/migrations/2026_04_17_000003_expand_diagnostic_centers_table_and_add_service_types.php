<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_centers', function (Blueprint $table) {
            if (! Schema::hasColumn('diagnostic_centers', 'center_type')) {
                $table->string('center_type', 80)->nullable()->after('name');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'address')) {
                $table->string('address', 255)->nullable()->after('center_type');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'micro_area')) {
                $table->string('micro_area', 190)->nullable()->after('address');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'email')) {
                $table->string('email', 190)->nullable()->after('micro_area');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'mobile_number')) {
                $table->string('mobile_number', 20)->nullable()->after('email');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'alternate_number')) {
                $table->string('alternate_number', 20)->nullable()->after('mobile_number');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'opening_time')) {
                $table->time('opening_time')->nullable()->after('alternate_number');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'closing_time')) {
                $table->time('closing_time')->nullable()->after('opening_time');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'available_days')) {
                $table->json('available_days')->nullable()->after('closing_time');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'weekly_off')) {
                $table->string('weekly_off', 120)->nullable()->after('available_days');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'authorized_person_name')) {
                $table->string('authorized_person_name', 190)->nullable()->after('weekly_off');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'authorized_person_role')) {
                $table->string('authorized_person_role', 120)->nullable()->after('authorized_person_name');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'authorized_person_mobile')) {
                $table->string('authorized_person_mobile', 20)->nullable()->after('authorized_person_role');
            }
            if (! Schema::hasColumn('diagnostic_centers', 'authorized_person_email')) {
                $table->string('authorized_person_email', 190)->nullable()->after('authorized_person_mobile');
            }
        });

        Schema::create('diagnostic_service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 190)->unique();
            $table->string('code', 190)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::table('diagnostic_services', function (Blueprint $table) {
            if (! Schema::hasColumn('diagnostic_services', 'diagnostic_service_type_id')) {
                $table
                    ->foreignId('diagnostic_service_type_id')
                    ->nullable()
                    ->after('diagnostic_center_id')
                    ->constrained('diagnostic_service_types')
                    ->nullOnDelete();
                $table->index(['diagnostic_center_id', 'status', 'diagnostic_service_type_id'], 'dx_services_center_status_type');
            }
        });

        $names = DB::table('diagnostic_services')
            ->whereNotNull('name')
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->values();

        $existingCodes = [];
        $now = now();

        foreach ($names as $name) {
            $name = trim($name);
            $base = Str::slug($name);
            $code = $base !== '' ? $base : 'service';
            $i = 1;
            $candidate = $code;
            while (isset($existingCodes[$candidate]) || DB::table('diagnostic_service_types')->where('code', $candidate)->exists()) {
                $i++;
                $candidate = $code.'-'.$i;
            }
            $code = $candidate;
            $existingCodes[$code] = true;

            $typeId = DB::table('diagnostic_service_types')->insertGetId([
                'name' => $name,
                'code' => $code,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('diagnostic_services')
                ->where('name', $name)
                ->whereNull('diagnostic_service_type_id')
                ->update(['diagnostic_service_type_id' => $typeId]);
        }
    }

    public function down(): void
    {
        Schema::table('diagnostic_services', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_services', 'diagnostic_service_type_id')) {
                $table->dropIndex('dx_services_center_status_type');
                $table->dropForeign(['diagnostic_service_type_id']);
                $table->dropColumn('diagnostic_service_type_id');
            }
        });

        Schema::dropIfExists('diagnostic_service_types');

        Schema::table('diagnostic_centers', function (Blueprint $table) {
            foreach ([
                'center_type',
                'address',
                'micro_area',
                'email',
                'mobile_number',
                'alternate_number',
                'opening_time',
                'closing_time',
                'available_days',
                'weekly_off',
                'authorized_person_name',
                'authorized_person_role',
                'authorized_person_mobile',
                'authorized_person_email',
            ] as $column) {
                if (Schema::hasColumn('diagnostic_centers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
