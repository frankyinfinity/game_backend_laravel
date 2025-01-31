<?php

use App\Models\Entity;
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
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('specie_id');
            $table->foreign('specie_id')->references('id')->on('species');
            $table->longText('uid');
            $table->integer('tile_i')->nullable();
            $table->integer('tile_j')->nullable();
            $table->integer('state')->default(Entity::STATE_LIFE);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
