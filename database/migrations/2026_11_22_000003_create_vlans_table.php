<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vlans', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('vlan_id')->unique()->comment('802.1Q VLAN Tag (1-4094)');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('security_zone', 32)->default('internal');
            $table->string('status', 24)->default('active')->index();
            $table->boolean('is_restricted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vlans');
    }
};

