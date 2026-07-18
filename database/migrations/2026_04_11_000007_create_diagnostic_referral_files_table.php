<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_referral_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('diagnostic_referral_id')
                ->constrained('diagnostic_referrals')
                ->cascadeOnDelete();

            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_referral_files');
    }
};
