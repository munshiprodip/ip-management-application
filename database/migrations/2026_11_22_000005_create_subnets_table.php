<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subnets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('subnets')->nullOnDelete();
            $table->foreignId('vlan_id')->nullable()->constrained('vlans')->nullOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->string('cidr', 64);
            $table->tinyInteger('ip_version');
            $table->string('network_hex', 32)->index();
            $table->string('start_hex', 32)->index();
            $table->string('end_hex', 32)->index();
            $table->unsignedSmallInteger('prefix_length');
            $table->string('subnet_type', 32)->default('assignable');
            $table->string('status', 24)->default('active')->index();
            $table->boolean('allocation_enabled')->default(true);
            $table->timestamps();

            $table->index(['ip_version', 'network_hex', 'prefix_length'], 'subnets_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subnets');
    }
};
