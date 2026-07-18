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
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('code', 120)->unique();
            $table->string('plain_label', 160)->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });

        Schema::table('specialists', function (Blueprint $table) {
            if (! Schema::hasColumn('specialists', 'specialty_id')) {
                $table
                    ->foreignId('specialty_id')
                    ->nullable()
                    ->after('primary_specialization')
                    ->constrained('specialties')
                    ->nullOnDelete();
                $table->index(['specialty_id'], 'specialists_specialty_id_index');
            }
        });

        $names = DB::table('specialists')
            ->whereNotNull('primary_specialization')
            ->select('primary_specialization')
            ->distinct()
            ->orderBy('primary_specialization')
            ->pluck('primary_specialization')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->values();

        $existingCodes = [];
        $now = now();

        foreach ($names as $name) {
            $name = trim($name);
            $base = Str::slug($name);
            $code = $base !== '' ? $base : Str::slug('specialty-'.$name);
            if ($code === '') {
                $code = 'specialty';
            }

            $i = 1;
            $candidate = $code;
            while (isset($existingCodes[$candidate]) || DB::table('specialties')->where('code', $candidate)->exists()) {
                $i++;
                $candidate = $code.'-'.$i;
            }
            $code = $candidate;
            $existingCodes[$code] = true;

            $specialtyId = DB::table('specialties')->insertGetId([
                'name' => $name,
                'code' => $code,
                'plain_label' => null,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('specialists')
                ->where('primary_specialization', $name)
                ->whereNull('specialty_id')
                ->update(['specialty_id' => $specialtyId]);
        }
    }

    public function down(): void
    {
        Schema::table('specialists', function (Blueprint $table) {
            if (Schema::hasColumn('specialists', 'specialty_id')) {
                $table->dropIndex('specialists_specialty_id_index');
                $table->dropForeign(['specialty_id']);
                $table->dropColumn('specialty_id');
            }
        });

        Schema::dropIfExists('specialties');
    }
};
