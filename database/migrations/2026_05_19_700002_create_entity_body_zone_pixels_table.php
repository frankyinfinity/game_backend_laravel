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
        Schema::create('entity_body_zone_pixels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_body_zone_id')->constrained('entity_body_zones')->cascadeOnDelete();
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_body_zone_pixels');
    }
};
