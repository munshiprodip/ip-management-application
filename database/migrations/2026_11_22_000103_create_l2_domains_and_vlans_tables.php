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
        // Layer 2 Domains (Broadcast Domain namespace per site/campus)
        Schema::create('l2_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->string('code', 64)->index();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->string('status', 24)->default('active')->index(); // 'active', 'retired'
            $table->timestamps();

            $table->unique(['site_id', 'code']);
        });

        // VLANs (Strictly unique per L2 Domain: (l2_domain_id, vlan_id))
        Schema::create('vlans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('l2_domain_id')->constrained('l2_domains')->restrictOnDelete();
            $table->unsignedSmallInteger('vlan_id'); // 1 to 4094
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->string('security_zone', 48)->default('internal')->index(); // clinical, pacs, management, etc.
            $table->string('status', 24)->default('active')->index(); // 'active', 'reserved', 'deprecated'
            $table->timestamps();

            $table->unique(['l2_domain_id', 'vlan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vlans');
        Schema::dropIfExists('l2_domains');
    }
};

