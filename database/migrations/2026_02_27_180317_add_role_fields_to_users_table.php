<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile')->unique()->after('email');
            $table->enum('role', ['gp', 'specialist', 'hospital_admin', 'admin'])
                ->default('gp')
                ->after('mobile');
            $table->enum('status', ['active', 'blocked'])
                ->default('active')
                ->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile', 'role', 'status']);
        });
    }
};
