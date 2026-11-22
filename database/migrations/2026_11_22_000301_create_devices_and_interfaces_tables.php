<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Hardware Manufacturers (Cisco, GE Healthcare, Dell, Siemens, etc.)
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->string('website', 255)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        // 2. Device Types (Server, Core Switch, Patient Monitor, CT Scanner, etc.)
        Schema::create('device_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 100);
            $table->string('category', 32)->default('server')->index();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        // 3. Hardware Models
        Schema::create('device_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained('manufacturers')->cascadeOnDelete();
            $table->foreignId('device_type_id')->constrained('device_types')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('model_number', 100)->nullable();
            $table->string('part_number', 100)->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->unique(['manufacturer_id', 'name']);
        });

        // 4. Authoritative Devices (Independent of IPs)
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('hostname', 255)->nullable()->index();
            $table->string('name', 255);
            $table->foreignId('device_type_id')->constrained('device_types')->restrictOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained('manufacturers')->nullOnDelete();
            $table->foreignId('device_model_id')->nullable()->constrained('device_models')->nullOnDelete();
            $table->string('asset_tag', 64)->nullable()->unique();
            $table->string('serial_number', 100)->nullable()->index();
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rack_location', 100)->nullable();
            $table->string('criticality', 32)->default('medium')->index();
            $table->string('status', 32)->default('active')->index();
            $table->string('environment', 32)->default('production')->index();
            $table->string('os_firmware', 255)->nullable();
            $table->boolean('is_monitored')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Device Network Interfaces (Multi-NIC)
        Schema::create('device_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('name', 64);
            $table->unsignedInteger('if_index')->nullable();
            $table->string('type', 32)->default('physical')->index();
            $table->string('mac_address', 24)->nullable()->index();
            $table->foreignId('parent_interface_id')->nullable()->constrained('device_interfaces')->nullOnDelete();
            $table->foreignId('vlan_id')->nullable()->constrained('vlans')->nullOnDelete();
            $table->string('status', 24)->default('up')->index();
            $table->unsignedInteger('speed_mbps')->nullable();
            $table->string('switch_port_label', 100)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'name']);
        });

        // 6. Interface Topology Links
        Schema::create('interface_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_interface_id')->constrained('device_interfaces')->cascadeOnDelete();
            $table->foreignId('to_interface_id')->constrained('device_interfaces')->cascadeOnDelete();
            $table->string('link_type', 32)->default('copper');
            $table->string('status', 24)->default('active')->index();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interface_links');
        Schema::dropIfExists('device_interfaces');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('device_models');
        Schema::dropIfExists('device_types');
        Schema::dropIfExists('manufacturers');
    }
};

