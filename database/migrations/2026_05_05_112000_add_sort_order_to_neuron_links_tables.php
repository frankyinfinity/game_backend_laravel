<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('neuron_links', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('condition');
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('neuron_links', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('element_has_position_neuron_links', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
