<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Schema unico per la timeline evolutiva:
     *  - timeline_evolution           : una riga per player
     *  - time_evolution_detail        : una riga per evolution step della timeline
     *  - time_evolution_detail_entity : legame N:N fra detail ed entity
     */
    public function up(): void
    {
        Schema::create('timeline_evolution', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->string('uid');
            $table->timestamps();

            $table->foreign('player_id')
                ->references('id')
                ->on('players')
                ->onDelete('cascade');
        });

        Schema::create('timeline_evolution_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('timeline_evolution_id');
            $table->unsignedBigInteger('evolution_step_id')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('creation_date')->useCurrent();
            $table->integer('population_life')->default(1);
            $table->integer('population_death')->default(0);
            $table->timestamps();

            $table->foreign('timeline_evolution_id')
                ->references('id')
                ->on('timeline_evolution')
                ->onDelete('cascade');

            $table->foreign('evolution_step_id')
                ->references('id')
                ->on('evolution_steps')
                ->onDelete('cascade');
        });

        Schema::create('timeline_evolution_detail_entity', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('timeline_evolution_detail_id');
            $table->unsignedBigInteger('entity_id');
            $table->timestamps();

            $table->foreign('timeline_evolution_detail_id', 'fk_timeline_evolution_detail_entity_detail')
                ->references('id')
                ->on('timeline_evolution_detail')
                ->onDelete('cascade');

            $table->foreign('entity_id')
                ->references('id')
                ->on('entities')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline_evolution_detail_entity');
        Schema::dropIfExists('timeline_evolution_detail');
        Schema::dropIfExists('timeline_evolution');
    }
};
