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
        Schema::create('element_has_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->string('session_id');
            $table->unsignedBigInteger('element_id');
            $table->string('uid');
            $table->integer('tile_i');
            $table->integer('tile_j');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_positions');
    }
};
