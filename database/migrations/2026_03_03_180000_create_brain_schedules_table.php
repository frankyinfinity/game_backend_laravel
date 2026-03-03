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
        Schema::create('brain_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_has_position_id')->constrained('element_has_positions')->cascadeOnDelete();
            $table->string('state', 32)->default('create');
            $table->timestamps();

            $table->index(['element_has_position_id', 'state'], 'brain_schedules_element_state_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brain_schedules');
    }
};
