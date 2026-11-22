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
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('token_hash', 64)->unique();
            $table->string('token_prefix', 16)->index();
            $table->json('scopes_json');
            $table->integer('rate_limit_per_minute')->default(60);
            $table->timestamp('last_used_at')->nullable()->index();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at']);
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index(); // dhcp_server, dns_server, active_directory, cmdb
            $table->string('name', 128);
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->json('config_json')->nullable();
            $table->string('credential_ref', 128)->nullable();
            $table->boolean('enabled')->default(false)->index();
            $table->string('status', 24)->default('idle')->index(); // idle, syncing, degraded, failed
            $table->timestamp('last_sync_at')->nullable()->index();
            $table->string('last_sync_status', 24)->nullable();
            $table->timestamps();
        });

        Schema::create('integration_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
            $table->string('sync_type', 32)->default('full'); // dhcp_leases, dns_records, full
            $table->string('status', 24)->default('running')->index(); // running, succeeded, partial, failed
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->integer('items_scanned')->default(0);
            $table->integer('items_matched')->default(0);
            $table->integer('items_divergent')->default(0);
            $table->json('summary_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['integration_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_runs');
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('api_tokens');
    }
};
