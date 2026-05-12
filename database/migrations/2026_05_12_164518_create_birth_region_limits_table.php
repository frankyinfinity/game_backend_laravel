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
        Schema::create('birth_region_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('birth_region_id');
            $table->foreign('birth_region_id')->references('id')->on('birth_regions')->onDelete('cascade');
            $table->json('json_family_tile');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('birth_region_limits');
    }
};
