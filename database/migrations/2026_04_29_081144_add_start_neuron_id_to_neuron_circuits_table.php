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
        Schema::table('neuron_circuits', function (Blueprint $table) {
            $table->unsignedBigInteger('start_neuron_id')->nullable()->index()->after('brain_id');
            $table->foreign('start_neuron_id')->references('id')->on('neurons')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neuron_circuits', function (Blueprint $table) {
            $table->dropForeign(['start_neuron_id']);
            $table->dropColumn('start_neuron_id');
        });
    }
};
