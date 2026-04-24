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
        Schema::create('element_has_position_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_id');
            $table->unsignedBigInteger('element_has_position_rule_chimical_element_id');
            $table->double('value')->default(0);
            $table->timestamps();

            $table->foreign('element_has_position_id', 'ehpce_ehp_fk')
                ->references('id')
                ->on('element_has_positions')
                ->onDelete('cascade');

            $table->foreign('element_has_position_rule_chimical_element_id', 'ehpce_ehprce_fk')
                ->references('id')
                ->on('element_has_position_rule_chimical_elements')
                ->onDelete('cascade');

            $table->index('element_has_position_id', 'ehpce_ehp_idx');
            $table->index('element_has_position_rule_chimical_element_id', 'ehpce_ehprce_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_position_chimical_elements');
    }
};
