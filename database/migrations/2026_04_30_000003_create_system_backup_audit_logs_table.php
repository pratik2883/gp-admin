<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_backup_audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('system_backup_id')->nullable()->constrained('system_backups')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 60);
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backup_audit_logs');
    }
};

