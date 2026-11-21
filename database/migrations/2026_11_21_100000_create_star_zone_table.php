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
        Schema::create('star_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('star_id')->constrained('star')->cascadeOnDelete();
            $table->string('name');
            $table->integer('from');
            $table->integer('to');
            $table->string('rgb');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('star_zone');
    }
};
