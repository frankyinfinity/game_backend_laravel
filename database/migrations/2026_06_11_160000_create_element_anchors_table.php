<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_anchors', function (Blueprint $table) {
            $table->id();
            $table->integer('x');
            $table->integer('y');
            $table->string('anchorable_type');
            $table->unsignedBigInteger('anchorable_id');
            $table->timestamps();

            $table->index(['anchorable_type', 'anchorable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_anchors');
    }
};
