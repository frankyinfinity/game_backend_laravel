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
            if (!Schema::hasColumn('neuron_links', 'condition')) {
                $table->string('condition')->nullable()->after('to_neuron_id');
            }
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            if (!Schema::hasColumn('element_has_position_neuron_links', 'condition')) {
                $table->string('condition')->nullable()->after('to_element_has_position_neuron_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neuron_links', function (Blueprint $table) {
            if (Schema::hasColumn('neuron_links', 'condition')) {
                $table->dropColumn('condition');
            }
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            if (Schema::hasColumn('element_has_position_neuron_links', 'condition')) {
                $table->dropColumn('condition');
            }
        });
    }
};
