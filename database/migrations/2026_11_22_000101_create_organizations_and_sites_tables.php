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
        // 1. Organizations
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();
        });

        // 2. Sites / Campuses
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('code', 32)->index();
            $table->string('name', 150);
            $table->string('description', 255)->nullable();
            $table->string('timezone', 64)->default('Asia/Dhaka');
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // 3. Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->string('code', 32)->index();
            $table->string('name', 150);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        // 4. Locations (Hierarchical: Campus -> Building -> Floor -> Room -> Rack)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->string('type', 32)->default('building')->index(); // building, floor, room, rack, etc.
            $table->string('code', 48)->index();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();

            $table->index(['site_id', 'parent_id', 'code']);
        });

        // 5. User Scopes (Granular site/department/VRF scope restrictions per user)
        Schema::create('user_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope_type', 32); // 'site', 'department', 'vrf'
            $table->unsignedBigInteger('scope_id');
            $table->string('permission_level', 32)->default('read'); // 'read', 'write', 'admin'
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'scope_type', 'scope_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_scopes');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('organizations');
    }
};
