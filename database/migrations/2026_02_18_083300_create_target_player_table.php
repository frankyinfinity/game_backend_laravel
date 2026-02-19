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
        Schema::create('target_player', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('phase_column_player_id')->constrained('phase_column_player')->onDelete('cascade');
            $table->foreignId('target_id')->constrained()->onDelete('cascade');
            $table->integer('slot');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('state')->default('locked');
            $table->timestamps();
            
            // Ensure unique combination of player_id and target_id
            $table->unique(['player_id', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_player');
    }
};