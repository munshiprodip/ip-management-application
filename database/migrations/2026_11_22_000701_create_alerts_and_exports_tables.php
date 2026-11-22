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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finding_id')->nullable()->constrained('reconciliation_findings')->nullOnDelete();
            $table->string('rule_code', 64)->index();
            $table->string('dedupe_key', 150)->index();
            $table->string('severity', 20)->default('warning')->index();
            $table->string('status', 20)->default('open')->index();
            $table->string('title', 255);
            $table->text('description');
            $table->json('context_json')->nullable();
            $table->integer('occurrence_count')->default(1);
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->index();
            $table->timestamp('last_occurred_at')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('alerts')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('channel', 20)->default('in_app');
            $table->string('status', 20)->default('delivered');
            $table->string('subject', 255);
            $table->text('body');
            $table->timestamp('sent_at')->index();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->index(['recipient_id', 'read_at']);
        });

        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->string('format', 10)->default('csv');
            $table->json('filters_json')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->string('private_file_path', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->integer('row_count')->default(0);
            $table->bigInteger('file_size_bytes')->default(0);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['requested_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('alerts');
    }
};
