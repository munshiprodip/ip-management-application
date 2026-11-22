<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subnets', function (Blueprint $table) {
            $table->string('gateway', 64)->nullable()->after('cidr');
            $table->string('subnet_mask', 64)->nullable()->after('gateway');
        });
    }

    public function down(): void
    {
        Schema::table('subnets', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'subnet_mask']);
        });
    }
};
