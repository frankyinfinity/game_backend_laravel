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
        Schema::create('element_has_tiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_id')->constrained()->onDelete('cascade');
            $table->foreignId('tile_id')->constrained()->onDelete('cascade');
            $table->foreignId('climate_id')->constrained()->onDelete('cascade');
            $table->integer('percentage')->default(0);
            
            // Unicità per evitare duplicati della stessa combinazione
            $table->unique(['element_id', 'tile_id', 'climate_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_tiles');
    }
};
