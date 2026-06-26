<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_has_position_component_brains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_component_id');
            $table->string('uid');
            $table->integer('grid_width')->default(5);
            $table->integer('grid_height')->default(5);
            $table->timestamps();

            $table->foreign('element_has_position_component_id', 'ehp_comp_brains_comp_fk')
                ->references('id')->on('element_has_position_components')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_has_position_component_brains');
    }
};
