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
        Schema::create('phase_column_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('phase_player_id')->constrained('phase_player')->onDelete('cascade');
            $table->foreignId('phase_column_id')->constrained()->onDelete('cascade');
            $table->string('uid');
            $table->timestamps();
            
            // Ensure unique combination of player_id and phase_column_id
            $table->unique(['player_id', 'phase_column_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase_column_player');
    }
};