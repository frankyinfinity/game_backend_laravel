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
        Schema::create('target_link_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_target_player_id')->constrained('target_player')->onDelete('cascade');
            $table->foreignId('to_target_player_id')->constrained('target_player')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure unique link between targets for a player (short name to avoid identifier too long error)
            $table->unique(['player_id', 'from_target_player_id', 'to_target_player_id'], 'tlp_unique_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_link_player');
    }
};