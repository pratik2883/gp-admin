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
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('hospital_type')->nullable();     // 20-Bed Hospital, Multi-Speciality…
            $table->string('address')->nullable();
            $table->string('micro_area')->nullable();        // Andheri West
            $table->string('city')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('India');

            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();

            // Authorized representative
            $table->string('admin_name')->nullable();
            $table->string('admin_designation')->nullable();
            $table->string('admin_mobile')->nullable();
            $table->string('admin_email')->nullable();

            // Status for admin panel
            $table->enum('status', ['active', 'inactive', 'pending'])
                ->default('active');

            $table->timestamps();

            $table->index(['city', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospitals');
    }
};
