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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('viewer')->after('password')->index();
            $table->string('status', 24)->default('active')->after('role')->index();
            $table->string('department', 100)->nullable()->after('status');
            $table->string('phone', 32)->nullable()->after('department');
            $table->string('timezone', 64)->default('Asia/Dhaka')->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'department', 'phone', 'timezone']);
        });
    }
};
