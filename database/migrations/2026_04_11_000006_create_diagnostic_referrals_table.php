<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_referrals', function (Blueprint $table) {
            $table->id();
            $table->string('lead_code')->unique();

            $table->foreignId('gp_id')->constrained('gps')->cascadeOnDelete();
            $table->foreignId('diagnostic_center_id')->constrained('diagnostic_centers')->cascadeOnDelete();

            $table->string('patient_name');
            $table->string('patient_mobile')->nullable();
            $table->unsignedTinyInteger('patient_age')->nullable();
            $table->enum('patient_gender', ['male', 'female', 'other'])->nullable();

            $table->text('case_summary');
            $table->enum('appointment_type', ['opd', 'ipd'])->default('opd');
            $table->enum('priority', ['routine', 'urgent'])->default('routine');

            $table->enum('status', ['sent', 'accepted', 'consulted', 'closed'])->default('sent');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('consulted_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['gp_id', 'status']);
            $table->index(['diagnostic_center_id', 'status']);
            $table->index(['created_at']);
        });

        Schema::create('diagnostic_referral_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_referral_id')->constrained('diagnostic_referrals')->cascadeOnDelete();
            $table->foreignId('diagnostic_service_id')->constrained('diagnostic_services')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['diagnostic_referral_id', 'diagnostic_service_id'], 'diag_ref_service_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_referral_service');
        Schema::dropIfExists('diagnostic_referrals');
    }
};
