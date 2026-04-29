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
            $table->string('color')->nullable()->after('condition');
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->string('color')->nullable()->after('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neuron_links', function (Blueprint $table) {
            $table->dropColumn('color');
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
