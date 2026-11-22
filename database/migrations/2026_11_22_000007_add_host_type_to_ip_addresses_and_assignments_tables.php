<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->string('host_type', 32)->nullable()->after('classification')->index();
        });

        Schema::table('ip_assignments', function (Blueprint $table) {
            $table->string('host_type', 32)->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('ip_assignments', function (Blueprint $table) {
            $table->dropColumn('host_type');
        });

        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropColumn('host_type');
        });
    }
};

