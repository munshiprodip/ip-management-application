<?php

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
        Schema::create('subnets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vrf_id')->constrained('vrfs')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('subnets')->restrictOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('vlan_id')->nullable()->constrained('vlans')->nullOnDelete();

            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->string('cidr', 64)->index();
            $table->unsignedTinyInteger('ip_version')->index(); // 4 or 6
            $table->string('network_hex', 32);
            $table->string('start_hex', 32);
            $table->string('end_hex', 32);
            $table->unsignedSmallInteger('prefix_length');

            $table->string('subnet_type', 32)->default('assignable')->index(); // aggregate, assignable, point_to_point, loopback
            $table->string('status', 24)->default('active')->index(); // planned, active, deprecated, retired
            $table->boolean('allocation_enabled')->default(true);
            $table->boolean('scan_enabled')->default(false); // Safe default: discovery disabled

            $table->timestamps();

            // Unique within VRF and address family
            $table->unique(['vrf_id', 'ip_version', 'network_hex', 'prefix_length'], 'subnets_vrf_family_cidr_unique');

            // Range indexing for fast containment lookups across SQLite & MySQL
            $table->index(['vrf_id', 'ip_version', 'start_hex', 'end_hex'], 'subnets_vrf_range_index');
            $table->index(['parent_id', 'status']);
        });

        Schema::create('subnet_vlans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subnet_id')->constrained('subnets')->cascadeOnDelete();
            $table->foreignId('vlan_id')->constrained('vlans')->restrictOnDelete();
            $table->string('association_type', 32)->default('primary'); // primary, secondary, routed
            $table->string('gateway_ip', 64)->nullable();
            $table->timestamps();

            $table->unique(['subnet_id', 'vlan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subnet_vlans');
        Schema::dropIfExists('subnets');
    }
};
