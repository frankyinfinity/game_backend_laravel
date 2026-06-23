<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('element_has_position_bodies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_id');
            $table->string('name');
            $table->integer('characteristic')->default(0);
            $table->string('image')->nullable();
            $table->timestamps();

            $table->foreign('element_has_position_id', 'ehp_bodies_ehp_fk')
                ->references('id')->on('element_has_positions')->onDelete('cascade');
        });

        Schema::create('element_has_position_body_zones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_body_id');
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();

            $table->foreign('element_has_position_body_id', 'ehp_body_zones_body_fk')
                ->references('id')->on('element_has_position_bodies')->onDelete('cascade');
        });

        Schema::create('element_has_position_body_zone_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_body_zone_id');
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();

            $table->foreign('element_has_position_body_zone_id', 'ehp_bz_details_zone_fk')
                ->references('id')->on('element_has_position_body_zones')->onDelete('cascade');
        });

        Schema::create('element_has_position_body_zone_pixels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('element_has_position_body_zone_id');
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();

            $table->foreign('element_has_position_body_zone_id', 'ehp_bz_pixels_zone_fk')
                ->references('id')->on('element_has_position_body_zones')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_has_position_body_zone_pixels');
        Schema::dropIfExists('element_has_position_body_zone_details');
        Schema::dropIfExists('element_has_position_body_zones');
        Schema::dropIfExists('element_has_position_bodies');
    }
};
