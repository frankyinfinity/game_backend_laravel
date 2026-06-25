<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_has_position_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_id');
            $table->unsignedBigInteger('element_component_id');
            $table->string('name');
            $table->integer('characteristic')->default(0);
            $table->string('image')->nullable();
            $table->timestamps();

            $table->foreign('element_has_position_id', 'ehp_components_ehp_fk')
                ->references('id')->on('element_has_positions')->onDelete('cascade');
            $table->foreign('element_component_id', 'ehp_components_ec_fk')
                ->references('id')->on('element_components')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_has_position_components');
    }
};
