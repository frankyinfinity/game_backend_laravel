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
        Schema::dropIfExists('containers');
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('parent_type');
            $table->string('parent_id');
            $table->longText('container_id');
            $table->longText('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('containers');
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('parent_type');
            $table->string('parent_id');
            $table->longText('name');
            $table->longText('image');
            $table->timestamps();
        });
    }
};
