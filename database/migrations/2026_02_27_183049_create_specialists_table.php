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
        Schema::create('specialists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Profile
            $table->string('profile_photo_path')->nullable();
            $table->string('whatsapp_number')->nullable();

            // Primary specialization for filters (Cardiology, Neurology, ENT…)
            $table->string('primary_specialization')->nullable();

            // Education (you can also normalize; keeping JSON for speed)
            $table->json('education_primary')->nullable();   // {degree, university, year}
            $table->json('education_postgrad')->nullable();  // {degree, university, year}
            $table->json('additional_qualifications')->nullable(); // array

            // Professional details
            $table->string('medical_council_registration_no')->nullable();
            $table->string('medical_council_name')->nullable();

            // Practice / clinic
            $table->string('clinic_name')->nullable();
            $table->string('clinic_address')->nullable();
            $table->string('clinic_city')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['primary_specialization', 'clinic_city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialists');
    }
};
