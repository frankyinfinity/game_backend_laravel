<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_bodies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('state')->default(0);
            $table->integer('characteristic')->default(0);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_bodies');
    }
};
