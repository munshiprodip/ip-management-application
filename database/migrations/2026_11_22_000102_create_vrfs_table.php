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
        Schema::create('vrfs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 128);
            $table->text('description')->nullable();
            $table->string('route_distinguisher', 64)->nullable()->index();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->boolean('is_default')->default(false)->index();
            $table->string('status', 24)->default('active')->index(); // 'active', 'retired'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vrfs');
    }
};

