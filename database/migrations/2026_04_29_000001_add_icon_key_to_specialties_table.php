<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('specialties')) {
            return;
        }

        Schema::table('specialties', function (Blueprint $table) {
            if (! Schema::hasColumn('specialties', 'icon_key')) {
                $table->string('icon_key', 80)->nullable();
                $table->index(['icon_key']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('specialties')) {
            return;
        }

        Schema::table('specialties', function (Blueprint $table) {
            if (Schema::hasColumn('specialties', 'icon_key')) {
                $table->dropIndex(['icon_key']);
                $table->dropColumn('icon_key');
            }
        });
    }
};

