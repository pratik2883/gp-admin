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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();

            $table->string('lead_code')->unique(); // e.g. SSC-0035

            $table->foreignId('gp_id')
                ->constrained('gps')
                ->onDelete('cascade');

            $table->foreignId('specialist_id')
                ->constrained('specialists')
                ->onDelete('cascade');

            // nullable for OPD
            $table->foreignId('hospital_id')
                ->nullable()
                ->constrained('hospitals')
                ->nullOnDelete();

            // Patient fields
            $table->string('patient_name');
            $table->string('patient_mobile')->nullable();
            $table->unsignedTinyInteger('patient_age')->nullable();
            $table->enum('patient_gender', ['male', 'female', 'other'])->nullable();

            // Case
            $table->text('case_summary');
            $table->enum('appointment_type', ['opd', 'ipd'])->default('opd');

            // Status flow: sent -> accepted -> consulted -> closed
            $table->enum('status', ['sent', 'accepted', 'consulted', 'closed'])
                ->default('sent');

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('consulted_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['specialist_id', 'status']);
            $table->index(['gp_id', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
