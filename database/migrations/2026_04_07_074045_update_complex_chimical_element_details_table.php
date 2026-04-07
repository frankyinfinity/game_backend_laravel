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
        Schema::table('complex_chimical_element_details', function (Blueprint $table) {
            // Drop current foreign keys before renaming/modifying
            $table->dropForeign('cce_details_cce_id_fk');
            $table->dropForeign('cce_details_ce_id_fk');

            // Rename existing owner field to parent_id
            $table->renameColumn('complex_chimical_element_id', 'parent_id');

            // Make existing component field nullable
            $table->unsignedBigInteger('chimical_element_id')->nullable()->change();

            // Add new component field for sub-complex elements
            $table->unsignedBigInteger('complex_chimical_element_id')->nullable()->after('chimical_element_id');

            // Re-add foreign keys
            $table->foreign('parent_id', 'cce_details_parent_id_fk')->references('id')->on('complex_chimical_elements')->onDelete('cascade');
            $table->foreign('chimical_element_id', 'cce_details_ce_id_fk')->references('id')->on('chimical_elements')->onDelete('cascade');
            $table->foreign('complex_chimical_element_id', 'cce_details_cce_id_fk')->references('id')->on('complex_chimical_elements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('complex_chimical_element_details', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['chimical_element_id']);
            $table->dropForeign(['complex_chimical_element_id']);

            $table->dropColumn('complex_chimical_element_id');
            $table->renameColumn('parent_id', 'complex_chimical_element_id');
            $table->unsignedBigInteger('chimical_element_id')->change(); // This might fail if database doesn't support changing back to NOT NULL if data exists

            $table->foreign('complex_chimical_element_id', 'cce_details_cce_id_fk')->references('id')->on('complex_chimical_elements')->onDelete('cascade');
            $table->foreign('chimical_element_id', 'cce_details_ce_id_fk')->references('id')->on('chimical_elements')->onDelete('cascade');
        });
    }
};
