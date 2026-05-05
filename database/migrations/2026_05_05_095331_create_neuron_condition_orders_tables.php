<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('neuron_condition_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('neuron_id')->constrained('neurons')->onDelete('cascade');
            $table->string('condition');
            $table->integer('sort_order')->default(0);
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('element_has_position_neuron_condition_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_has_position_neuron_id')
                ->constrained('element_has_position_neurons')
                ->onDelete('cascade')
                ->name('ehp_neuron_cond_orders_neuron_id_foreign');
            $table->string('condition');
            $table->integer('sort_order')->default(0);
            $table->string('color')->nullable();
            $table->timestamps();
        });

        // Remove sort_order and color from links tables
        Schema::table('neuron_links', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'color']);
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->dropColumn(['sort_order', 'color']);
        });
    }

    public function down()
    {
        Schema::table('neuron_links', function (Blueprint $table) {
            $table->integer('sort_order')->default(0);
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->integer('sort_order')->default(0);
        });

        Schema::dropIfExists('element_has_position_neuron_condition_orders');
        Schema::dropIfExists('neuron_condition_orders');
    }
};
