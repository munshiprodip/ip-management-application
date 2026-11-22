<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('slug', 64)->unique();
            $table->text('description')->nullable();
            $table->string('color', 32)->default('slate');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        // Seed 8 default enterprise & clinical host types
        $defaults = [
            ['name' => 'Servers', 'slug' => 'server', 'color' => 'indigo', 'description' => 'Database, HIS, application, VM hosts', 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Workstations', 'slug' => 'workstation', 'color' => 'blue', 'description' => 'PCs, laptops, thin clients', 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Network equipment', 'slug' => 'network', 'color' => 'purple', 'description' => 'Switches, routers, firewalls', 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Printers', 'slug' => 'printer', 'color' => 'orange', 'description' => 'Office and clinical printers', 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Wireless', 'slug' => 'wireless', 'color' => 'sky', 'description' => 'APs and controllers', 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Medical devices', 'slug' => 'medical', 'color' => 'rose', 'description' => 'Connected clinical equipment', 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Surveillance', 'slug' => 'surveillance', 'color' => 'amber', 'description' => 'CCTV, NVR, access control', 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Other / IoT', 'slug' => 'other_iot', 'color' => 'slate', 'description' => 'UPS, sensors, virtual device', 'is_system' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ];

        \Illuminate\Support\Facades\DB::table('host_types')->insert($defaults);
    }

    public function down(): void
    {
        Schema::dropIfExists('host_types');
    }
};
