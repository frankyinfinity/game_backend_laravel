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
        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->foreignId('chemical_element_id')->nullable()->after('gene_attack_id')->constrained('chimical_elements')->nullOnDelete();
            $table->foreignId('complex_chemical_element_id')->nullable()->after('chemical_element_id')->constrained('complex_chimical_elements')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->dropForeign(['chemical_element_id']);
            $table->dropForeign(['complex_chemical_element_id']);
            $table->dropColumn(['chemical_element_id', 'complex_chemical_element_id']);
        });
    }
};
