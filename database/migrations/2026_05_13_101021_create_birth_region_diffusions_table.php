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
        Schema::create('birth_region_diffusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('birth_region_id')->constrained('birth_regions')->onDelete('cascade');
            $table->foreignId('family_tile_id')->constrained('family_tiles')->onDelete('cascade');
            $table->json('json_family_tile');
            $table->timestamps();

            $table->unique(['birth_region_id', 'family_tile_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birth_region_diffusions');
    }
};
