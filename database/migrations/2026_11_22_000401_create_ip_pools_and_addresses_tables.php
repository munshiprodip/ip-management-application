<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. IP Pools (Scoped to a leaf subnet)
        Schema::create('ip_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subnet_id')->constrained('subnets')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('start_address', 45);
            $table->string('end_address', 45);
            $table->string('start_hex', 32)->index();
            $table->string('end_hex', 32)->index();
            $table->string('type', 32)->default('static')->index(); // static, dhcp_dynamic, etc.
            $table->string('allocation_strategy', 32)->default('sequential');
            $table->string('status', 24)->default('active')->index();
            $table->integer('priority')->default(100);
            $table->timestamps();
        });

        // 2. IP Exclusions (Static IP Ranges, VIPs, or DHCP bounds excluded from candidate offering)
        Schema::create('ip_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subnet_id')->constrained('subnets')->cascadeOnDelete();
            $table->foreignId('ip_pool_id')->nullable()->constrained('ip_pools')->cascadeOnDelete();
            $table->string('start_address', 45);
            $table->string('end_address', 45);
            $table->string('start_hex', 32)->index();
            $table->string('end_hex', 32)->index();
            $table->string('reason', 255);
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();
        });

        // 3. Materialized IP Records (Authoritative address state per VRF)
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vrf_id')->constrained('vrfs')->restrictOnDelete();
            $table->foreignId('subnet_id')->constrained('subnets')->restrictOnDelete();
            $table->tinyInteger('ip_version')->index(); // 4 or 6
            $table->string('address_hex', 32)->index(); // fixed-width 8 or 32 chars
            $table->string('address_text', 45);
            $table->string('allocation_status', 32)->default('unassigned')->index(); // unassigned, assigned, reserved, quarantined, blocked
            $table->string('connectivity_status', 32)->default('unobserved')->index(); // observed_online, observed_offline, unobserved
            $table->string('classification', 32)->default('static_host')->index();
            $table->unsignedBigInteger('current_assignment_id')->nullable()->index();
            $table->string('hostname', 255)->nullable()->index();
            $table->string('fqdn', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('quarantined_until')->nullable()->index();
            $table->string('quarantine_reason', 255)->nullable();
            $table->timestamp('last_reconciled_at')->nullable();
            $table->timestamps();

            // Strict Invariant T-006 & T-007: Address uniqueness is per (vrf_id, ip_version, address_hex)
            $table->unique(['vrf_id', 'ip_version', 'address_hex']);
        });

        // 4. Historical & Current IP Assignments
        Schema::create('ip_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ip_address_id')->constrained('ip_addresses')->cascadeOnDelete();
            $table->foreignId('interface_id')->nullable()->constrained('device_interfaces')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('released_at')->nullable()->index();
            $table->string('assignment_method', 32)->default('manual_static');
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('release_reason', 255)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->string('idempotency_key', 64)->nullable()->index();
            $table->timestamps();
        });

        // 5. Future IP Reservations
        Schema::create('ip_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ip_address_id')->constrained('ip_addresses')->cascadeOnDelete();
            $table->foreignId('reserved_for_device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('reserved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 255);
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_reservations');
        Schema::dropIfExists('ip_assignments');
        Schema::dropIfExists('ip_addresses');
        Schema::dropIfExists('ip_exclusions');
        Schema::dropIfExists('ip_pools');
    }
};
