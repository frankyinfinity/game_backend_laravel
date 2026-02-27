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
        Schema::create('brains', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->unsignedInteger('grid_width')->default(5);
            $table->unsignedInteger('grid_height')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brains');
    }
};

