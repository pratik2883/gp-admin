<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin panel had no way to hand work to a field officer: a doctor, hospital or
 * a not-yet-registered prospect could not be assigned to an MR for follow-up.
 * This table is that assignment, and the MR app reads it in "My Assignments".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mr_assignments')) {
            return;
        }

        Schema::create('mr_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mr_user_id')->constrained('users')->cascadeOnDelete();

            // Either an existing doctor/hospital/centre (morph), or a free-text
            // lead the MR has to visit and register.
            $table->string('assignable_type')->nullable();
            $table->unsignedBigInteger('assignable_id')->nullable();
            $table->string('lead_name')->nullable();
            $table->string('lead_mobile', 20)->nullable();
            $table->string('lead_city')->nullable();

            $table->string('title');
            $table->string('purpose')->default('follow_up');
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'done', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('mr_remarks')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['mr_user_id', 'status']);
            $table->index(['assignable_type', 'assignable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mr_assignments');
    }
};
