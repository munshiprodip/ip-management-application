<?php

declare(strict_types=1);

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
        Schema::create('scan_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subnet_id')->constrained('subnets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('pending')->index(); // pending, running, completed, cancelled, failed
            $table->unsignedInteger('total_targets')->default(0);
            $table->unsignedInteger('scanned_count')->default(0);
            $table->unsignedInteger('responding_count')->default(0);
            $table->unsignedInteger('unregistered_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scan_job_id')->constrained('scan_jobs')->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->string('ip_hex', 32);
            $table->string('status', 32); // responding, no_reply, error
            $table->float('response_time_ms')->nullable();
            $table->string('discovered_hostname')->nullable();
            $table->string('discovered_mac', 32)->nullable();
            $table->string('ipam_status', 32)->default('unassigned'); // unassigned, assigned, reserved, quarantined
            $table->string('ipam_hostname')->nullable();
            $table->string('ipam_assigned_to')->nullable();
            $table->boolean('is_unregistered')->default(false)->index();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['scan_job_id', 'ip_address']);
            $table->index(['scan_job_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_results');
        Schema::dropIfExists('scan_jobs');
    }
};

