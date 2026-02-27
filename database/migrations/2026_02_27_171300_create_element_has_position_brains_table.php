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
        Schema::create('element_has_position_brains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_has_position_id')->constrained()->cascadeOnDelete();
            $table->string('uid')->unique();
            $table->unsignedInteger('grid_width')->default(5);
            $table->unsignedInteger('grid_height')->default(5);
            $table->timestamps();

            $table->unique('element_has_position_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_position_brains');
    }
};

