<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. MR profile table
        Schema::create('mrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('employee_code')->unique()->nullable();
            $table->string('territory_zone')->nullable();
            $table->string('headquarters_city')->nullable();
            $table->unsignedInteger('daily_visit_target')->default(10);
            $table->unsignedInteger('monthly_subscription_target')->default(5);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 2. MR Visits (Daily Call Report - DCR)
        Schema::create('mr_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mr_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('doctor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('doctor_type', ['gp', 'specialist', 'diagnostic_center'])->default('gp');
            $table->string('doctor_name')->nullable();
            $table->string('clinic_name')->nullable();
            $table->date('visit_date');
            $table->string('visit_purpose')->default('pitching'); // pitching, training, renewal, feedback, complaint, other
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('selfie_path')->nullable();
            $table->timestamps();
        });

        // 3. MR Attendances
        Schema::create('mr_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mr_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_in_lat', 10, 8)->nullable();
            $table->decimal('check_in_lng', 11, 8)->nullable();
            $table->string('selfie_path')->nullable();
            $table->enum('status', ['present', 'half_day', 'absent'])->default('present');
            $table->timestamps();
        });

        // 4. MR Leaves
        Schema::create('mr_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mr_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 5. Collaterals & Demo Library
        Schema::create('collaterals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('brochure'); // brochure, video, presentation
            $table->enum('file_type', ['pdf', 'video', 'link'])->default('pdf');
            $table->string('file_url');
            $table->string('thumbnail_url')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaterals');
        Schema::dropIfExists('mr_leaves');
        Schema::dropIfExists('mr_attendances');
        Schema::dropIfExists('mr_visits');
        Schema::dropIfExists('mrs');
    }
};
