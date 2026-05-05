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
        Schema::table('neuron_links', function (Blueprint $table) {
            $table->dropForeign(['from_neuron_id']);
            $table->dropForeign(['to_neuron_id']);
            $table->dropUnique(['from_neuron_id', 'to_neuron_id']);
            $table->foreign('from_neuron_id')->references('id')->on('neurons')->cascadeOnDelete();
            $table->foreign('to_neuron_id')->references('id')->on('neurons')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neuron_links', function (Blueprint $table) {
            $table->dropForeign(['from_neuron_id']);
            $table->dropForeign(['to_neuron_id']);
            $table->unique(['from_neuron_id', 'to_neuron_id']);
            $table->foreign('from_neuron_id')->references('id')->on('neurons')->cascadeOnDelete();
            $table->foreign('to_neuron_id')->references('id')->on('neurons')->cascadeOnDelete();
        });
    }
};
