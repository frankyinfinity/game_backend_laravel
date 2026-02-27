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
        Schema::create('element_has_position_neurons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_brain_id');
            $table->string('type');
            $table->unsignedInteger('grid_i');
            $table->unsignedInteger('grid_j');
            $table->unsignedInteger('radius')->nullable();
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_element_id')->nullable();
            $table->timestamps();

            $table->foreign('element_has_position_brain_id', 'ehpn_brain_fk')
                ->references('id')
                ->on('element_has_position_brains')
                ->cascadeOnDelete();
            $table->foreign('target_element_id', 'ehpn_target_element_fk')
                ->references('id')
                ->on('elements')
                ->nullOnDelete();

            $table->unique(['element_has_position_brain_id', 'grid_i', 'grid_j'], 'ehpn_unique_cell');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_position_neurons');
    }
};
