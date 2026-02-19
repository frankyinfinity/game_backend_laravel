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
        Schema::create('target_has_score_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('target_player_id')->constrained('target_player')->onDelete('cascade');
            $table->foreignId('score_id')->constrained()->onDelete('cascade');
            $table->integer('value');
            $table->timestamps();
            
            // Unique constraint for target_player_id and score_id
            $table->unique(['target_player_id', 'score_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_has_score_player');
    }
};