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
        Schema::create('star_zone_has_planet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('star_zone_id')->constrained('star_zone')->cascadeOnDelete();
            $table->foreignId('planet_id')->constrained('planets')->cascadeOnDelete();
            $table->integer('radius');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('star_zone_has_planet');
    }
};
