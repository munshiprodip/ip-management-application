<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subnet_id')->constrained('subnets')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('pool_type', 24)->default('static');
            $table->string('start_address', 64);
            $table->string('end_address', 64);
            $table->string('start_hex', 32)->index();
            $table->string('end_hex', 32)->index();
            $table->timestamps();
        });

        Schema::create('ip_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subnet_id')->constrained('subnets')->cascadeOnDelete();
            $table->string('start_address', 64);
            $table->string('end_address', 64);
            $table->string('start_hex', 32)->index();
            $table->string('end_hex', 32)->index();
            $table->string('reason', 255)->nullable();
            $table->string('status', 24)->default('active');
            $table->timestamps();
        });

        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subnet_id')->constrained('subnets')->cascadeOnDelete();
            $table->tinyInteger('ip_version');
            $table->string('address_hex', 32)->index();
            $table->string('address_text', 64);
            $table->string('allocation_status', 24)->default('unassigned')->index();
            $table->string('classification', 32)->default('static_host');
            $table->string('hostname', 255)->nullable()->index();
            $table->string('mac_address', 32)->nullable()->index();
            $table->string('assigned_to', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('quarantined_until')->nullable();
            $table->string('quarantine_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['subnet_id', 'address_hex']);
        });

        Schema::create('ip_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ip_address_id')->constrained('ip_addresses')->cascadeOnDelete();
            $table->string('assigned_to', 255)->nullable();
            $table->string('hostname', 255)->nullable();
            $table->string('mac_address', 32)->nullable();
            $table->string('assignment_method', 32)->default('manual');
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 64)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('ip_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ip_address_id')->constrained('ip_addresses')->cascadeOnDelete();
            $table->foreignId('reserved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('purpose');
            $table->timestamp('reserved_until')->nullable();
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

