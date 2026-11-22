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
        Schema::create('discovery_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 32)->unique();
            $table->string('identity_ref', 100);
            $table->string('api_key_hash', 64)->nullable()->unique();
            $table->boolean('enabled')->default(true)->index();
            $table->json('allowed_subnets_json')->nullable();
            $table->json('protocols_json')->nullable();
            $table->integer('rate_limit_pps')->default(50);
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('discovery_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('discovery_agents')->cascadeOnDelete();
            $table->foreignId('subnet_id')->constrained('subnets')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type', 32)->default('fixture');
            $table->string('status', 32)->default('queued')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('metrics_json')->nullable();
            $table->text('error_summary')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
        });

        Schema::create('ip_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovery_job_id')->constrained('discovery_jobs')->cascadeOnDelete();
            $table->foreignId('vrf_id')->constrained('vrfs')->restrictOnDelete();
            $table->foreignId('subnet_id')->constrained('subnets')->restrictOnDelete();
            $table->foreignId('ip_address_id')->nullable()->constrained('ip_addresses')->nullOnDelete();
            $table->tinyInteger('ip_version')->index();
            $table->string('address_hex', 32)->index();
            $table->string('address_text', 45)->index();
            $table->string('observed_mac', 20)->nullable()->index();
            $table->string('hostname', 255)->nullable();
            $table->string('source_type', 32)->index();
            $table->tinyInteger('confidence')->default(100);
            $table->timestamp('observed_at')->index();
            $table->json('payload_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['vrf_id', 'address_hex', 'observed_at']);
        });

        Schema::create('reconciliation_findings', function (Blueprint $table) {
            $table->id();
            $table->string('finding_type', 32)->index();
            $table->foreignId('vrf_id')->constrained('vrfs')->restrictOnDelete();
            $table->foreignId('subnet_id')->constrained('subnets')->restrictOnDelete();
            $table->foreignId('ip_address_id')->nullable()->constrained('ip_addresses')->nullOnDelete();
            $table->string('address_text', 45)->index();
            $table->string('severity', 20)->default('warning')->index();
            $table->string('status', 20)->default('open')->index();
            $table->string('title', 255);
            $table->text('details');
            $table->string('observed_mac', 20)->nullable();
            $table->string('authoritative_mac', 20)->nullable();
            $table->json('evidence_refs_json')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_findings');
        Schema::dropIfExists('ip_observations');
        Schema::dropIfExists('discovery_jobs');
        Schema::dropIfExists('discovery_agents');
    }
};
