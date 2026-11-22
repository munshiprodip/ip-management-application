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
        Schema::create('ip_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 32)->unique();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('request_type', 32)->index();
            $table->string('target_type', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 32)->default('pending_approval')->index();
            $table->json('payload_json');
            $table->text('reason');
            $table->text('rejection_reason')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('sla_due_at')->nullable()->index();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->foreignId('provisioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'sla_due_at']);
            $table->index(['requester_id', 'status']);
        });

        Schema::create('request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('ip_requests')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision', 20);
            $table->text('comments')->nullable();
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['request_id', 'decision']);
        });

        Schema::create('change_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained('ip_requests')->nullOnDelete();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('change_type', 64);
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->string('result', 20);
            $table->text('error_message')->nullable();
            $table->uuid('correlation_id')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_records');
        Schema::dropIfExists('request_approvals');
        Schema::dropIfExists('ip_requests');
    }
};
